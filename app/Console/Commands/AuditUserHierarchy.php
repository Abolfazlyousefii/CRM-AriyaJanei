<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditUserHierarchy extends Command
{
    protected $signature = 'users:audit-structure {--fix : Repair deterministic hierarchy problems}';

    protected $description = 'Audit and optionally repair invalid user-manager relationships';

    public function handle(): int
    {
        $root = $this->findRootUser();

        if (! $root) {
            $this->error('No active Owner or Admin account is available as the organization root.');

            return self::FAILURE;
        }

        $users = User::query()->with(['roles:id,name', 'manager.roles:id,name'])->get();
        $selfManaged = $users->filter(fn (User $user): bool => $user->manager_id === $user->id);
        $unassigned = $users->filter(fn (User $user): bool => $user->id !== $root->id && $user->manager_id === null);
        $invalidManagers = $users->filter(fn (User $user): bool => $user->manager
            && ! $user->manager->roles->contains(fn ($role): bool => User::isManagerialRole($role->name)));
        $cycles = $users->filter(fn (User $user): bool => $this->hasManagementCycle($user));

        $this->displayIssues('Self-managed users', $selfManaged);
        $this->displayIssues('Users without a manager', $unassigned);
        $this->displayIssues('Users assigned to a non-manager', $invalidManagers);
        $this->displayIssues('Users in a management cycle', $cycles);

        $issueCount = $selfManaged->count() + $unassigned->count() + $invalidManagers->count() + $cycles->count();

        if (! $this->option('fix')) {
            $this->info("Audit completed: {$issueCount} issue(s). Run with --fix to repair deterministic cases.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($root, $selfManaged, $unassigned, $invalidManagers): void {
            $root->update(['manager_id' => null]);

            $selfManaged->each(function (User $user) use ($root): void {
                $user->update(['manager_id' => $user->id === $root->id ? null : $root->id]);
            });

            $unassigned->each(fn (User $user) => $user->update(['manager_id' => $root->id]));
            $invalidManagers->each(fn (User $user) => $user->update(['manager_id' => $root->id]));
            $this->breakRemainingCycles($root);
        });

        $this->info("Hierarchy repaired. Root user: {$root->name} (#{$root->id}).");

        return self::SUCCESS;
    }

    private function findRootUser(): ?User
    {
        return User::query()->active()->role('Owner')->first()
            ?? User::query()->active()->role('Admin')->first();
    }

    private function displayIssues(string $title, Collection $users): void
    {
        $this->line("{$title}: {$users->count()}");

        if ($users->isNotEmpty()) {
            $this->table(['ID', 'Name', 'Manager ID'], $users->map(fn (User $user): array => [
                $user->id,
                $user->name,
                $user->manager_id ?? '-',
            ])->all());
        }
    }

    private function hasManagementCycle(User $user): bool
    {
        $visited = [];
        $current = $user;

        while ($current?->manager_id) {
            if (isset($visited[$current->id])) {
                return true;
            }

            $visited[$current->id] = true;
            $current = User::query()->find($current->manager_id);
        }

        return false;
    }

    private function breakRemainingCycles(User $root): void
    {
        while ($cycleEntry = User::query()->get()->map(fn (User $user) => $this->cycleEntry($user))->filter()->first()) {
            $cycleEntry->update([
                'manager_id' => $cycleEntry->id === $root->id ? null : $root->id,
            ]);
        }
    }

    private function cycleEntry(User $user): ?User
    {
        $visited = [];
        $current = $user;

        while ($current?->manager_id) {
            if (isset($visited[$current->id])) {
                return $current;
            }

            $visited[$current->id] = true;
            $current = User::query()->find($current->manager_id);
        }

        return null;
    }
}
