<?php

namespace App\Services;

use App\Models\Leave;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Collection;

class LeaveMonthlyUsageService
{
    private const COUNTED_TYPES = ['استحقاقی', 'اضطراری'];
    private const APPROVED_STATUSES = ['final_approved', 'approved'];
    private const DAILY_MINUTES = 480;
    private const WARNING_MINUTES = 1200;

    public function period(?Carbon $date = null): array
    {
        $month = Verta::instance($date ?? now());
        $first = Verta::parse($month->format('Y/m').'/01')->startMonth();

        return [
            'start' => Carbon::instance($first->datetime())->startOfDay(),
            'end' => Carbon::instance($first->copy()->addMonth()->datetime())->startOfDay(),
            'label' => $month->format('Y/m'),
        ];
    }

    public function forRequests(Collection $requests): array
    {
        $period = $this->period();
        $approvedByUser = [];
        if ($requests->isNotEmpty()) {
            // One query for all employees on this page, independent of pagination of their history.
            $approved = Leave::query()->whereIn('user_id', $requests->pluck('user_id')->unique())
                ->whereIn('leave_type', self::COUNTED_TYPES)->whereIn('status', self::APPROVED_STATUSES)
                ->whereDate('start_date', '<', $period['end']->toDateString())
                ->whereDate('end_date', '>=', $period['start']->toDateString())->get();

            foreach ($approved as $leave) {
                $key = $leave->leave_type === 'اضطراری' ? 'emergency_minutes' : 'entitled_minutes';
                $approvedByUser[$leave->user_id][$key] = ($approvedByUser[$leave->user_id][$key] ?? 0)
                    + $this->minutesInPeriod($leave, $period);
            }
        }

        $summaries = [];
        foreach ($requests as $request) {
            $usage = array_replace(['entitled_minutes' => 0, 'emergency_minutes' => 0], $approvedByUser[$request->user_id] ?? []);
            $approvedMinutes = $usage['entitled_minutes'] + $usage['emergency_minutes'];
            $currentMinutes = in_array($request->status, LeaveApprovalService::PENDING_STATUSES, true)
                && ! $request->trashed() ? $this->minutesInPeriod($request, $period) : 0;
            $projected = $approvedMinutes + $currentMinutes;
            $summaries[$request->id] = $usage + [
                'approved_minutes' => $approvedMinutes,
                'current_minutes' => $currentMinutes,
                'projected_minutes' => $projected,
                'over_limit' => $this->exceedsLimit($projected),
            ];
        }

        return ['period' => $period, 'by_leave' => $summaries];
    }

    public function minutesInPeriod(Leave $leave, array $period): int
    {
        if (! in_array($leave->leave_type, self::COUNTED_TYPES, true) || ! $leave->start_date || ! $leave->end_date) {
            return 0;
        }

        $daily = $leave->leave_unit === 'روزانه';
        if (! $daily && (! $leave->start_time || ! $leave->end_time)) {
            return 0;
        }
        $from = $leave->start_date->copy()->startOfDay();
        $to = $leave->end_date->copy()->startOfDay();
        if ($daily) {
            $to->addDay(); // Daily ranges include the end date; only days inside this month count.
        } else {
            $from->setTimeFromTimeString($leave->start_time->format('H:i:s'));
            $to->setTimeFromTimeString($leave->end_time->format('H:i:s'));
        }
        if ($from->lt($period['start'])) {
            $from = $period['start']->copy();
        }
        if ($to->gt($period['end'])) {
            $to = $period['end']->copy();
        }
        if ($to->lte($from)) {
            return 0;
        }

        return $daily ? (int) $from->diffInDays($to) * self::DAILY_MINUTES : (int) $from->diffInMinutes($to);
    }

    public function exceedsLimit(int $minutes): bool
    {
        return $minutes > self::WARNING_MINUTES;
    }

    public function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        $text = $hours.' ساعت'.($remaining ? ' و '.$remaining.' دقیقه' : '');

        return strtr($text, array_combine(range(0, 9), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']));
    }
}
