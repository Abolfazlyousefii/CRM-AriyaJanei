<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Notification;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    public function __construct(private readonly OrganizationHierarchyService $organization) {}

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
        if($leave->approval_route==='staged')return app(StagedLeaveService::class)->canDecide($user,$leave);
        if (! $user->isActive() || $user->isBlocked() || (int) $leave->user_id === (int) $user->id) {
            return false;
        }
        if ($leave->approval_route === 'organization') {
            return (int) $leave->assigned_approver_id === (int) $user->id
                && in_array($leave->status, self::PENDING_STATUSES, true);
        }
        return $this->isApprover($user) && in_array($leave->status, self::PENDING_STATUSES, true);
    }

    public function responsiblePerson(User $author): ?User
    {
        try {
            return $this->organization->activeManagerChain($author)->first();
        } catch (DomainException) {
            // Imported cycles must not assign a guessed recipient or block legacy requests.
            return null;
        }
    }

    public function routingForNewRequest(User $author): array
    {
        $responsible = $this->responsiblePerson($author);
        return $responsible ? [
            'approval_route' => 'organization',
            'assigned_approver_id' => $responsible->id,
            'manager_id' => $responsible->id,
        ] : ['manager_id' => $author->manager_id];
    }

    public function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Admin', 'Accountant'])) {
            return $query;
        }
        $team = ($user->hasAnyRole(['Manager', 'InternalManager', 'internalManager', 'Owner', 'Supervisor']) || app(CompanyStructureService::class)->isLeader($user))
            ? $this->organization->subordinateIds($user) : [];
        return $query->where(function (Builder $visible) use ($user, $team): void {
            $visible->where('user_id', $user->id)->orWhere('substitute_user_id', $user->id)
                ->orWhere('assigned_approver_id', $user->id);
            if(config('organization_flow.enabled',false))$visible->orWhereHas('steps',fn($q)=>$q->where('approver_id',$user->id));
            if ($team !== []) {
                $visible->orWhereIn('user_id', $team);
            }
            $visible->orWhere(function (Builder $legacy) use ($user): void {
                $legacy->whereNull('approval_route');
                if (! $this->isApprover($user)) {
                    $legacy->where('manager_id', $user->id);
                }
            });
        });
    }

    public function notifyApprovers(Leave $leave): void
    {
        $recipients = $leave->approval_route === 'organization'
            ? collect([$leave->assignedApprover])->filter(fn ($person) => $person && $person->isActive() && ! $person->isBlocked())
            : $this->approvers()->reject(fn ($person) => (int) $person->id === (int) $leave->user_id);
        foreach ($recipients as $approver) {
            Notification::create([
                'user_id' => $approver->id, 'leave_id' => $leave->id,
                'title' => 'درخواست مرخصی برای بررسی',
                'message' => "درخواست مرخصی {$leave->user?->name} در انتظار تأیید یا رد شماست.",
                'seen' => false,
            ]);
        }
    }

    public function reassign(Leave $leave, User $actor, User $replacement, string $reason): void
    {
        abort_unless($actor->isActive() && ! $actor->isBlocked() && $actor->hasRole('Admin'), 403);
        DB::transaction(function () use ($leave, $actor, $replacement, $reason): void {
            $locked = Leave::query()->lockForUpdate()->findOrFail($leave->id);
            abort_unless($locked->approval_route === 'organization'
                && in_array($locked->status, self::PENDING_STATUSES, true), 409);
            $replacement = User::query()->findOrFail($replacement->id);
            abort_unless($replacement->isActive() && ! $replacement->isBlocked()
                && (int) $replacement->id !== (int) $locked->user_id, 422);
            $old = $locked->assigned_approver_id;
            $locked->update(['assigned_approver_id' => $replacement->id, 'manager_id' => $replacement->id]);
            activity()->causedBy($actor)->performedOn($locked)->withProperties([
                'previous_reviewer_id' => $old, 'reviewer_id' => $replacement->id, 'reason' => $reason,
            ])->log('تغییر مسئول بررسی مرخصی');
            $this->notifyApprovers($locked);
        });
    }
}
