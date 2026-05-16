<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Leave::with(['user', 'substituteUser', 'manager'])->latest();

        if ($user->hasRole('Admin') || $user->hasAnyRole(['internalManager', 'InternalManager'])) {
            // همه را می‌بیند
        } elseif ($user->hasRole('Manager')) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhere('user_id', $user->id)
                    ->orWhere('substitute_user_id', $user->id);
            });
        } elseif ($user->hasRole('User')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('substitute_user_id', $user->id);
            });
        } else {
            abort(403);
        }

        $leaves = $query->paginate(15);

        return view('leaves.index', compact('leaves'));
    }

    public function create()
    {
        $user = auth()->user();
        $substitutes = $this->getSubstitutes($user);

        return view('leaves.create', compact('substitutes'));
    }

    public function edit(Leave $leave)
    {
        $this->authorize('update', $leave);

        $user = auth()->user();
        $substitutes = $this->getSubstitutes($user);

        return view('leaves.edit', compact('leave', 'substitutes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $this->validateLeaveForm($request, $user);

        $managerId = $user->manager_id;

        $needsManagerApproval = $this->needsManagerApproval($managerId);

        if ($data['substitute']) {
            $status = 'pending';
        } else {
            $status = $needsManagerApproval ? 'manager_approved' : 'internal_approved';
        }

        $leave = Leave::create([
            'user_id'            => $user->id,
            'substitute_user_id' => $data['substitute']?->id,
            'leave_type'         => $data['leave_type'],
            'leave_unit'         => $data['leave_unit'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'start_time'         => $data['start_time'],
            'end_time'           => $data['end_time'],
            'reason'             => $data['reason'],
            'manager_id'         => $managerId,
            'status'             => $status,
        ]);

        if ($data['substitute']) {
            $this->notifyUser(
                $data['substitute']->id,
                'درخواست تایید جایگزین مرخصی',
                "{$user->name} شما را به‌عنوان جایگزین انتخاب کرده است. لطفاً درخواست را تایید یا رد کنید.",
                $leave->id
            );

            $this->notifyUser(
                $user->id,
                'مرخصی ثبت شد',
                'درخواست مرخصی ثبت شد و در انتظار تایید فرد جایگزین است.',
                $leave->id
            );

            return redirect()
                ->route('leaves')
                ->with('success', 'درخواست مرخصی ثبت شد و برای فرد جایگزین ارسال گردید.');
        }

        if ($needsManagerApproval) {
            $this->notifyUser(
                $managerId,
                'درخواست مرخصی جدید',
                "{$user->name} درخواست مرخصی ثبت کرده و منتظر تایید مدیر واحد است.",
                $leave->id
            );

            $this->notifyUser(
                $user->id,
                'مرخصی ثبت شد',
                'درخواست مرخصی شما ثبت شد و برای تایید مدیر واحد ارسال گردید.',
                $leave->id
            );

            return redirect()
                ->route('leaves')
                ->with('success', 'درخواست مرخصی ثبت شد و برای مدیر واحد ارسال گردید.');
        }

        $this->notifyInternalManagers(
            'درخواست مرخصی جدید',
            "{$user->name} درخواست مرخصی ثبت کرده و منتظر تایید مدیر داخلی است.",
            $leave->id
        );

        $this->notifyUser(
            $user->id,
            'مرخصی ثبت شد',
            'درخواست مرخصی شما ثبت شد و مستقیماً برای تایید مدیر داخلی ارسال گردید.',
            $leave->id
        );

        return redirect()
            ->route('leaves')
            ->with('success', 'درخواست مرخصی ثبت شد و برای مدیر داخلی ارسال گردید.');
    }

    public function update(Request $request, Leave $leave)
    {
        $this->authorize('update', $leave);

        $user = auth()->user();

        $data = $this->validateLeaveForm($request, $user, $leave->id);

        $managerId = $user->manager_id;

        $needsManagerApproval = $this->needsManagerApproval($managerId);

        if ($data['substitute']) {
            $status = 'pending';
        } else {
            $status = $needsManagerApproval ? 'manager_approved' : 'internal_approved';
        }

        $leave->update([
            'substitute_user_id' => $data['substitute']?->id,
            'leave_type'         => $data['leave_type'],
            'leave_unit'         => $data['leave_unit'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'start_time'         => $data['start_time'],
            'end_time'           => $data['end_time'],
            'reason'             => $data['reason'],
            'manager_id'         => $managerId,
            'status'             => $status,
        ]);

        return redirect()
            ->route('leaves')
            ->with('success', 'مرخصی با موفقیت ویرایش شد.');
    }

    public function destroy(int $leave)
    {
        $model = Leave::findOrFail($leave);

        $this->authorize('delete', $model);

        try {
            $model->forceDelete();

            return redirect()
                ->route('leaves')
                ->with('success', 'مرخصی با موفقیت حذف شد.');
        } catch (QueryException $e) {
            return back()->with('success', 'مرخصی با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            return back()->with('success', 'مرخصی با موفقیت حذف شد.');
        }
    }

    public function approve(Leave $leave)
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | مرحله 1: تایید جایگزین
        |--------------------------------------------------------------------------
        */
        if (
            $leave->status === 'pending' &&
            $leave->substitute_user_id &&
            (int) $leave->substitute_user_id === (int) $user->id
        ) {
            if ($this->needsManagerApproval($leave->manager_id)) {
                $leave->update([
                    'status' => 'manager_approved',
                ]);

                $this->notifyUser(
                    $leave->manager_id,
                    'مرخصی آماده تایید مدیر واحد',
                    "مرخصی {$leave->user?->name} توسط جایگزین تایید شد و منتظر تایید مدیر واحد است.",
                    $leave->id
                );

                $this->notifyUser(
                    $leave->user_id,
                    'مرخصی شما توسط جایگزین تایید شد',
                    'درخواست مرخصی شما توسط جایگزین تایید شد و منتظر تایید مدیر واحد است.',
                    $leave->id
                );
            } else {
                $leave->update([
                    'status' => 'internal_approved',
                ]);

                $this->notifyInternalManagers(
                    'مرخصی آماده تایید مدیر داخلی',
                    "مرخصی {$leave->user?->name} توسط جایگزین تایید شد و منتظر تایید مدیر داخلی است.",
                    $leave->id
                );

                $this->notifyUser(
                    $leave->user_id,
                    'مرخصی شما توسط جایگزین تایید شد',
                    'درخواست مرخصی شما توسط جایگزین تایید شد و مستقیماً وارد مرحله تایید مدیر داخلی شد.',
                    $leave->id
                );
            }

            return back()->with('success', 'مرخصی توسط جایگزین تأیید شد.');
        }

        /*
        |--------------------------------------------------------------------------
        | مرحله 2: تایید مدیر واحد
        |--------------------------------------------------------------------------
        */
        if (
            $user->hasRole('Manager') &&
            $leave->status === 'manager_approved' &&
            $this->needsManagerApproval($leave->manager_id) &&
            (int) $leave->manager_id === (int) $user->id
        ) {
            $leave->update([
                'status' => 'internal_approved',
            ]);

            $this->notifyInternalManagers(
                'مرخصی آماده تایید مدیر داخلی',
                "مرخصی {$leave->user?->name} تایید مدیر واحد را گرفته و منتظر تایید مدیر داخلی است.",
                $leave->id
            );

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط مدیر واحد تایید شد',
                'درخواست مرخصی شما توسط مدیر واحد تایید شد و منتظر تایید مدیر داخلی است.',
                $leave->id
            );

            return back()->with('success', 'مرخصی توسط مدیر واحد تأیید شد.');
        }

        /*
        |--------------------------------------------------------------------------
        | مرحله 3: تایید مدیر داخلی = تایید نهایی
        |--------------------------------------------------------------------------
        */
        if (
            ($user->hasRole('Admin') || $user->hasAnyRole(['internalManager', 'InternalManager'])) &&
            $leave->status === 'internal_approved'
        ) {
            $leave->update([
                'status'           => 'final_approved',
                'super_manager_id' => $user->id,
            ]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما تایید نهایی شد',
                'درخواست مرخصی شما توسط مدیر داخلی تایید نهایی شد.',
                $leave->id
            );

            return back()->with('success', 'مرخصی تایید نهایی شد.');
        }

        abort(403, 'شما مجوز تایید این مرخصی را ندارید.');
    }

    public function reject(Leave $leave)
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | رد توسط جایگزین
        |--------------------------------------------------------------------------
        */
        if (
            $leave->status === 'pending' &&
            $leave->substitute_user_id &&
            (int) $leave->substitute_user_id === (int) $user->id
        ) {
            $leave->update([
                'status' => 'manager_rejected',
            ]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط جایگزین رد شد',
                'فرد جایگزین درخواست مرخصی شما را رد کرد.',
                $leave->id
            );

            return back()->with('error', 'مرخصی توسط جایگزین رد شد.');
        }

        /*
        |--------------------------------------------------------------------------
        | رد توسط مدیر واحد
        |--------------------------------------------------------------------------
        */
        if (
            $user->hasRole('Manager') &&
            $leave->status === 'manager_approved' &&
            $this->needsManagerApproval($leave->manager_id) &&
            (int) $leave->manager_id === (int) $user->id
        ) {
            $leave->update([
                'status'     => 'internal_rejected',
                'manager_id' => $user->id,
            ]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط مدیر واحد رد شد',
                'درخواست مرخصی شما در مرحله مدیر واحد رد شد.',
                $leave->id
            );

            return back()->with('error', 'مرخصی توسط مدیر واحد رد شد.');
        }

        /*
        |--------------------------------------------------------------------------
        | رد توسط مدیر داخلی
        |--------------------------------------------------------------------------
        */
        if (
            ($user->hasRole('Admin') || $user->hasAnyRole(['internalManager', 'InternalManager'])) &&
            $leave->status === 'internal_approved'
        ) {
            $leave->update([
                'status'           => 'accounting_rejected',
                'super_manager_id' => $user->id,
            ]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط مدیر داخلی رد شد',
                'درخواست مرخصی شما در مرحله مدیر داخلی رد شد.',
                $leave->id
            );

            return back()->with('error', 'مرخصی توسط مدیر داخلی رد شد.');
        }

        abort(403, 'شما مجوز رد این مرخصی را ندارید.');
    }

    public function exportCsv(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to'   => 'required|string',
        ]);

        $from = $this->parseJalaliDate($request->from)->startOfDay();
        $to   = $this->parseJalaliDate($request->to)->endOfDay();

        $user = Auth::user();

        $query = Leave::query()->with(['user', 'manager']);

        if ($user->hasRole('Admin') || $user->hasAnyRole(['internalManager', 'InternalManager'])) {
            // همه را می‌بیند
        } elseif ($user->hasRole('Manager')) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhere('user_id', $user->id)
                    ->orWhere('substitute_user_id', $user->id);
            });
        } elseif ($user->hasRole('User')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('substitute_user_id', $user->id);
            });
        } else {
            abort(403);
        }

        $query->whereBetween('start_date', [$from, $to])->latest();

        $fromSafe = str_replace('/', '-', $request->from);
        $toSafe   = str_replace('/', '-', $request->to);

        $filename = "leaves_{$fromSafe}_to_{$toSafe}.csv";

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'ID',
                'کارمند',
                'نوع مرخصی',
                'نوع ثبت',
                'از تاریخ',
                'تا تاریخ',
                'از ساعت',
                'تا ساعت',
                'دلیل',
                'مدیر',
                'وضعیت',
                'تاریخ ثبت',
            ]);

            $query->chunk(500, function ($leaves) use ($out) {
                foreach ($leaves as $leave) {
                    fputcsv($out, [
                        $leave->id,
                        $leave->user?->name ?? '-',
                        $leave->leave_type ?? '-',
                        $leave->leave_unit ?? '-',
                        $leave->start_date ? Verta::instance($leave->start_date)->format('Y/m/d') : '-',
                        $leave->end_date ? Verta::instance($leave->end_date)->format('Y/m/d') : '-',
                        $leave->start_time ? substr($leave->start_time, 0, 5) : '-',
                        $leave->end_time ? substr($leave->end_time, 0, 5) : '-',
                        $leave->reason ?? '-',
                        $leave->manager?->name ?? '-',
                        $leave->status ?? '-',
                        $leave->created_at ? Verta::instance($leave->created_at)->format('Y/m/d H:i') : '-',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validateLeaveForm(Request $request, User $user, ?int $leaveId = null): array
    {
        $validated = $request->validate([
            'leave_type'         => 'required|in:اضطراری,استعلاجی,استحقاقی',
            'leave_unit'         => 'required|in:روزانه,ساعتی',
            'start_date'         => 'required|string',
            'end_date'           => 'required|string',
            'start_time'         => 'required|date_format:H:i',
            'end_time'           => 'required|date_format:H:i',
            'reason'             => 'nullable|string|max:2000',
            'substitute_user_id' => 'nullable|exists:users,id',
        ]);

        $substitute = $this->resolveSubstitute($validated['substitute_user_id'] ?? null, $user);

        $startDate = $this->parseJalaliDate($validated['start_date'])->startOfDay();
        $endDate   = $this->parseJalaliDate($validated['end_date'])->startOfDay();

        if ($validated['leave_unit'] === 'روزانه') {
            $minimumAllowedDate = now(config('app.timezone', 'Asia/Tehran'))
                ->startOfDay()
                ->addDays(2);

            if ($startDate->lt($minimumAllowedDate)) {
                throw ValidationException::withMessages([
                    'start_date' => 'برای مرخصی روزانه، انتخاب امروز و فردا مجاز نیست.',
                ]);
            }

            if ($endDate->lt($minimumAllowedDate)) {
                throw ValidationException::withMessages([
                    'end_date' => 'برای مرخصی روزانه، انتخاب امروز و فردا مجاز نیست.',
                ]);
            }
        }

        $startDateTime = $this->mergeDateAndTime($startDate, $validated['start_time']);
        $endDateTime   = $this->mergeDateAndTime($endDate, $validated['end_time']);

        if ($endDateTime->lte($startDateTime)) {
            throw ValidationException::withMessages([
                'end_time' => 'تاریخ/ساعت پایان باید بعد از تاریخ/ساعت شروع باشد.',
            ]);
        }

        return [
            'leave_type' => $validated['leave_type'],
            'leave_unit' => $validated['leave_unit'],
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'start_time' => $validated['start_time'],
            'end_time'   => $validated['end_time'],
            'reason'     => $validated['reason'] ?? null,
            'substitute' => $substitute,
        ];
    }

    private function resolveSubstitute(?int $substituteUserId, User $user): ?User
    {
        if (!$substituteUserId) {
            return null;
        }

        $substitute = User::findOrFail($substituteUserId);

        if ((int) $substitute->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'substitute_user_id' => 'نمی‌توانید خودتان را به‌عنوان جایگزین انتخاب کنید.',
            ]);
        }

        $sameUnit = (int) $substitute->manager_id === (int) $user->manager_id;

        if (!$sameUnit) {
            throw ValidationException::withMessages([
                'substitute_user_id' => 'فرد جایگزین باید از همان واحد شما انتخاب شود.',
            ]);
        }

        return $substitute;
    }

    private function parseJalaliDate(string $value): Carbon
    {
        try {
            return Carbon::instance(Verta::parse(trim($value))->datetime());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'start_date' => 'فرمت تاریخ نامعتبر است.',
            ]);
        }
    }

    private function mergeDateAndTime(Carbon $date, string $time): Carbon
    {
        [$hour, $minute] = explode(':', $time);

        return $date->copy()->setTime((int) $hour, (int) $minute, 0);
    }

    private function getSubstitutes(User $user)
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->when(
                $user->manager_id,
                fn ($q) => $q->where('manager_id', $user->manager_id),
                fn ($q) => $q->whereNull('manager_id')
            )
            ->orderBy('name')
            ->get();
    }

    private function needsManagerApproval(?int $managerId): bool
    {
        return !empty($managerId) && (int) $managerId !== 1;
    }

    private function getInternalManagerIds()
    {
        return User::role(['Admin', 'internalManager', 'InternalManager'])
            ->pluck('id')
            ->unique();
    }

    private function notifyInternalManagers(string $title, string $message, ?int $leaveId = null): void
    {
        $internalIds = $this->getInternalManagerIds();

        foreach ($internalIds as $id) {
            $this->notifyUser(
                $id,
                $title,
                $message,
                $leaveId
            );
        }
    }

    private function notifyUser(int $userId, string $title, string $message, ?int $leaveId = null): void
    {
        Notification::create([
            'user_id'  => $userId,
            'leave_id' => $leaveId,
            'title'    => $title,
            'message'  => $message,
            'seen'     => false,
        ]);
    }
}