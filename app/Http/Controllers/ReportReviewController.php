<?php

namespace App\Http\Controllers;

use App\Models\ReportReviewWorkflow;
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
            ->with(['report.user', 'steps'])
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'assigned_page');

        $returned = ReportReviewWorkflow::query()
            ->where('status', 'needs_revision')
            ->whereHas('report', fn ($query) => $query->where('user_id', $reviewerId))
            ->with(['report', 'steps'])
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'returned_page');

        return view('reports.review-inbox', compact('assigned', 'returned'));
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
}
