<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Report;
use App\Models\ReportReviewWorkflow;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportReviewWorkflowService
{
    public function __construct(private readonly OrganizationHierarchyService $organization) {}

    /**
     * Start a review for a NEW submitted report. Historical reports remain
     * legacy and are never migrated into this workflow automatically.
     */
    public function start(Report $report): ?ReportReviewWorkflow
    {
        if (! config('report_review.enabled', false)) {
            return null;
        }

        $existing = ReportReviewWorkflow::query()->where('report_id', $report->id)->first();
        if ($existing) {
            return $existing;
        }

        if (! in_array($report->status, [Report::STATUS_SUBMITTED, Report::STATUS_READ], true) || ! $report->user) {
            return null;
        }

        try {
            $chain = $this->organization->managerChain($report->user);
        } catch (DomainException $exception) {
            // The imported organization can contain legacy self-references.
            // Keep the old report flow rather than block a staff submission.
            Log::warning('Report review path has a legacy cycle', ['report_id' => $report->id]);
            return null;
        }

        $reviewers = collect();
        $maxSteps = (int) config('report_review.max_steps', 3);
        foreach ($chain as $manager) {
            if (! $manager->isActive() || $manager->isBlocked()) {
                continue;
            }

            $reviewers->push($manager);
            if ($manager->org_position === 'internal_manager' || $reviewers->count() >= $maxSteps) {
                break;
            }
        }

        if ($reviewers->isEmpty()) {
            // No organizational assignment yet. Do not invent a reviewer.
            return null;
        }

        return DB::transaction(function () use ($report, $reviewers): ReportReviewWorkflow {
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $existing = ReportReviewWorkflow::query()->where('report_id', $locked->id)->first();

            if ($existing) {
                return $existing;
            }

            $workflow = ReportReviewWorkflow::create([
                'report_id' => $locked->id,
                'status' => 'pending',
                'current_reviewer_id' => $reviewers->first()->id,
                'current_sequence' => 1,
            ]);

            foreach ($reviewers as $index => $manager) {
                $workflow->steps()->create([
                    'sequence' => $index + 1,
                    'reviewer_id' => $manager->id,
                    'reviewer_name' => $manager->name,
                    'decision' => $index === 0 ? 'pending' : 'waiting',
                ]);
            }

            DB::table('report_review_events')->insert([
                'workflow_id' => $workflow->id,
                'actor_id' => $locked->user_id,
                'action' => 'submitted',
                'sequence' => 1,
                'created_at' => now(),
            ]);

            return $workflow;
        });
    }

    public function approve(ReportReviewWorkflow $workflow, User $actor, ?string $comment): ReportReviewWorkflow
    {
        return $this->decide($workflow, $actor, 'approved', $comment);
    }

    public function returnForRevision(ReportReviewWorkflow $workflow, User $actor, string $comment): ReportReviewWorkflow
    {
        return $this->decide($workflow, $actor, 'returned', $comment);
    }

    private function decide(
        ReportReviewWorkflow $workflow,
        User $actor,
        string $decision,
        ?string $comment
    ): ReportReviewWorkflow {
        abort_unless(config('report_review.enabled', false), 404);

        return DB::transaction(function () use ($workflow, $actor, $decision, $comment): ReportReviewWorkflow {
            $workflow = ReportReviewWorkflow::query()->whereKey($workflow->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $workflow->current_reviewer_id === (int) $actor->id, 403);
            abort_unless($actor->isActive() && ! $actor->isBlocked(), 403);
            abort_unless($workflow->status === 'pending', 409);

            $step = $workflow->steps()->where('sequence', $workflow->current_sequence)->lockForUpdate()->firstOrFail();
            abort_unless($step->decision === 'pending', 409);

            $step->update([
                'decision' => $decision,
                'comment' => $comment,
                'decided_at' => now(),
                'decided_by' => $actor->id,
            ]);

            $this->record($workflow, $actor->id, $decision, $step->sequence, $comment);

            if ($decision === 'returned') {
                $workflow->update(['status' => 'needs_revision']);
                Notification::create([
                    'user_id' => $workflow->report->user_id,
                    'title' => 'گزارش کار نیازمند اصلاح',
                    'message' => 'گزارش شما برای تکمیل اطلاعات برگشت داده شد.',
                    'seen' => false,
                ]);

                return $workflow->fresh();
            }

            $next = $workflow->steps()->where('sequence', '>', $step->sequence)->orderBy('sequence')->first();
            if ($next) {
                $next->update(['decision' => 'pending']);
                $workflow->update([
                    'current_sequence' => $next->sequence,
                    'current_reviewer_id' => $next->reviewer_id,
                ]);

                if ($next->reviewer_id) {
                    Notification::create([
                        'user_id' => $next->reviewer_id,
                        'title' => 'گزارش کار در انتظار بررسی',
                        'message' => 'گزارش کاری برای بررسی به شما ارجاع شده است.',
                        'seen' => false,
                    ]);
                }
            } else {
                $workflow->update([
                    'status' => 'approved',
                    'current_reviewer_id' => null,
                ]);
                Notification::create([
                    'user_id' => $workflow->report->user_id,
                    'title' => 'تأیید گزارش کار',
                    'message' => 'گزارش شما تمام مراحل بررسی را با موفقیت طی کرد.',
                    'seen' => false,
                ]);
            }

            return $workflow->fresh();
        });
    }

    public function resubmit(ReportReviewWorkflow $workflow, User $author): ReportReviewWorkflow
    {
        abort_unless(config('report_review.enabled', false), 404);

        return DB::transaction(function () use ($workflow, $author): ReportReviewWorkflow {
            $workflow = ReportReviewWorkflow::query()->whereKey($workflow->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $workflow->report->user_id === (int) $author->id, 403);
            abort_unless($author->isActive() && ! $author->isBlocked(), 403);
            abort_unless($workflow->status === 'needs_revision', 409);

            $step = $workflow->steps()->where('sequence', $workflow->current_sequence)->lockForUpdate()->firstOrFail();
            abort_unless($step->decision === 'returned' && $step->reviewer_id, 409);
            $step->update(['decision' => 'pending']);
            $workflow->update(['status' => 'pending']);

            $this->record($workflow, $author->id, 'resubmitted', $step->sequence);
            Notification::create([
                'user_id' => $step->reviewer_id,
                'title' => 'ارسال مجدد گزارش کار',
                'message' => 'گزارشی که برای اصلاح بازگشته بود دوباره ارسال شد.',
                'seen' => false,
            ]);

            return $workflow->fresh();
        });
    }

    private function record(
        ReportReviewWorkflow $workflow,
        int $actorId,
        string $action,
        int $sequence,
        ?string $comment = null
    ): void {
        DB::table('report_review_events')->insert([
            'workflow_id' => $workflow->id,
            'actor_id' => $actorId,
            'action' => $action,
            'sequence' => $sequence,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }
}
