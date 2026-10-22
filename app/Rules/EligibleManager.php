<?php

namespace App\Rules;

use App\Models\User;
use App\Services\OrganizationHierarchyService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EligibleManager implements ValidationRule
{
    public function __construct(private readonly ?User $subject = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $manager = User::query()->eligibleManagers()->find($value);

        if (! $manager) {
            $fail('مدیر انتخاب‌شده معتبر یا فعال نیست.');

            return;
        }

        if (! $this->subject) {
            return;
        }

        if ($manager->is($this->subject)) {
            $fail('کاربر نمی‌تواند مدیر مستقیم خودش باشد.');

            return;
        }

        if (app(OrganizationHierarchyService::class)->wouldCreateCycle($this->subject, $manager)) {
            $fail('این انتخاب باعث ایجاد حلقه در ساختار سازمانی می‌شود.');
        }
    }
}
