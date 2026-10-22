<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
// اگر Gate لازم دارید:
// use Illuminate\Support\Facades\Gate;

use App\Models\Reminder;
use App\Policies\ReminderPolicy;
use App\Models\Leave;
use App\Policies\LeavePolicy;
use App\Models\RequestTicket;
use App\Policies\RequestTicketPolicy;
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Report::class => \App\Policies\ReportPolicy::class,
        Reminder::class => ReminderPolicy::class,
        Leave::class    => LeavePolicy::class,
        RequestTicket::class => RequestTicketPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // اگر Gate تعریف خاصی دارید، اینجا بگذارید.
        // Gate::define('something', function ($user) { ... });
    }
}
