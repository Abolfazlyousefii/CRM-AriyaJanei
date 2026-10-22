<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    public const PENDING_STATUSES = ['pending', 'manager_approved', 'internal_approved', 'accounting_approved', 'pending_manager', 'pending_internal', 'pending_account'];

    public function approvers()
    {
        return User::query()->where('is_active', true)
            ->whereIn('id', DB::table('leave_approvers')->select('user_id'))
            ->orderBy('name')->get();
    }

    public function isApprover(User $user): bool
    {
        return $user->is_active && ! $user->isBlocked()
            && DB::table('leave_approvers')->where('user_id', $user->id)->exists();
    }

    public function canDecide(User $user, Leave $leave): bool
    {
        return $this->isApprover($user) && in_array($leave->status, self::PENDING_STATUSES, true);
    }

    public function notifyApprovers(Leave $leave): void
    {
        foreach ($this->approvers() as $approver) {
            Notification::create([
                'user_id' => $approver->id, 'leave_id' => $leave->id,
                'title' => 'درخواست مرخصی برای بررسی',
                'message' => "درخواست مرخصی {$leave->user?->name} در انتظار تأیید یا رد شماست.",
                'seen' => false,
            ]);
        }
    }
}
