<?php

namespace Tests\Unit;

use App\Models\Leave;
use App\Services\LeaveMonthlyUsageService;
use Carbon\Carbon;
use Tests\TestCase;

class LeaveMonthlyUsageTest extends TestCase
{
    private function leave(array $attributes = []): Leave
    {
        return new Leave(array_replace([
            'leave_type' => 'استحقاقی', 'leave_unit' => 'ساعتی',
            'start_date' => '2026-11-01', 'end_date' => '2026-11-01',
            'start_time' => '14:45', 'end_time' => '17:00',
        ], $attributes));
    }

    public function test_current_jalali_month_uses_real_month_boundaries(): void
    {
        $period = app(LeaveMonthlyUsageService::class)->period(Carbon::parse('2026-11-01'));
        $this->assertSame('1405/08/01', verta($period['start'])->format('Y/m/d'));
        $this->assertSame('1405/09/01', verta($period['end'])->format('Y/m/d'));
        $this->assertSame('1405/08/30', verta($period['end']->copy()->subDay())->format('Y/m/d'));
    }

    public function test_hourly_duration_preserves_minutes_and_excludes_other_types(): void
    {
        $service = app(LeaveMonthlyUsageService::class);
        $period = $service->period(Carbon::parse('2026-11-01'));
        $this->assertSame(135, $service->minutesInPeriod($this->leave(), $period));
        $this->assertSame(135, $service->minutesInPeriod($this->leave(['leave_type' => 'اضطراری']), $period));
        foreach (['ماموریت', 'استعلاجی'] as $type) {
            $this->assertSame(0, $service->minutesInPeriod($this->leave(['leave_type' => $type]), $period));
        }
    }

    public function test_daily_leave_counts_eight_hours_per_inclusive_day_and_clips_to_month(): void
    {
        $service = app(LeaveMonthlyUsageService::class);
        $period = $service->period(Carbon::parse('2026-11-01'));
        $this->assertSame(480, $service->minutesInPeriod($this->leave(['leave_unit' => 'روزانه']), $period));
        $this->assertSame(960, $service->minutesInPeriod($this->leave([
            'leave_unit' => 'روزانه', 'start_date' => $period['start']->copy()->subDay(),
            'end_date' => $period['start']->copy()->addDay(),
        ]), $period));
        $this->assertSame(0, $service->minutesInPeriod($this->leave([
            'start_date' => $period['end'], 'end_date' => $period['end'],
        ]), $period));
    }

    public function test_twenty_hours_is_not_over_limit_but_twenty_hours_and_one_minute_is(): void
    {
        $service = app(LeaveMonthlyUsageService::class);
        $this->assertFalse($service->exceedsLimit(1200));
        $this->assertTrue($service->exceedsLimit(1201));
        $this->assertSame('۲ ساعت و ۱۵ دقیقه', $service->formatMinutes(135));
    }
}
