<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Leave;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportReviewWorkflowService;
use Hekmatinasser\Verta\Verta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationalRoutingAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Admin', 'Manager', 'User', 'Marketer', 'InternalManager', 'Owner', 'Supervisor', 'Accountant'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        config()->set('report_review.enabled', true);
        config()->set('report_review.all_users', true);
    }

    private function person(string $role = 'User', array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }

    private function report(User $owner): Report
    {
        return Report::create(['user_id' => $owner->id, 'description' => 'Original daily activity',
            'status' => Report::STATUS_SUBMITTED, 'submitted_at' => now()]);
    }

    private function requestLeave(User $owner): Leave
    {
        $date = Verta::instance(now()->addDays(2))->format('Y/m/d');
        $this->actingAs($owner)->post(route('leaves.store'), [
            'leave_type' => 'استحقاقی', 'leave_unit' => 'روزانه',
            'start_date' => $date, 'end_date' => $date,
            'start_time' => '08:00', 'end_time' => '16:00',
        ])->assertSessionHasNoErrors()->assertRedirect(route('leaves'));
        return Leave::latest('id')->firstOrFail();
    }

    public function test_unrelated_manager_cannot_change_another_employees_report(): void
    {
        $report = $this->report($this->person());
        $this->actingAs($this->person('Manager'))->put(route('user.reports.update', $report), [
            'description' => 'Unrelated manager attempted overwrite',
        ])->assertForbidden();
        $this->assertSame('Original daily activity', $report->fresh()->description);
    }

    public function test_unrelated_manager_cannot_read_a_report_or_mark_it_read(): void
    {
        $report = $this->report($this->person());
        $this->actingAs($this->person('Manager'))->get(route('user.reports.show', $report))->assertForbidden();
        $this->assertSame(Report::STATUS_SUBMITTED, $report->fresh()->status);
    }

    public function test_feedback_and_score_cannot_be_set_by_an_unrelated_manager_or_the_author(): void
    {
        $author = $this->person();
        $report = $this->report($author);
        foreach ([$this->person('Manager'), $author] as $actor) {
            $this->actingAs($actor)->put(route('user.reports.feedback', $report), [
                'feedback' => 'Unauthorized feedback', 'rating' => 5,
            ])->assertForbidden();
        }
        $this->assertNull($report->fresh()->rating);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_organizational_manager_can_view_and_score_team_but_cannot_rewrite_their_work(): void
    {
        $manager = $this->person('Manager');
        $unit = Department::create(['name' => 'Acceptance marketing', 'supervisor_id' => $manager->id]);
        $manager->update(['department_id' => $unit->id, 'org_position' => 'supervisor']);
        $author = $this->person('Marketer', ['department_id' => $unit->id, 'org_position' => 'staff']);
        $report = $this->report($author);
        $this->actingAs($manager)->get(route('user.reports.show', $report))->assertOk();
        $this->put(route('user.reports.feedback', $report), ['feedback' => 'Good visit details', 'rating' => 4])->assertRedirect();
        $this->put(route('user.reports.update', $report), ['description' => 'Manager rewrite'])->assertForbidden();
        $this->delete(route('user.reports.destroy', $report))->assertForbidden();
        $this->get(route('user.reports.reportsManagment'))->assertOk()
            ->assertViewHas('reports', fn ($reports) => $reports->contains('id', $report->id))
            ->assertDontSee(route('user.reports.edit', $report), false)
            ->assertDontSee(route('user.reports.destroy', $report), false);
        $this->assertSame(4, $report->fresh()->rating);
    }

    public function test_previous_fixed_user_ids_no_longer_grant_access(): void
    {
        $author = $this->person('User', ['id' => 31]);
        $viewer = $this->person('User', ['id' => 35]);
        $report = $this->report($author);
        $this->actingAs($viewer)->get(route('user.reports.show', $report))->assertForbidden();
        $this->get(route('user.reports.index'))->assertOk()
            ->assertViewHas('reports', fn ($reports) => $reports->isEmpty());
    }

    public function test_managerial_position_without_team_permission_does_not_grant_team_access(): void
    {
        $manager = $this->person('User', ['org_position' => 'ceo']);
        $report = $this->report($this->person('User', ['manager_id' => $manager->id]));
        $this->actingAs($manager)->get(route('user.reports.show', $report))->assertForbidden();
    }

    public function test_snapshot_reviewer_can_open_report_after_the_employee_changes_manager(): void
    {
        $original = $this->person('Supervisor', ['org_position' => 'supervisor']);
        $author = $this->person('User', ['manager_id' => $original->id]);
        $report = $this->report($author);
        $workflow = app(ReportReviewWorkflowService::class)->start($report);
        $author->update(['manager_id' => $this->person('Manager')->id]);
        $this->actingAs($original)->get(route('user.reports.show', $report))->assertOk();
        $this->postJson(route('report-review.approve', $workflow))->assertOk();
        $this->assertSame($original->id, $workflow->steps()->first()->reviewer_id);
    }

    public function test_legacy_submit_endpoint_cannot_change_a_returned_workflow_report(): void
    {
        $manager = $this->person('Manager');
        $author = $this->person('User', ['manager_id' => $manager->id]);
        $report = $this->report($author);
        $service = app(ReportReviewWorkflowService::class);
        $workflow = $service->start($report);
        $service->returnForRevision($workflow, $manager, 'Please add visit results');
        $report->update(['status' => Report::STATUS_DRAFT]);
        $this->actingAs($author)->post(route('user.reports.submit', $report))->assertForbidden();
        $this->assertSame('needs_revision', $workflow->fresh()->status);
    }

    public function test_new_leave_notifies_and_accepts_only_the_configured_responsible_person(): void
    {
        $general = $this->person('Manager');
        DB::table('leave_approvers')->insert(['user_id' => $general->id]);
        $manager = $this->person('User', ['org_position' => 'supervisor']);
        $owner = $this->person('User', ['manager_id' => $manager->id]);
        $leave = $this->requestLeave($owner);
        $this->assertSame([$manager->id], Notification::where('leave_id', $leave->id)->pluck('user_id')->all());
        $this->actingAs($general)->patch(route('leaves.approve', $leave))->assertForbidden();
        $this->get(route('leaves'))->assertOk()->assertDontSee(route('leaves.approve', $leave), false);
        $this->actingAs($manager)->get(route('leaves'))->assertOk()->assertSee(route('leaves.approve', $leave), false);
        $this->patch(route('leaves.approve', $leave))->assertRedirect();
        $this->assertSame('final_approved', $leave->fresh()->status);
    }

    public function test_leave_routing_uses_department_fallback_and_preserves_the_assigned_reviewer(): void
    {
        $original = $this->person('User', ['org_position' => 'supervisor']);
        $unit = Department::create(['name' => 'Acceptance sales', 'supervisor_id' => $original->id]);
        $original->update(['department_id' => $unit->id]);
        $owner = $this->person('User', ['department_id' => $unit->id, 'org_position' => 'staff']);
        $leave = $this->requestLeave($owner);
        $replacement = $this->person('Manager');
        $owner->update(['manager_id' => $replacement->id]);
        $this->actingAs($replacement)->patch(route('leaves.approve', $leave))->assertForbidden();
        $this->actingAs($original)->patch(route('leaves.approve', $leave))->assertRedirect();
        $this->assertSame($original->id, $leave->fresh()->assigned_approver_id);
    }

    public function test_invalid_organization_keeps_legacy_leave_flow_without_inventing_a_manager(): void
    {
        $general = $this->person('Manager');
        DB::table('leave_approvers')->insert(['user_id' => $general->id]);
        $owner = $this->person();
        $owner->update(['manager_id' => $owner->id]);
        $leave = $this->requestLeave($owner);
        $this->assertNull($leave->assigned_approver_id);
        $this->actingAs($general)->patch(route('leaves.approve', $leave))->assertRedirect();
    }

    public function test_selected_global_approver_cannot_approve_own_leave(): void
    {
        $owner = $this->person();
        DB::table('leave_approvers')->insert(['user_id' => $owner->id]);
        $leave = $this->requestLeave($owner);
        $this->actingAs($owner)->patch(route('leaves.approve', $leave))->assertForbidden();
    }

    public function test_score_only_payload_is_also_protected(): void
    {
        $report = $this->report($this->person());
        $this->actingAs($this->person('Manager'))->put(route('user.reports.feedback', $report), ['rating' => 5])->assertForbidden();
        $this->assertNull($report->fresh()->rating);
    }

    public function test_cyclic_hierarchy_cannot_grant_team_authorization(): void
    {
        $manager = $this->person('Manager');
        $employee = $this->person('User', ['manager_id' => $manager->id]);
        $manager->update(['manager_id' => $employee->id]);
        $report = $this->report($employee);
        $this->actingAs($manager)->get(route('user.reports.show', $report))->assertForbidden();
    }

    public function test_marketer_can_submit_a_report_and_only_assigned_reviewer_is_notified(): void
    {
        $manager = $this->person('Manager');
        $author = $this->person('Marketer', ['manager_id' => $manager->id]);
        $this->actingAs($author)->post(route('user.reports.store'), ['description' => 'Wholesale visit results'])->assertRedirect();
        $this->assertSame([$manager->id], Notification::pluck('user_id')->all());
    }

    public function test_inactive_leave_reviewer_does_not_fall_back_to_global_approvers(): void
    {
        $manager = $this->person('Manager');
        $owner = $this->person('User', ['manager_id' => $manager->id]);
        $leave = $this->requestLeave($owner);
        $manager->update(['is_active' => false]);
        $general = $this->person('Manager');
        DB::table('leave_approvers')->insert(['user_id' => $general->id]);
        $this->actingAs($general)->patch(route('leaves.approve', $leave))->assertForbidden();
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_admin_can_explicitly_reassign_a_report_with_a_logged_reason(): void
    {
        $old = $this->person('Manager');
        $author = $this->person('User', ['manager_id' => $old->id]);
        $workflow = app(ReportReviewWorkflowService::class)->start($this->report($author));
        $old->update(['is_active' => false]);
        $replacement = $this->person('Supervisor');
        $admin = $this->person('Admin');
        $payload = ['reviewer_id' => $replacement->id, 'reason' => 'Original reviewer left the company'];
        $this->actingAs($author)->postJson(route('report-review.reassign', $workflow), $payload)->assertForbidden();
        $this->actingAs($admin)->postJson(route('report-review.reassign', $workflow), ['reviewer_id' => $replacement->id])->assertUnprocessable();
        $this->postJson(route('report-review.reassign', $workflow), $payload)->assertOk();
        $this->assertSame($replacement->id, $workflow->fresh()->current_reviewer_id);
        $this->assertDatabaseHas('report_review_events', ['workflow_id' => $workflow->id, 'actor_id' => $admin->id, 'action' => 'reassigned']);
        $this->actingAs($replacement)->postJson(route('report-review.approve', $workflow))->assertOk();
    }

    public function test_inactive_returned_report_reviewer_requires_explicit_reassignment_before_resubmission(): void
    {
        $manager = $this->person('Manager');
        $author = $this->person('User', ['manager_id' => $manager->id]);
        $report = $this->report($author);
        $service = app(ReportReviewWorkflowService::class);
        $workflow = $service->start($report);
        $service->returnForRevision($workflow, $manager, 'Please complete this report');
        $manager->update(['is_active' => false]);
        $report->update(['description' => 'Corrected report with new details']);
        $this->actingAs($author)->postJson(route('report-review.resubmit', $workflow))->assertStatus(409);
    }

    public function test_admin_can_reassign_an_open_organizational_leave_without_changing_its_owner(): void
    {
        $old = $this->person('Manager');
        $owner = $this->person('User', ['manager_id' => $old->id]);
        $leave = $this->requestLeave($owner);
        $old->update(['is_active' => false]);
        $replacement = $this->person('Manager');
        $payload = ['assignee_id' => $replacement->id, 'reason' => 'Original reviewer unavailable'];
        $this->actingAs($owner)->patch(route('admin.leaves.reassign', $leave), $payload)->assertForbidden();
        $this->actingAs($this->person('Admin'))->patch(route('admin.leaves.reassign', $leave), $payload)->assertRedirect();
        $this->assertSame($owner->id, $leave->fresh()->user_id);
        $this->assertSame($replacement->id, $leave->fresh()->assigned_approver_id);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $leave->id, 'subject_type' => Leave::class, 'description' => 'تغییر مسئول بررسی مرخصی']);
        $this->actingAs($replacement)->patch(route('leaves.approve', $leave))->assertRedirect();
    }

    public function test_management_chart_totals_do_not_include_unrelated_reports(): void
    {
        $manager = $this->person('Manager');
        $employee = $this->person('Marketer', ['manager_id' => $manager->id]);
        $this->report($employee)->update(['successful_calls' => 2]);
        $this->report($this->person('Marketer'))->update(['successful_calls' => 999]);
        $this->actingAs($manager)->get(route('admin.reports'))->assertOk()
            ->assertViewHas('reportsByDay', fn ($days) => $days->sum('successful') === 2);
    }

    public function test_authorized_voice_feedback_is_saved_with_its_report(): void
    {
        Storage::fake('public');
        $manager = $this->person('Manager');
        $report = $this->report($this->person('User', ['manager_id' => $manager->id]));
        $this->actingAs($manager)->put(route('user.reports.feedback', $report), [
            'feedback' => 'Voice review', 'voice_file' => UploadedFile::fake()->create('review.webm', 2, 'audio/webm'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($report->fresh()->voice_path);
        Storage::disk('public')->assertExists($report->fresh()->voice_path);
    }

    public function test_explicit_snapshot_reviewer_does_not_need_a_manager_software_role(): void
    {
        $reviewer = $this->person('Accountant');
        $author = $this->person('User', ['manager_id' => $reviewer->id]);
        $report = $this->report($author);
        $workflow = app(ReportReviewWorkflowService::class)->start($report);
        $this->actingAs($reviewer)->get(route('user.reports.show', $report))->assertOk();
        $this->postJson(route('report-review.approve', $workflow))->assertOk();
        $this->post(route('user.reports.store'), ['description' => 'No implicit access to create reports'])->assertForbidden();
    }
}
