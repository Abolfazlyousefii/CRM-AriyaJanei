<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Notification;
use App\Models\User;
use Hekmatinasser\Verta\Verta;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('Admin')) {
            $leaves = Leave::with(['user', 'substituteUser'])->latest()->paginate(15);
        } elseif ($user->hasRole('Manager')) {
            $leaves = Leave::with(['user', 'substituteUser'])
                ->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id)
                        ->orWhere('user_id', $user->id)
                        ->orWhere('substitute_user_id', $user->id);
                })
                ->latest()
                ->paginate(15);
        } elseif ($user->hasRole('User')) {
            $leaves = Leave::with(['user', 'substituteUser'])
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('substitute_user_id', $user->id);
                })
                ->latest()
                ->paginate(15);
        } else {
            $leaves = Leave::with(['user', 'substituteUser'])->latest()->paginate(15);
        }

        return view('leaves.index', compact('leaves'));
    }

    public function destroy(int $leave)
    {
        $model = Leave::findOrFail($leave);
        $this->authorize('delete', $model);

        try {
            $model->forceDelete();

            return redirect()
                ->route('leaves.index')
                ->with('success', 'مرخصی با موفقیت حذف شد.');
        } catch (QueryException $e) {
            return back()->with('success', 'مرخصی با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            return back()->with('success', 'مرخصی با موفقیت حذف شد.');
        }
    }

    public function create()
    {
        $user = auth()->user();

        $substitutes = User::query()
            ->where('id', '!=', $user->id)
            ->when(
                $user->manager_id,
                fn ($q) => $q->where('manager_id', $user->manager_id),
                fn ($q) => $q->whereNull('manager_id')
            )
            ->orderBy('name')
            ->get();

        return view('leaves.create', compact('substitutes'));
    }

    public function edit(Leave $leave)
    {
        $this->authorize('update', $leave);

        return view('leaves.edit', compact('leave'));
    }

    public function update(Request $request, Leave $leave)
    {
        $this->authorize('update', $leave);

        $data = $request->validate([
            'type' => 'required|in:استحقاقی,بدون حقوق,ساعتی',
            'start_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
        ]);

        $leave->update($data);

        return redirect()->route('leaves')->with('success', 'مرخصی به‌روزرسانی شد.');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isManagerRequester = $user->hasRole('Manager');

        $request->validate([
            'leave_type' => 'required|string|max:255',
            'start_date' => 'required|string',
            'end_date' => 'required|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string',
            'substitute_user_id' => 'nullable|exists:users,id',
        ]);

        $substitute = null;
        if ($request->filled('substitute_user_id')) {
            $substitute = User::findOrFail($request->substitute_user_id);

            if ((int) $substitute->id === (int) $user->id) {
                return back()->withErrors([
                    'substitute_user_id' => 'نمی‌توانید خودتان را به‌عنوان جایگزین انتخاب کنید.',
                ])->withInput();
            }

            $sameUnit = $substitute->manager_id === $user->manager_id;
            if (!$sameUnit) {
                return back()->withErrors([
                    'substitute_user_id' => 'فرد جایگزین باید از همان واحد شما انتخاب شود.',
                ])->withInput();
            }
        }

        $startDate = Verta::parse($request->start_date)->datetime();
        $endDate = Verta::parse($request->end_date)->datetime();

        $leave = Leave::create([
            'user_id' => $user->id,
            'substitute_user_id' => $substitute?->id,
            'leave_type' => $request->leave_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
            'manager_id' => $isManagerRequester ? $user->id : $user->manager_id,
            'status' => $substitute
                ? 'pending'
                : ($isManagerRequester ? 'internal_approved' : 'manager_approved'),
        ]);

        if ($substitute) {
            $this->notifyUser(
                $substitute->id,
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

            return redirect()->route('leaves')->with('success', 'درخواست مرخصی ثبت شد و برای فرد جایگزین ارسال گردید.');
        }

        if (!$isManagerRequester && $leave->manager_id) {
            $this->notifyUser(
                $leave->manager_id,
                'درخواست مرخصی بدون جایگزین',
                "{$user->name} یک درخواست مرخصی بدون جایگزین ثبت کرده است.",
                $leave->id
            );
        }

        if ($isManagerRequester) {
            $internalIds = User::role(['Admin', 'internalManager', 'InternalManager'])->pluck('id')->unique();
            foreach ($internalIds as $id) {
                $this->notifyUser(
                    $id,
                    'درخواست مرخصی مدیر واحد',
                    "{$user->name} یک درخواست مرخصی ثبت کرده و منتظر تایید مدیر داخلی است.",
                    $leave->id
                );
            }
        }

        $this->notifyUser(
            $user->id,
            'مرخصی ثبت شد',
            $isManagerRequester
                ? 'درخواست مرخصی شما ثبت شد و مستقیماً برای مدیر داخلی ارسال گردید.'
                : 'درخواست مرخصی بدون جایگزین ثبت شد و مستقیماً برای مدیر واحد ارسال شد.',
            $leave->id
        );

        return redirect()->route('leaves')->with(
            'success',
            $isManagerRequester
                ? 'درخواست مرخصی ثبت شد و برای مدیر داخلی ارسال گردید.'
                : 'درخواست مرخصی بدون جایگزین ثبت شد و برای مدیر واحد ارسال گردید.'
        );
    }

    public function approve(Leave $leave)
    {
        $user = auth()->user();

        if ($leave->status === 'pending' && (int) $leave->substitute_user_id === (int) $user->id) {
            $isManagerLeave = $leave->user && $leave->user->hasRole('Manager');
            $leave->update(['status' => $isManagerLeave ? 'internal_approved' : 'manager_approved']);

            if (!$isManagerLeave && $leave->manager_id) {
                $this->notifyUser(
                    $leave->manager_id,
                    'تایید مرخصی توسط جایگزین',
                    "مرخصی {$leave->user->name} توسط جایگزین تایید شد و منتظر تایید مدیر واحد است.",
                    $leave->id
                );
            }

            if ($isManagerLeave) {
                $internalIds = User::role(['Admin', 'internalManager', 'InternalManager'])->pluck('id')->unique();
                foreach ($internalIds as $id) {
                    $this->notifyUser(
                        $id,
                        'مرخصی مدیر واحد آماده تایید داخلی',
                        "مرخصی {$leave->user->name} توسط جایگزین تایید شد و منتظر تایید مدیر داخلی است.",
                        $leave->id
                    );
                }
            }

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط جایگزین تایید شد',
                $isManagerLeave
                    ? 'درخواست مرخصی شما مستقیماً وارد مرحله تایید مدیر داخلی شد.'
                    : 'درخواست مرخصی شما وارد مرحله تایید مدیر واحد شد.',
                $leave->id
            );
        } elseif ($user->hasRole('Manager') && $leave->status === 'manager_approved' && (int) $leave->manager_id === (int) $user->id) {
            $leave->update(['status' => 'internal_approved', 'manager_id' => $user->id]);

            $internalIds = User::role(['Admin', 'internalManager', 'InternalManager'])->pluck('id')->unique();
            foreach ($internalIds as $id) {
                $this->notifyUser(
                    $id,
                    'مرخصی آماده تایید مدیر داخلی',
                    "مرخصی {$leave->user->name} تایید مدیر واحد را گرفته و منتظر تصمیم مدیر داخلی است.",
                    $leave->id
                );
            }
        } elseif (($user->hasRole('Admin') || $user->hasAnyRole(['internalManager', 'InternalManager'])) && $leave->status === 'internal_approved') {
            $leave->update(['status' => 'final_approved', 'super_manager_id' => $user->id]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما تایید نهایی شد',
                'درخواست مرخصی شما پس از تایید جایگزین و مدیر واحد، توسط مدیر داخلی تایید نهایی شد.',
                $leave->id
            );
        } else {
            abort(403, 'شما مجوز تایید این مرخصی را ندارید.');
        }

        return back()->with('success', 'مرخصی تأیید شد.');
    }

    public function reject(Leave $leave)
    {
        $user = auth()->user();

        if ($leave->status === 'pending' && (int) $leave->substitute_user_id === (int) $user->id) {
            $leave->update(['status' => 'manager_rejected']);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط جایگزین رد شد',
                'فرد جایگزین درخواست مرخصی شما را رد کرد.',
                $leave->id
            );
        } elseif ($user->hasRole('Manager') && $leave->status === 'manager_approved' && (int) $leave->manager_id === (int) $user->id) {
            $leave->update(['status' => 'internal_rejected', 'manager_id' => $user->id]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط مدیر واحد رد شد',
                'درخواست مرخصی شما در مرحله مدیر واحد رد شد.',
                $leave->id
            );
        } elseif (($user->hasRole('Admin') || $user->hasAnyRole(['internalManager', 'InternalManager'])) && $leave->status === 'internal_approved') {
            $leave->update(['status' => 'accounting_rejected', 'super_manager_id' => $user->id]);

            $this->notifyUser(
                $leave->user_id,
                'مرخصی شما توسط مدیر داخلی رد شد',
                'درخواست مرخصی شما در مرحله تایید مدیر داخلی رد شد.',
                $leave->id
            );
        } else {
            abort(403, 'شما مجوز رد این مرخصی را ندارید.');
        }

        return back()->with('error', 'مرخصی رد شد.');
    }

    private function notifyUser(int $userId, string $title, string $message, ?int $leaveId = null): void
    {
        Notification::create([
            'user_id' => $userId,
            'leave_id' => $leaveId,
            'title' => $title,
            'message' => $message,
            'seen' => false,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $from = Verta::parse($request->from)->startDay()->datetime();
        $to = Verta::parse($request->to)->endDay()->datetime();

        $user = Auth::user();
        $query = Leave::query()->with(['user', 'manager']);

        if ($user->hasRole('Admin')) {
        } elseif ($user->hasRole('Manager')) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhere('user_id', $user->id)
                    ->orWhere('substitute_user_id', $user->id);
            });
        } elseif ($user->hasRole('User')) {
            $query->where('user_id', $user->id);
        } else {
            abort(403);
        }

        $query->whereBetween('start_date', [$from, $to])->latest();

        $fromSafe = str_replace('/', '-', $request->from);
        $toSafe = str_replace('/', '-', $request->to);

        $filename = "leaves_{$fromSafe}_to_{$toSafe}.csv";

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'ID',
                'کارمند',
                'نوع مرخصی',
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
                        $leave->start_date ? Verta::instance($leave->start_date)->format('Y/m/d') : '-',
                        $leave->end_date ? Verta::instance($leave->end_date)->format('Y/m/d') : '-',
                        $leave->start_time ?? '-',
                        $leave->end_time ?? '-',
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
}
