<?php

namespace Tests\Feature;

use App\Models\CompanyWorkflowSetting;
use App\Models\Department;
use App\Models\Leave;
use App\Models\User;
use App\Services\StagedLeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmergencyLeaveCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        config(['organization_flow.enabled' => true]);
        $this->employee = User::factory()->create();
    }

    private function payload(string $from = '14:45', string $to = '17:00'): array
    {
        return [
            'leave_type' => 'اضطراری', 'leave_unit' => 'ساعتی',
            'start_date' => Carbon::parse('2026-11-01'), 'end_date' => Carbon::parse('2026-11-01'),
            'start_time' => $from, 'end_time' => $to, 'substitute' => null,
        ];
    }

    private function existing(string $status = 'pending', string $from = '14:45', string $to = '17:00'): Leave
    {
        return Leave::create([
            'user_id' => $this->employee->id, 'status' => $status,
            ...collect($this->payload($from, $to))->except('substitute')->all(),
        ]);
    }

    public function test_employee_can_resubmit_same_hours_after_cancelling_through_the_application(): void
    {
        $internal = User::factory()->create();
        $executive = User::factory()->create();
        CompanyWorkflowSetting::create(['id' => 1, 'internal_manager_id' => $internal->id, 'executive_manager_id' => $executive->id]);
        $department = Department::create(['name' => 'واحد آزمایشی لغو مرخصی', 'supervisor_id' => $internal->id]);
        $this->employee->update(['department_id' => $department->id]);
        $previous = $this->existing();
        $previous->update(['substitute_user_id' => $internal->id]);
        app(StagedLeaveService::class)->start($previous, $this->employee);
        $this->actingAs($this->employee)->delete(route('leaves.destroy', $previous))->assertRedirect(route('leaves'));

        $this->assertSoftDeleted($previous);
        $this->assertSame('cancelled', $previous->fresh()->status);
        app(StagedLeaveService::class)->assertRules($this->employee, [...$this->payload(), 'substitute' => $internal]);
        $this->assertDatabaseCount('leave_workflow_events', 1);
    }

    public function test_cancelled_requests_do_not_exhaust_monthly_occurrences(): void
    {
        foreach ([1, 2, 3] as $day) {
            $previous = $this->existing('cancelled', '09:00', '10:00');
            $previous->update(['start_date' => '2026-11-0'.$day, 'end_date' => '2026-11-0'.$day]);
            $previous->delete();
        }

        app(StagedLeaveService::class)->assertRules($this->employee, $this->payload());
        $this->assertSame(3, Leave::withTrashed()->where('status', 'cancelled')->count());
    }

    public function test_active_requests_still_share_the_four_hour_daily_limit(): void
    {
        $this->existing('pending', '09:00', '11:00');
        $this->expectException(ValidationException::class);
        app(StagedLeaveService::class)->assertRules($this->employee, $this->payload());
    }

    public function test_exactly_four_hours_is_allowed_after_cancellation(): void
    {
        $this->existing('cancelled')->delete();
        app(StagedLeaveService::class)->assertRules($this->employee, $this->payload('13:00', '17:00'));
        $this->assertSame(1, Leave::withTrashed()->count());
    }

    public function test_single_request_above_four_hours_remains_invalid_after_cancellation(): void
    {
        $this->existing('cancelled')->delete();
        $this->expectException(ValidationException::class);
        app(StagedLeaveService::class)->assertRules($this->employee, $this->payload('12:59', '17:00'));
    }

    public function test_soft_deletion_without_cancellation_cannot_bypass_the_monthly_limit(): void
    {
        foreach ([1, 2, 3] as $day) {
            $previous = $this->existing('final_rejected', '09:00', '10:00');
            $previous->update(['start_date' => '2026-11-0'.$day, 'end_date' => '2026-11-0'.$day]);
            $previous->delete();
        }

        $this->expectException(ValidationException::class);
        app(StagedLeaveService::class)->assertRules($this->employee, $this->payload());
    }
}
