<?php

namespace Tests\Feature;

use App\Models\CompanyWorkflowSetting;
use App\Models\Department;
use App\Models\Leave;
use App\Models\Report;
use App\Models\User;
use App\Services\ExecutiveLeaveCodeService;
use App\Services\OrganizationHierarchyService;
use App\Services\Sms\SmsManager;
use App\Services\StagedLeaveService;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $executive;

    private User $internal;

    private User $supervisor;

    private User $manager;

    private User $worker;

    private User $substitute;

    private Department $unit;

    protected function setUp(): void
    {
        parent::setUp();
        config(['organization_flow.enabled' => true, 'report_review.enabled' => false]);
        Role::findOrCreate('User', 'web');
        $this->executive = $this->person(['org_position' => 'executive_manager']);
        $this->internal = $this->person(['org_position' => 'internal_manager']);
        CompanyWorkflowSetting::create(['id' => 1, 'internal_manager_id' => $this->internal->id, 'executive_manager_id' => $this->executive->id]);
        $this->unit = Department::create(['name' => 'آیتی نمونه']);
        $this->manager = $this->person(['department_id' => $this->unit->id, 'org_position' => 'department_manager']);
        $this->supervisor = $this->person(['department_id' => $this->unit->id, 'org_position' => 'supervisor']);
        $this->worker = $this->person(['department_id' => $this->unit->id, 'org_position' => 'staff']);
        $this->substitute = $this->person(['department_id' => $this->unit->id, 'org_position' => 'staff', 'manager_id' => $this->manager->id]);
        $this->unit->update(['supervisor_id' => $this->supervisor->id, 'manager_id' => $this->manager->id]);
    }

    private function person(array $data = []): User
    {
        $user = User::factory()->create($data);
        $user->assignRole('User');

        return $user;
    }

    private function payload(array $extra = []): array
    {
        $date = Verta::instance(now()->addDays(4))->format('Y/m/d');

        return array_replace(['leave_type' => 'استحقاقی', 'leave_unit' => 'روزانه', 'start_date' => $date, 'end_date' => $date,
            'start_time' => '09:00', 'end_time' => '17:00', 'substitute_user_id' => $this->substitute->id], $extra);
    }

    private function leave(?User $author = null): Leave
    {
        $this->actingAs($author ?? $this->worker)->post(route('leaves.store'), $this->payload())
            ->assertSessionHasNoErrors()->assertRedirect(route('leaves'));

        return Leave::latest('id')->firstOrFail();
    }

    public function test_substitute_must_approve_before_manager_and_all_stages_are_required(): void
    {
        $leave = $this->leave();
        $this->assertSame(['substitute', 'unit', 'internal', 'executive'], $leave->steps()->orderBy('position')->pluck('stage')->all());
        $this->actingAs($this->supervisor)->patch(route('leaves.approve', $leave))->assertForbidden();
        foreach ([$this->substitute, $this->supervisor, $this->internal] as $actor) {
            $this->actingAs($actor)->patch(route('leaves.approve', $leave))->assertRedirect();
            $this->assertSame('pending', $leave->fresh()->status);
        }
        $this->actingAs($this->executive)->patch(route('leaves.approve', $leave))->assertRedirect();
        $this->assertSame('final_approved', $leave->fresh()->status);
    }

    public function test_supervisor_skips_unit_manager_and_goes_to_internal_after_substitute(): void
    {
        $leave = $this->leave($this->supervisor);
        $this->assertSame(['substitute', 'internal', 'executive'], $leave->steps()->orderBy('position')->pluck('stage')->all());
    }

    public function test_substitute_from_another_department_is_rejected_even_with_same_manager(): void
    {
        $other = $this->person(['manager_id' => $this->worker->manager_id]);
        $this->actingAs($this->worker)->post(route('leaves.store'), $this->payload(['substitute_user_id' => $other->id]))
            ->assertSessionHasErrors('substitute_user_id');
        $this->assertDatabaseCount('leaves', 0);
    }

    public function test_normal_daily_requires_48_hours_but_hourly_and_emergency_do_not(): void
    {
        $date = Verta::instance(now()->addDay())->format('Y/m/d');
        $this->actingAs($this->worker)->post(route('leaves.store'), $this->payload(['start_date' => $date, 'end_date' => $date]))->assertSessionHasErrors('start_date');
        $this->post(route('leaves.store'), $this->payload(['start_date' => $date, 'end_date' => $date, 'leave_unit' => 'ساعتی']))->assertSessionHasNoErrors();
        $this->post(route('leaves.store'), $this->payload(['start_date' => $date, 'end_date' => $date, 'leave_type' => 'اضطراری']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('leaves', 2);
    }

    public function test_emergency_cap_counts_three_submissions_even_when_rejected_or_cancelled(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->worker)->post(route('leaves.store'), $this->payload(['leave_type' => 'اضطراری']))->assertSessionHasNoErrors();
        }
        Leave::first()->update(['status' => 'final_rejected']);
        Leave::latest('id')->first()->delete();
        $this->post(route('leaves.store'), $this->payload(['leave_type' => 'اضطراری', 'leave_unit' => 'ساعتی']))->assertSessionHasErrors('leave_type');
        $this->assertSame(3, Leave::withTrashed()->count());
    }

    public function test_report_viewers_follow_chart_without_manager_software_role_and_comments_do_not_overwrite(): void
    {
        $report = Report::create(['user_id' => $this->worker->id, 'description' => 'گزارش روزانه', 'status' => 'submitted', 'submitted_at' => now()]);
        foreach ([$this->supervisor, $this->manager, $this->internal, $this->executive] as $viewer) {
            $this->actingAs($viewer)->get(route('user.reports.show', $report))->assertOk();
            $this->post(route('reports.comments.store', $report), ['body' => 'نظر '.$viewer->id])->assertRedirect();
        }
        $this->assertDatabaseCount('report_comments', 4);
        $this->assertNull($report->fresh()->feedback);
        $this->actingAs($this->person())->post(route('reports.comments.store', $report), ['body' => 'نامرتبط'])->assertForbidden();
        $this->assertDatabaseCount('report_review_workflows', 0);
    }

    public function test_otp_is_only_for_internal_manager_at_executive_stage_and_cannot_be_replayed(): void
    {
        $leave = $this->leave();
        $service = app(ExecutiveLeaveCodeService::class);
        $this->actingAs($this->internal)->post(route('leaves.executive-code.request', $leave))->assertForbidden();
        foreach ([$this->substitute, $this->supervisor, $this->internal] as $actor) {
            app(StagedLeaveService::class)->decide($leave, $actor, true);
        }
        $step = $leave->steps()->where('stage', 'executive')->firstOrFail();
        DB::table('leave_executive_codes')->insert(['leave_id' => $leave->id, 'step_id' => $step->id, 'requester_id' => $this->internal->id,
            'executive_id' => $this->executive->id, 'code_hash' => Hash::make('4826'), 'attempts' => 0, 'delivery_status' => 'sent',
            'expires_at' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->worker)->post(route('leaves.executive-code.verify', $leave), ['code' => '4826'])->assertForbidden();
        $this->actingAs($this->internal)->post(route('leaves.executive-code.verify', $leave), ['code' => '4826'])->assertRedirect();
        $this->assertSame('final_approved', $leave->fresh()->status);
        $event = $leave->events()->where('method', 'sms_code')->firstOrFail();
        $this->assertSame($this->internal->id, $event->actor_id);
        $this->assertSame($this->executive->id, $event->principal_id);
        $this->post(route('leaves.executive-code.verify', $leave), ['code' => '4826'])->assertForbidden();
    }

    private function executiveStage(): Leave
    {
        $leave = $this->leave();
        foreach ([$this->substitute, $this->supervisor, $this->internal] as $actor) {
            app(StagedLeaveService::class)->decide($leave, $actor, true);
        }

        return $leave->fresh();
    }

    private function challenge(Leave $leave, array $extra = []): void
    {
        DB::table('leave_executive_codes')->insert(array_replace([
            'leave_id' => $leave->id, 'step_id' => $leave->steps()->where('stage', 'executive')->value('id'),
            'requester_id' => $this->internal->id, 'executive_id' => $this->executive->id, 'code_hash' => Hash::make('4826'),
            'attempts' => 0, 'delivery_status' => 'sent', 'expires_at' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now(),
        ], $extra));
    }

    public function test_wrong_codes_commit_attempt_counts_and_lock_after_five_tries(): void
    {
        $leave = $this->executiveStage();
        $this->challenge($leave);
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->internal)->post(route('leaves.executive-code.verify', $leave), ['code' => '1111'])->assertSessionHasErrors('code');
        }
        $this->assertSame(5, (int) DB::table('leave_executive_codes')->value('attempts'));
        $this->post(route('leaves.executive-code.verify', $leave), ['code' => '4826'])->assertSessionHasErrors('code');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_expired_code_cannot_approve_leave(): void
    {
        $leave = $this->executiveStage();
        $this->challenge($leave, ['expires_at' => now()->subSecond()]);
        $this->actingAs($this->internal)->post(route('leaves.executive-code.verify', $leave), ['code' => '4826'])->assertSessionHasErrors('code');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_code_for_another_leave_cannot_be_used(): void
    {
        $first = $this->executiveStage();
        $this->challenge($first);
        $second = $this->executiveStage();
        $this->actingAs($this->internal)->post(route('leaves.executive-code.verify', $second), ['code' => '4826'])->assertSessionHasErrors('code');
        $this->assertSame('pending', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
    }

    public function test_sms_is_sent_only_to_executive_and_response_never_discloses_code(): void
    {
        $leave = $this->executiveStage();
        $captured = null;
        $this->mock(SmsManager::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('send')->once()->withArgs(function ($phone, $text) use (&$captured) {
                $this->assertSame($this->executive->phone, $phone);
                preg_match('/:\s*(\d{4})/u', $text, $m);
                $captured = $m[1];

                return true;
            })->andReturn(['status' => 'success', 'response' => ['recId' => 123456]]);
        });
        $response = $this->actingAs($this->internal)->post(route('leaves.executive-code.request', $leave));
        $response->assertSessionHasNoErrors()->assertRedirect();
        $response->assertDontSee($captured);
        $row = DB::table('leave_executive_codes')->first();
        $this->assertNotSame($captured, $row->code_hash);
        $this->assertTrue(Hash::check($captured, $row->code_hash));
        $this->post(route('leaves.executive-code.verify', $leave), ['code' => $captured])->assertSessionHasNoErrors();
        $this->assertSame('final_approved', $leave->fresh()->status);
    }

    public function test_failed_sms_delivery_cannot_be_used_for_approval(): void
    {
        $leave = $this->executiveStage();
        $this->mock(SmsManager::class, fn ($mock) => $mock->shouldReceive('send')->once()->andReturn(['status' => 'success', 'response' => ['recId' => -6]]));
        $this->actingAs($this->internal)->post(route('leaves.executive-code.request', $leave))->assertSessionHasErrors('code');
        $this->assertSame('failed', DB::table('leave_executive_codes')->value('delivery_status'));
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_rejection_stops_route_and_duplicate_approval_is_forbidden(): void
    {
        $leave = $this->leave();
        $this->actingAs($this->substitute)->patch(route('leaves.reject', $leave), ['reason' => 'امکان جایگزینی ندارم'])->assertRedirect();
        $this->assertSame('final_rejected', $leave->fresh()->status);
        $this->actingAs($this->supervisor)->patch(route('leaves.approve', $leave))->assertForbidden();
        $this->assertSame(0, $leave->steps()->whereIn('status', ['pending', 'waiting'])->count());
    }

    public function test_submitted_leave_is_frozen_but_owner_can_cancel_without_removing_history(): void
    {
        $leave = $this->leave();
        $this->assertFalse($this->worker->can('update', $leave));
        $this->actingAs($this->worker);
        $this->delete(route('leaves.destroy', $leave))->assertRedirect();
        $this->assertSoftDeleted('leaves', ['id' => $leave->id]);
        $this->assertSame(4, $leave->steps()->count());
        $this->actingAs($this->substitute)->patch(route('leaves.approve', $leave))->assertNotFound();
    }

    public function test_emergency_quota_resets_at_jalali_boundary_not_gregorian_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-04-01 10:00:00', 'Asia/Tehran'));
        $last = Carbon::instance(Verta::parse('1405/01/31')->datetime())->toDateString();
        $next = Verta::parse('1405/02/01')->format('Y/m/d');
        for ($i = 0; $i < 3; $i++) {
            Leave::create(['user_id' => $this->worker->id, 'leave_type' => 'اضطراری', 'leave_unit' => 'روزانه', 'start_date' => $last, 'end_date' => $last, 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'final_rejected']);
        }
        $this->actingAs($this->worker)->post(route('leaves.store'), $this->payload(['leave_type' => 'اضطراری', 'start_date' => '1405/01/30', 'end_date' => '1405/01/30']))->assertSessionHasErrors('leave_type');
        $this->post(route('leaves.store'), $this->payload(['leave_type' => 'اضطراری', 'start_date' => $next, 'end_date' => $next]))->assertSessionHasNoErrors();
        $this->assertSame(4, Leave::count());
    }

    public function test_reports_notify_all_chart_ancestors_without_starting_approval_even_if_old_flag_is_on(): void
    {
        config(['report_review.enabled' => true, 'report_review.all_users' => true]);
        $this->actingAs($this->worker)->post(route('user.reports.store'), ['description' => 'گزارش بدون نیاز به تأیید'])->assertRedirect();
        $this->assertDatabaseCount('report_review_workflows', 0);
        $this->assertEqualsCanonicalizing([$this->supervisor->id, $this->manager->id, $this->internal->id, $this->executive->id], DB::table('notifications')->pluck('user_id')->all());
    }

    public function test_hourly_leave_cannot_span_multiple_dates_to_bypass_daily_notice(): void
    {
        $this->actingAs($this->worker)->post(route('leaves.store'), $this->payload([
            'leave_unit' => 'ساعتی', 'end_date' => Verta::instance(now()->addDays(5))->format('Y/m/d'),
        ]))->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('leaves', 0);
    }

    public function test_active_company_chart_ignores_legacy_direct_manager_override_for_assigned_members(): void
    {
        $outsider = $this->person();
        $this->worker->update(['manager_id' => $outsider->id]);
        $chain = app(OrganizationHierarchyService::class)->managerChain($this->worker->fresh())->pluck('id')->all();
        $this->assertSame([$this->supervisor->id, $this->manager->id, $this->internal->id, $this->executive->id],$chain);
    }
}
