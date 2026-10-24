<?php

namespace App\Services;

use App\Models\CompanyWorkflowSetting;
use App\Models\Department;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CompanyStructureService
{
    private bool $loaded = false;

    private ?CompanyWorkflowSetting $cached = null;

    public function reset(): void
    {
        $this->loaded = false;
        $this->cached = null;
    }

    public function enabled(): bool
    {
        return (bool) config('organization_flow.enabled', false);
    }

    public function settings(): ?CompanyWorkflowSetting
    {
        if (! $this->enabled()) {
            return null;
        }
        if (! $this->loaded) {
            $this->cached = CompanyWorkflowSetting::find(1);
            $this->loaded = true;
        }

        return $this->cached;
    }

    public function leaders(): array
    {
        $settings = $this->settings();
        $internal = $settings ? User::find($settings->internal_manager_id) : null;
        $executive = $settings ? User::find($settings->executive_manager_id) : null;
        foreach ([$internal, $executive] as $person) {
            if (! $person || ! $person->isActive() || $person->isBlocked()) {
                throw ValidationException::withMessages(['organization' => 'مدیر داخلی و مدیر اجرایی فعال باید در تنظیمات چارت مشخص باشند.']);
            }
        }
        if ($internal->is($executive)) {
            throw ValidationException::withMessages(['organization' => 'مدیر داخلی و مدیر اجرایی باید متفاوت باشند.']);
        }

        return [$internal, $executive];
    }

    public function isUnitLeader(User $person): bool
    {
        return $person->department_id && Department::whereKey($person->department_id)
            ->where(fn ($q) => $q->where('supervisor_id', $person->id)->orWhere('manager_id', $person->id))->exists();
    }

    public function isLeader(User $person): bool
    {
        $settings = $this->settings();

        return $settings && (in_array((int) $person->id, [(int) $settings->internal_manager_id, (int) $settings->executive_manager_id], true) || $this->isUnitLeader($person));
    }

    public function corporateFallback(User $person): ?User
    {
        $settings = $this->settings();
        if (! $settings || (int) $person->id === (int) $settings->executive_manager_id) {
            return null;
        }

        return User::find((int) $person->id === (int) $settings->internal_manager_id ? $settings->executive_manager_id : $settings->internal_manager_id);
    }
}
