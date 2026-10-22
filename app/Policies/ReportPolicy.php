<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Services\ReportAccessService;

class ReportPolicy
{
    public function __construct(private readonly ReportAccessService $access) {}

    private function active(User $user): bool
    {
        return $user->isActive() && ! $user->isBlocked();
    }

    public function view(User $user, Report $report): bool
    {
        if (! $this->active($user)) return false;
        if ((int) $user->id === (int) $report->user_id) return true;
        if (! in_array($report->status, [Report::STATUS_SUBMITTED, Report::STATUS_READ], true)) return false;
        // The snapshotted reviewer retains access after organizational moves.
        return $this->access->supervises($user, (int) $report->user_id)
            || (bool) $report->reviewWorkflow?->steps()->where('reviewer_id', $user->id)->exists();
    }

    public function update(User $user, Report $report): bool
    {
        if (! $this->active($user)) return false;
        $workflow = $report->reviewWorkflow;
        if ($workflow) {
            return (int) $user->id === (int) $report->user_id && $workflow->status === 'needs_revision';
        }
        return (int) $user->id === (int) $report->user_id || $user->hasRole('Admin');
    }

    public function delete(User $user, Report $report): bool
    {
        return $this->update($user, $report) && ! $report->reviewWorkflow;
    }

    public function feedback(User $user, Report $report): bool
    {
        return $this->active($user) && (int) $user->id !== (int) $report->user_id
            && in_array($report->status, [Report::STATUS_SUBMITTED, Report::STATUS_READ], true)
            && ($this->access->supervises($user, (int) $report->user_id)
                || (int) $report->reviewWorkflow?->current_reviewer_id === (int) $user->id);
    }

    public function submit(User $user, Report $report): bool
    {
        return $this->active($user) && (int) $user->id === (int) $report->user_id
            && $report->status === Report::STATUS_DRAFT && ! $report->reviewWorkflow;
    }
}
