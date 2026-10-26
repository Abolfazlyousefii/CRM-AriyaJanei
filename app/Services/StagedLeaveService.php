<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveWorkflowStep;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StagedLeaveService
{
    public function __construct(private readonly CompanyStructureService $company) {}

    public function assertRules(User $author, array $data): void
    {
        if ($data['leave_type'] === 'اضطراری' && $data['leave_unit'] !== 'ساعتی') {
            throw ValidationException::withMessages(['leave_unit' => 'مرخصی اضطراری فقط به‌صورت ساعتی ثبت می‌شود.']);
        }
        $start = $data['start_date']->copy()->setTimeFromTimeString($data['start_time']);
        if ($data['leave_unit'] !== 'ساعتی' && $start->copy()->startOfDay()->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['start_date' => 'تاریخ شروع نمی‌تواند قبل از امروز باشد.']);
        }
        if ($data['leave_unit'] === 'ساعتی' && ! $data['start_date']->isSameDay($data['end_date'])) {
            throw ValidationException::withMessages(['end_date' => 'مرخصی ساعتی باید در همان روز شروع پایان یابد.']);
        }
        if ($data['leave_type'] !== 'اضطراری' && $data['leave_unit'] === 'روزانه' && $start->lt(now()->addHours(48))) {
            throw ValidationException::withMessages(['start_date' => 'مرخصی روزانه عادی باید حداقل ۴۸ ساعت پیش از شروع ثبت شود.']);
        }
        if ($author->department_id && ! $data['substitute']) {
            throw ValidationException::withMessages(['substitute_user_id' => 'انتخاب جایگزین از همین واحد الزامی است.']);
        }
        if ($data['leave_type'] === 'اضطراری') {
            $end = $data['end_date']->copy()->setTimeFromTimeString($data['end_time']);
            $minutes = $start->diffInMinutes($end);
            $jalali = Verta::instance($start);
            $month = Verta::parse($jalali->format('Y/m').'/01')->startMonth();
            $from = Carbon::instance($month->datetime())->startOfDay();
            $to = Carbon::instance($month->copy()->addMonth()->datetime())->startOfDay();
            $count = Leave::withTrashed()->where('user_id', $author->id)->where('leave_type', 'اضطراری')->where('start_date', '>=', $from)->where('start_date', '<', $to)->count();
            if ($count >= 3) {
                throw ValidationException::withMessages(['leave_type' => 'سهمیه سه درخواست اضطراری این ماه شمسی تکمیل شده است؛ درخواست ردشده یا لغوشده نیز شمرده می‌شود.']);
            }
            if ($minutes > 240) {
                throw ValidationException::withMessages(['end_time' => 'هر نوبت مرخصی اضطراری حداکثر ۴ ساعت است؛ ساعت استفاده‌نشده ذخیره نمی‌شود.']);
            }
            // The controller holds the author's row lock, so simultaneous submissions cannot exceed this cap.
            $usedMinutes = Leave::withTrashed()->where('user_id', $author->id)->where('leave_type', 'اضطراری')
                ->whereDate('start_date', $start->toDateString())->get()->sum(function (Leave $previous) {
                    $from = $previous->start_date->copy()->setTimeFromTimeString($previous->start_time->format('H:i:s'));
                    $to = $previous->end_date->copy()->setTimeFromTimeString($previous->end_time->format('H:i:s'));

                    return max(0, $from->diffInMinutes($to));
                });
            if ($usedMinutes + $minutes > 240) {
                throw ValidationException::withMessages(['end_time' => 'مجموع درخواست‌های اضطراری یک روز نمی‌تواند بیشتر از ۴ ساعت باشد.']);
            }
        }
    }

    public function start(Leave $leave, User $author): void
    {
        [$internal,$executive] = $this->company->leaders();
        if ($author->is($executive)) {
            throw ValidationException::withMessages(['organization' => 'برای مرخصی مدیر اجرایی باید مسئول مستقل تعیین شود؛ تأیید درخواست خود مجاز نیست.']);
        }
        $route = [];
        if ($leave->substitute_user_id) {
            $route[] = ['substitute', (int) $leave->substitute_user_id];
        }
        if (! $author->is($internal) && ! $this->company->isUnitLeader($author)) {
            $unit = $author->department;
            $leader = $unit?->supervisor_id ?? $unit?->manager_id;
            if ($leader && ! in_array((int) $leader, [$internal->id, $executive->id], true)) {
                $route[] = ['unit', (int) $leader];
            } elseif (! $unit) {
                throw ValidationException::withMessages(['organization' => 'واحد و مسیر گزارش‌دهی شما هنوز تنظیم نشده است.']);
            }
        }
        if (! $author->is($internal)) {
            $route[] = ['internal', $internal->id];
        }
        $route[] = ['executive', $executive->id];
        foreach ($route as $position => [$stage,$id]) {
            $person = User::find($id);
            if (! $person || ! $person->isActive() || $person->isBlocked() || $person->is($author)) {
                throw ValidationException::withMessages(['organization' => 'یکی از مسئولان مسیر تأیید معتبر یا فعال نیست.']);
            }
            $leave->steps()->create(['position' => $position + 1, 'stage' => $stage, 'approver_id' => $id, 'status' => $position === 0 ? 'pending' : 'waiting']);
        }
        $leave->update(['approval_route' => 'staged', 'status' => 'pending', 'assigned_approver_id' => $route[0][1], 'manager_id' => $author->manager_id]);
        $leave->events()->create(['actor_id' => $author->id, 'action' => 'submitted', 'method' => 'account']);
        $this->notifyCurrent($leave);
    }

    public function current(Leave $leave): ?LeaveWorkflowStep
    {
        return $leave->steps()->where('status', 'pending')->orderBy('position')->first();
    }

    public function canDecide(User $actor, Leave $leave): bool
    {
        return $actor->isActive() && ! $actor->isBlocked() && (int) $leave->user_id !== (int) $actor->id
            && $leave->status === 'pending' && $leave->approval_route === 'staged'
            && (int) $this->current($leave)?->approver_id === (int) $actor->id;
    }

    public function decide(Leave $leave, User $actor, bool $approve, ?string $reason = null): void
    {
        DB::transaction(function () use ($leave, $actor, $approve, $reason) {
            $locked = Leave::lockForUpdate()->findOrFail($leave->id);
            $actor = User::findOrFail($actor->id);
            abort_unless($this->canDecide($actor, $locked), 403);
            $this->completeLocked($locked, $actor, $actor, $approve, 'account', $reason);
        });
    }

    // Caller holds the leave lock and has verified the request-specific SMS challenge.
    public function completeLocked(Leave $leave, User $actor, User $principal, bool $approve, string $method, ?string $reason = null): void
    {
        $step = $leave->steps()->where('status', 'pending')->orderBy('position')->lockForUpdate()->first();
        abort_unless($step && (int) $step->approver_id === (int) $principal->id && $leave->status === 'pending', 409);
        $step->update(['status' => $approve ? 'approved' : 'rejected', 'decided_at' => now()]);
        $leave->events()->create(['step_id' => $step->id, 'actor_id' => $actor->id, 'principal_id' => $principal->id, 'action' => $approve ? 'approved' : 'rejected', 'method' => $method, 'reason' => $reason]);
        if (! $approve) {
            $leave->steps()->where('status', 'waiting')->update(['status' => 'cancelled']);
            $leave->update(['status' => 'final_rejected', 'assigned_approver_id' => null, 'super_manager_id' => $principal->id]);
        } else {
            $next = $leave->steps()->where('status', 'waiting')->orderBy('position')->first();
            if ($next) {
                $next->update(['status' => 'pending']);
                $leave->update(['assigned_approver_id' => $next->approver_id]);
                $this->notifyCurrent($leave);
            } else {
                $leave->update(['status' => 'final_approved', 'assigned_approver_id' => null, 'super_manager_id' => $principal->id]);
            }
        }
        Notification::create(['user_id' => $leave->user_id, 'leave_id' => $leave->id, 'title' => $approve ? 'تأیید یک مرحله مرخصی' : 'رد درخواست مرخصی',
            'message' => $principal->name.($approve ? ' این مرحله را تأیید کرد.' : ' درخواست را رد کرد.').($leave->status === 'final_approved' ? ' مرخصی شما نهایی شد.' : ''), 'seen' => false]);
    }

    public function notifyCurrent(Leave $leave): void
    {
        Notification::create(['user_id' => $leave->assigned_approver_id, 'leave_id' => $leave->id, 'title' => 'مرخصی در انتظار اقدام شما',
            'message' => 'درخواست مرخصی شماره '.$leave->id.' برای بررسی به شما رسیده است.', 'seen' => false]);
    }
}
