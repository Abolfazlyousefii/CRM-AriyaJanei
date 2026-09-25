<?php

namespace App\Rules;

use App\Models\User;
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

        $visited = [];
        $current = $manager;

        while ($current && ! isset($visited[$current->id])) {
            if ($current->id === $this->subject->id) {
                $fail('این انتخاب باعث ایجاد حلقه در ساختار سازمانی می‌شود.');

                return;
            }

            $visited[$current->id] = true;
            $current = $current->manager_id
                ? User::query()->find($current->manager_id)
                : null;
        }
    }
}
