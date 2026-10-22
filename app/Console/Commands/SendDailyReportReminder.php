<?php

namespace App\Console\Commands;

use App\Facades\Sms;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyReportReminder extends Command {
    protected $signature   = 'reports:send-reminder';
    protected $description = 'Send SMS reminder at 21:00 to users who have not submitted today report';

    public function handle() {
        $excludedUserIds = [ 17, 43, 42, 1, 30, 36, 26, 32 ];

        $today = Carbon::today()
            ->toDateString();

        // کاربرانی که امروز گزارش submitted/read ندارند
        $users = User::query()
            ->whereNotIn('id', $excludedUserIds)
            ->whereHas('roles', function ( $q ) {
                $q->whereIn('name', [ 'User', 'Marketer', 'Manager' ]);
            })
            ->whereNotNull('phone')
            ->whereDoesntHave('reports', function ( $q ) use ( $today ) {
                $q->whereDate('submitted_at', $today)
                    ->whereIn('status', [ Report::STATUS_SUBMITTED, Report::STATUS_READ ]);
            })
            ->get();

        $base = User::query()
            ->whereNotIn('id', $excludedUserIds);

        $this->info('All (not excluded): ' . $base->count());
        $this->info('With roles: ' . ( clone $base )->whereHas('roles', fn( $q ) => $q->whereIn('name', [
                'User',
                'Marketer',
                'Manager',
            ]))
                ->count());
        $this->info('With phone: ' . ( clone $base )->whereHas('roles', fn( $q ) => $q->whereIn('name', [
                'User',
                'Marketer',
                'Manager',
            ]))
                ->whereNotNull('phone')
                ->count());
        $this->info('Final (need reminder): ' . ( clone $base )->whereHas('roles', fn( $q ) => $q->whereIn('name', [
                'User',
                'Marketer',
                'Manager',
            ]))
                ->whereNotNull('phone')
                ->whereDoesntHave('reports', function ( $q ) use ( $today ) {
                    $q->whereDate('submitted_at', $today)
                        ->whereIn('status', [ Report::STATUS_SUBMITTED, Report::STATUS_READ ]);
                })
                ->count());

        foreach ( $users as $user ) {
            try {
                $log = Sms::sendPattern($user->phone, '533252', [
                    $user->name,
                ]);
                Log::info($log);
            } catch ( \Throwable $e ) {
                Log::error("SMS failed for user {$user->id} phone {$user->phone}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminders sent to: " . $users->count() . " users.");
        return 0;
    }
}
