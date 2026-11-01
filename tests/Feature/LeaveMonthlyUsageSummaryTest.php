<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveMonthlyUsageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveMonthlyUsageSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function record(User $author, array $extra = []): Leave
    {
        return Leave::create(array_replace([
            'user_id' => $author->id, 'leave_type' => 'استحقاقی', 'leave_unit' => 'ساعتی',
            'start_date' => '2026-11-01', 'end_date' => '2026-11-01',
            'start_time' => '08:00', 'end_time' => '18:00', 'status' => 'final_approved',
        ], $extra));
    }

    public function test_summary_separates_approved_usage_from_current_request_and_ignores_other_pending_or_excluded_records(): void
    {
        $author = User::factory()->create();
        $this->record($author);
        $this->record($author, ['leave_unit' => 'روزانه', 'start_date' => '2026-11-02', 'end_date' => '2026-11-02']);
        $this->record($author, ['leave_type' => 'اضطراری', 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'approved']);
        foreach (['pending', 'cancelled', 'final_rejected'] as $status) {
            $this->record($author, ['status' => $status]);
        }
        $this->record($author)->delete();
        $this->record($author, ['leave_type' => 'ماموریت']);
        $this->record($author, ['leave_type' => 'استعلاجی']);
        $this->record($author, ['start_date' => '2026-09-01', 'end_date' => '2026-09-01']);
        $this->record(User::factory()->create());
        $request = $this->record($author, ['status' => 'pending', 'start_time' => '14:45', 'end_time' => '15:00']);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $result = app(LeaveMonthlyUsageService::class)->forRequests(collect([$request]));
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
        $summary = $result['by_leave'][$request->id];
        $this->assertSame(1200, $summary['approved_minutes']);
        $this->assertSame(1080, $summary['entitled_minutes']);
        $this->assertSame(120, $summary['emergency_minutes']);
        $this->assertSame(15, $summary['current_minutes']);
        $this->assertSame(1215, $summary['projected_minutes']);
        $this->assertTrue($summary['over_limit']);
    }

    public function test_approved_request_is_not_counted_twice(): void
    {
        $approved = $this->record(User::factory()->create());
        $summary = app(LeaveMonthlyUsageService::class)->forRequests(collect([$approved]))['by_leave'][$approved->id];
        $this->assertSame(600, $summary['approved_minutes']);
        $this->assertSame(600, $summary['projected_minutes']);
        $this->assertFalse($summary['over_limit']);
    }

    public function test_reviewer_sees_red_warning_next_to_approval_and_an_unrelated_employee_does_not(): void
    {
        Role::findOrCreate('Admin', 'web');
        $reviewer = User::factory()->create();
        $reviewer->assignRole('Admin');
        $author = User::factory()->create();
        $this->record($author, ['leave_unit' => 'روزانه', 'start_date' => '2026-11-01', 'end_date' => '2026-11-03']);
        $pending = $this->record($author, ['status' => 'pending', 'approval_route' => 'organization', 'assigned_approver_id' => $reviewer->id]);

        $this->actingAs($reviewer)->get(route('leaves'))->assertOk()
            ->assertSee('data-monthly-leave-summary="'.$pending->id.'"', false)
            ->assertSee('alert-danger', false)
            ->assertSee('بیشتر از ۲۰ ساعت')
            ->assertSee('با احتساب این درخواست')
            ->assertSee('action="'.route('leaves.approve', $pending).'"', false);

        $this->actingAs(User::factory()->create())->get(route('leaves'))->assertOk()
            ->assertDontSee('data-monthly-leave-summary="'.$pending->id.'"', false);
    }
}
