<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Notification;
use App\Models\User;
use App\Services\LeaveApprovalService;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveApprovalService $approval)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        $query = Leave::with(['user', 'substituteUser', 'manager'])->latest();

        $this->approval->visibleTo($query, $user);

        $leaves = $query->with('assignedApprover')->paginate(15);
        $leaveAssignees = $user->hasRole('Admin') ? User::query()->where('is_active', true)
            ->where(function ($active) {
                $active->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
            })->orderBy('name')->get(['id', 'name']) : collect();

        return view('Leaves.index', compact('leaves', 'leaveAssignees'));
    }

    public function create()
    {
        $user = auth()->user();
        $substitutes = $this->getSubstitutes($user);
        $leaveApprover = $this->approval->responsiblePerson($user);

        return view('Leaves.create', compact('substitutes', 'leaveApprover'));
    }

    public function edit(Leave $leave)
    {
        $this->authorize('update', $leave);

        $user = auth()->user();
        $substitutes = $this->getSubstitutes($user);

        return view('Leaves.edit', compact('leave', 'substitutes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if(config('organization_flow.enabled',false)) {
            DB::transaction(function()use($request,$user){
                $user=User::lockForUpdate()->findOrFail($user->id);
                abort_unless($user->isActive() && !$user->isBlocked(),403);
                $data=$this->validateLeaveForm($request,$user);
                $flow=app(\App\Services\StagedLeaveService::class);$flow->assertRules($user,$data);
                $leave=Leave::create(collect($data)->except('substitute')->all()+[
                    'user_id'=>$user->id,'substitute_user_id'=>$data['substitute']?->id,'status'=>'pending',
                ]);
                $flow->start($leave,$user);
            });
            return redirect()->route('leaves')->with('success','مرخصی ثبت شد؛ نتیجه هر مرحله در همین صفحه نمایش داده می‌شود.');
        }
        $data = $this->validateLeaveForm($request, $user);
        $leave = Leave::create(array_merge([
            'user_id' => $user->id,
            'substitute_user_id' => $data['substitute']?->id,
            'leave_type' => $data['leave_type'], 'leave_unit' => $data['leave_unit'],
            'start_date' => $data['start_date'], 'end_date' => $data['end_date'],
            'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
            'reason' => $data['reason'], 'manager_id' => $user->manager_id,
            'status' => 'pending',
        ], $this->approval->routingForNewRequest($user)));
        $this->approval->notifyApprovers($leave);

        return redirect()->route('leaves')->with('success', 'درخواست مرخصی ثبت شد و در انتظار بررسی تأییدکنندگان است.');
    }

    public function update(Request $request, Leave $leave)
    {
        $this->authorize('update', $leave);

        $user = auth()->user();

        $data = $this->validateLeaveForm($request, $user, $leave->id);

        $leave->update([
            'substitute_user_id' => $data['substitute']?->id,
            'leave_type' => $data['leave_type'],
            'leave_unit' => $data['leave_unit'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'reason' => $data['reason'],
            // Keep the original assignment when the organizational chart changes.
            'manager_id' => $leave->manager_id,
            'status' => 'pending',
            'super_manager_id' => null,
        ]);

        $this->approval->notifyApprovers($leave);

        return redirect()
            ->route('leaves')
            ->with('success', 'مرخصی با موفقیت ویرایش شد.');
    }

    public function destroy(int $leave)
    {
        $model = Leave::findOrFail($leave);

        $this->authorize('delete', $model);

        try {
            if($model->approval_route==='staged') {
                DB::transaction(function()use($model){
                    $locked=Leave::lockForUpdate()->findOrFail($model->id);$this->authorize('delete',$locked);
                    $locked->events()->create(['actor_id'=>auth()->id(),'action'=>'cancelled','method'=>'account']);
                    $locked->steps()->whereIn('status',['pending','waiting'])->update(['status'=>'cancelled']);
                    $locked->update(['status'=>'cancelled','assigned_approver_id'=>null]);$locked->delete();
                });
            }elseif(config('organization_flow.enabled',false))$model->delete();
            else $model->forceDelete();

            return redirect()
                ->route('leaves')
                ->with('success', 'مرخصی با موفقیت حذف شد.');
        } catch (QueryException $e) {
            if($model->approval_route==='staged')throw $e;
            return back()->with('success', 'مرخصی با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            if($model->approval_route==='staged')throw $e;
            return back()->with('success', 'مرخصی با موفقیت حذف شد.');
        }
    }

    public function approve(Leave $leave)
    {
        return $this->decide($leave, 'final_approved', 'مرخصی تأیید شد.');
    }

    public function reject(Leave $leave)
    {
        return $this->decide($leave, 'final_rejected', 'مرخصی رد شد.');
    }

    private function decide(Leave $leave, string $status, string $message)
    {
        if($leave->approval_route==='staged') {
            app(\App\Services\StagedLeaveService::class)->decide($leave,auth()->user(),$status==='final_approved',request()->validate(['reason'=>'nullable|string|max:2000'])['reason']??null);
            return back()->with('success','نتیجه این مرحله ثبت شد.');
        }
        DB::transaction(function () use ($leave, $status, $message) {
            $lockedLeave = Leave::query()->lockForUpdate()->findOrFail($leave->id);
            $this->authorize('approve', $lockedLeave);
            $lockedLeave->update([
                'status' => $status,
                'super_manager_id' => auth()->id(),
            ]);
            $this->notifyUser($lockedLeave->user_id, $message, $message, $lockedLeave->id);
        });

        return back()->with('success', $message);
    }

    public function exportCsv(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $from = $this->parseJalaliDate($request->from)->startOfDay();
        $to = $this->parseJalaliDate($request->to)->endOfDay();

        $user = Auth::user();

        $query = Leave::query()->with(['user', 'manager']);

        $this->approval->visibleTo($query, $user);

        $query->whereBetween('start_date', [$from, $to])->latest();

        $fromSafe = str_replace('/', '-', $request->from);
        $toSafe = str_replace('/', '-', $request->to);

        $filename = "leaves_{$fromSafe}_to_{$toSafe}.csv";

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

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

    public function printMonthly(Request $request)
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}\/\d{2}$/'],
        ], [
            'month.required' => 'انتخاب ماه الزامی است.',
            'month.regex' => 'فرمت ماه باید مانند 1405/02 باشد.',
        ]);

        $user = Auth::user();

        if (! $user->hasRole('Accountant')) {
            abort(403);
        }

        [$jy, $jm] = array_map('intval', explode('/', $request->month));

        $monthStart = Carbon::instance(
            Verta::parse(sprintf('%04d/%02d/01', $jy, $jm))->startMonth()->datetime()
        )->startOfDay();

        $monthEnd = Carbon::instance(
            Verta::parse(sprintf('%04d/%02d/01', $jy, $jm))->endMonth()->datetime()
        )->endOfDay();

        $leaves = Leave::with(['user', 'substituteUser', 'manager'])
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->latest()
            ->get();

        return view('Leaves.print-monthly', [
            'leaves' => $leaves,
            'month' => $request->month,
        ]);
    }

    private function validateLeaveForm(Request $request, User $user, ?int $leaveId = null): array
    {
        $validated = $request->validate([
            'leave_type' => 'required|in:اضطراری,استعلاجی,استحقاقی,ماموریت',
            'leave_unit' => 'required|in:روزانه,ساعتی',
            'start_date' => 'required|string',
            'end_date' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:2000',
            'substitute_user_id' => 'nullable|exists:users,id',
        ]);

        $substitute = $this->resolveSubstitute($validated['substitute_user_id'] ?? null, $user);

        $startDate = $this->parseJalaliDate($validated['start_date'])->startOfDay();
        $endDate = $this->parseJalaliDate($validated['end_date'])->startOfDay();

        $startDateTime = $this->mergeDateAndTime($startDate, $validated['start_time']);
        $endDateTime = $this->mergeDateAndTime($endDate, $validated['end_time']);

        if ($endDateTime->lte($startDateTime)) {
            throw ValidationException::withMessages([
                'end_time' => 'تاریخ/ساعت پایان باید بعد از تاریخ/ساعت شروع باشد.',
            ]);
        }

        return [
            'leave_type' => $validated['leave_type'],
            'leave_unit' => $validated['leave_unit'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'] ?? null,
            'substitute' => $substitute,
        ];
    }

    private function resolveSubstitute(?int $substituteUserId, User $user): ?User
    {
        if (! $substituteUserId) {
            return null;
        }

        $substitute = User::findOrFail($substituteUserId);

        if ((int) $substitute->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'substitute_user_id' => 'نمی‌توانید خودتان را به‌عنوان جایگزین انتخاب کنید.',
            ]);
        }

        $sameUnit = config('organization_flow.enabled',false)
            ? ($user->department_id && (int)$substitute->department_id===(int)$user->department_id && $substitute->isActive() && !$substitute->isBlocked())
            : (int) $substitute->manager_id === (int) $user->manager_id;

        if (! $sameUnit) {
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
        if(config('organization_flow.enabled',false))return User::where('id','!=',$user->id)->where('is_active',true)
            ->whereNotNull('department_id')->where('department_id',$user->department_id)
            ->where(fn($q)=>$q->whereNull('blocked_until')->orWhere('blocked_until','<=',now()))->orderBy('name')->get();
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

    public function requestExecutiveCode(Leave $leave)
    {
        app(\App\Services\ExecutiveLeaveCodeService::class)->request($leave,auth()->user());
        return back()->with('success','کد تأیید فقط به شماره مدیر اجرایی ارسال شد؛ اعتبار آن پنج دقیقه است.');
    }
    public function verifyExecutiveCode(Request $request,Leave $leave)
    {
        try {
            $data=$request->validate(['code'=>['required','regex:/^[0-9]{4}$/']]);
            app(\App\Services\ExecutiveLeaveCodeService::class)->verify($leave,auth()->user(),$data['code']);
        }catch(ValidationException $exception){
            if($request->expectsJson())throw $exception;
            return back()->withErrors($exception->errors());
        }
        return back()->with('success','تأیید نهایی به نام مدیر اجرایی با ثبت سابقه ورود کد توسط مدیر داخلی انجام شد.');
    }
}
