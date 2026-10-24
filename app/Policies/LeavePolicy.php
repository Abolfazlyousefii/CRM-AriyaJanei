<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveApprovalService;

class LeavePolicy
{
    public function update(User $user, Leave $leave): bool
    {
        if($leave->approval_route==='staged' || config('organization_flow.enabled',false))return false;
        return (int) $user->id === (int) $leave->user_id
            && in_array($leave->status, LeaveApprovalService::PENDING_STATUSES, true);
    }

    public function delete(User $user, Leave $leave): bool
    {
        if($leave->approval_route==='staged' || config('organization_flow.enabled',false))return $user->isActive() && !$user->isBlocked() && (int)$user->id===(int)$leave->user_id && in_array($leave->status,LeaveApprovalService::PENDING_STATUSES,true);
        return $this->update($user, $leave);

    }

    public function approve(User $user, Leave $leave): bool
    {
        return app(LeaveApprovalService::class)->canDecide($user, $leave);
    }
}
