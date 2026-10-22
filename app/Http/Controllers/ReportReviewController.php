<?php

namespace App\Http\Controllers;

use App\Models\ReportReviewWorkflow;
use App\Models\User;
use App\Services\ReportReviewWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportReviewController extends Controller
{
    public function inbox(Request $request): View
    {
        abort_unless(config('report_review.enabled', false), 404);

        $reviewerId = $request->user()->id;
        $assigned = ReportReviewWorkflow::query()
            ->where('status', 'pending')
            ->where('current_reviewer_id', $reviewerId)
            ->with(['report.user', 'steps', 'events.actor', 'currentReviewer'])
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'assigned_page');

        $returned = ReportReviewWorkflow::query()
            ->where('status', 'needs_revision')
            ->whereHas('report', fn ($query) => $query->where('user_id', $reviewerId))
            ->with(['report', 'steps', 'events.actor', 'currentReviewer'])
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'returned_page');

        foreach ([$assigned, $returned] as $page) {
            $page->getCollection()->each(fn ($workflow) => $workflow->report?->setRelation('reviewWorkflow', $workflow));
        }
        $attention = null;
        $reviewers = collect();
        if ($request->user()->hasRole('Admin')) {
            $attention = ReportReviewWorkflow::query()->whereIn('status', ['pending', 'needs_revision'])
                ->whereDoesntHave('currentReviewer', function ($query) {
                    $query->where('is_active', true)->where(function ($active) {
                        $active->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
                    });
                })->with(['report.user', 'steps'])->latest('updated_at')->paginate(15, ['*'], 'attention_page');
            $reviewers = User::query()->where('is_active', true)->where(function ($query) {
                $query->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
            })->orderBy('name')->get(['id', 'name']);
        }
        return view('reports.review-inbox', compact('assigned', 'returned', 'attention', 'reviewers'));
    }

    public function approve(
        Request $request,
        ReportReviewWorkflow $workflow,
        ReportReviewWorkflowService $service
    ): JsonResponse {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json([
            'status' => $service->approve($workflow, $request->user(), $validated['comment'] ?? null)->status,
        ]);
    }

    public function returnForRevision(
        Request $request,
        ReportReviewWorkflow $workflow,
        ReportReviewWorkflowService $service
    ): JsonResponse {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        return response()->json([
            'status' => $service->returnForRevision($workflow, $request->user(), $validated['comment'])->status,
        ]);
    }

    public function resubmit(
        Request $request,
        ReportReviewWorkflow $workflow,
        ReportReviewWorkflowService $service
    ): JsonResponse {
        return response()->json(['status' => $service->resubmit($workflow, $request->user())->status]);
    }

    public function reassign(Request $request, ReportReviewWorkflow $workflow, ReportReviewWorkflowService $service): JsonResponse
    {
        abort_unless($request->user()->hasRole('Admin'), 403);
        $data = $request->validate([
            'reviewer_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        return response()->json(['status' => $service->reassign(
            $workflow, $request->user(), User::findOrFail($data['reviewer_id']), $data['reason']
        )->status]);
    }
}
