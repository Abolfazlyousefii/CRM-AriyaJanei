<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Notification;
use App\Models\Report;
use App\Models\ReportReviewStep;
use App\Models\ReportReviewWorkflow;
use App\Models\User;
use App\Services\ReportReviewWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportReviewWorkflowPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Admin', 'Manager', 'User', 'InternalManager'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        config()->set('report_review.enabled', true);
        config()->set('report_review.max_steps', 3);
    }

    private function person(string $position = 'staff', ?User $manager = null, ?Department $department = null): User
    {
        return User::factory()->create([
            'org_position' => $position,
            'manager_id' => $manager?->id,
            'department_id' => $department?->id,
        ]);
    }

    private function report(User $author): Report
    {
        return Report::create([
            'user_id' => $author->id,
            'description' => 'Daily report',
            'status' => Report::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function test_review_feature_is_disabled_by_default_and_does_not_modify_legacy_reports(): void
    {
        config()->set('report_review.enabled', false);
        $manager = $this->person('supervisor');
        $author = $this->person('staff', $manager);
        $report = $this->report($author);

        $this->assertNull(app(ReportReviewWorkflowService::class)->start($report));
        $this->assertDatabaseCount('report_review_workflows', 0);
        $this->assertSame(Report::STATUS_SUBMITTED, $report->fresh()->status);
    }

    public function test_snapshots_warehouse_supervisor_manager_and_internal_manager(): void
    {
        $internal = $this->person('internal_manager');
        $unit = Department::create(['name' => 'Phase 3 Warehouse', 'parent_manager_id' => $internal->id]);
        $manager = $this->person('department_manager', null, $unit);
        $supervisor = $this->person('supervisor', null, $unit);
        $author = $this->person('staff', null, $unit);
        $unit->update(['manager_id' => $manager->id, 'supervisor_id' => $supervisor->id]);

        $workflow = app(ReportReviewWorkflowService::class)->start($this->report($author));

        $this->assertInstanceOf(ReportReviewWorkflow::class, $workflow);
        $this->assertSame(
            [$supervisor->id, $manager->id, $internal->id],
            $workflow->steps()->orderBy('sequence')->pluck('reviewer_id')->all()
        );
        $this->assertSame($supervisor->id, $workflow->current_reviewer_id);

        // Later changes to a department must never silently rewrite submitted approval steps.
        $unit->update(['supervisor_id' => null]);
        $this->assertSame($supervisor->id, $workflow->fresh()->steps()->orderBy('sequence')->first()->reviewer_id);
    }

    public function test_sales_unit_without_supervisor_routes_directly_to_internal_manager(): void
    {
        $internal = $this->person('internal_manager');
        $department = Department::create(['name' => 'Phase 3 Sales', 'parent_manager_id' => $internal->id]);
        $author = $this->person('staff', null, $department);

        $workflow = app(ReportReviewWorkflowService::class)->start($this->report($author));

        $this->assertSame([$internal->id], $workflow->steps()->pluck('reviewer_id')->all());
    }

    public function test_unknown_reporting_chain_does_not_start_approval_or_change_report_status(): void
    {
        $author = $this->person();
        $report = $this->report($author);

        $this->assertNull(app(ReportReviewWorkflowService::class)->start($report));
        $this->assertSame(Report::STATUS_SUBMITTED, $report->fresh()->status);
    }

    public function test_only_the_current_reviewer_can_approve_and_outsiders_cannot_view_inbox(): void
    {
        $manager = $this->person('supervisor');
        $manager->assignRole('Manager');
        $outsider = $this->person();
        $outsider->assignRole('User');
        $author = $this->person('staff', $manager);
        $author->assignRole('User');
        $report = $this->report($author);
        $workflow = app(ReportReviewWorkflowService::class)->start($report);

        $this->actingAs($outsider)->postJson(route('report-review.approve', $workflow))
            ->assertForbidden();

        $this->get(route('report-review.inbox'))->assertOk()->assertDontSee('Daily report');

        $this->actingAs($manager)->get(route('report-review.inbox'))
            ->assertOk()->assertSee('Daily report');

        $this->postJson(route('report-review.approve', $workflow), [
            'comment' => 'Reviewed',
        ])->assertOk()->assertJsonPath('status', 'approved');

        $this->assertSame(Report::STATUS_SUBMITTED, $report->fresh()->status);
        $this->assertSame('approved', $workflow->fresh()->status);
        $this->assertSame(1, ReportReviewStep::query()->where('decision', 'approved')->count());
        $this->postJson(route('report-review.approve', $workflow))->assertStatus(409);
    }

    public function test_approval_is_sequential_and_notifies_only_the_next_reviewer(): void
    {
        $internal = $this->person('internal_manager');
        $supervisor = $this->person('supervisor', $internal);
        $author = $this->person('staff', $supervisor);
        $report = $this->report($author);

        $workflow = app(ReportReviewWorkflowService::class)->start($report);
        $this->assertSame(0, Notification::count());

        $this->actingAs($internal)->postJson(route('report-review.approve', $workflow))
            ->assertForbidden();

        $this->actingAs($supervisor)->postJson(route('report-review.approve', $workflow))
            ->assertOk()->assertJsonPath('status', 'pending');

        $this->assertSame($internal->id, $workflow->fresh()->current_reviewer_id);
        $this->assertDatabaseHas('notifications', ['user_id' => $internal->id, 'seen' => 0]);
        $this->assertSame(1, Notification::count());
    }

    public function test_return_and_author_resubmit_preserve_approval_history(): void
    {
        $manager = $this->person('supervisor');
        $author = $this->person('staff', $manager);
        $report = $this->report($author);
        $workflow = app(ReportReviewWorkflowService::class)->start($report);

        $this->actingAs($manager)->postJson(route('report-review.return', $workflow), [
            'comment' => 'Need more detail',
        ])->assertOk()->assertJsonPath('status', 'needs_revision');
        $this->assertSame(Report::STATUS_SUBMITTED, $report->fresh()->status);

        $this->postJson(route('report-review.resubmit', $workflow))->assertForbidden();

        $this->actingAs($author)->postJson(route('report-review.resubmit', $workflow))
            ->assertOk()->assertJsonPath('status', 'pending');

        $this->assertSame('pending', $workflow->fresh()->status);
        $this->assertSame(1, $workflow->steps()->count());
        $this->assertSame(1, $workflow->steps()->where('decision', 'pending')->count());
        $this->assertSame('Need more detail', $workflow->steps()->first()->comment);
    }

    public function test_enabled_workflow_does_not_duplicate_steps_on_repeated_start(): void
    {
        $manager = $this->person('supervisor');
        $author = $this->person('staff', $manager);
        $report = $this->report($author);
        $service = app(ReportReviewWorkflowService::class);
        $first = $service->start($report);
        $second = $service->start($report);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $first->steps()->count());
    }
}
