<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;

class OrganizationHierarchyService
{
    public const POSITIONS = [
        'staff', 'supervisor', 'department_manager',
        'internal_manager', 'executive_manager', 'ceo',
    ];

    /**
     * The existing users.manager_id is authoritative. Department leadership is
     * a fallback only for staff who have no explicit reporting manager.
     */
    public function directManager(User $user): ?User
    {
        if ($user->manager_id !== null) {
            return $user->manager()->first();
        }

        $department = $user->department;

        if (! $department) {
            return null;
        }

        return $this->departmentFallback($user, $department);
    }

    public function departmentFallback(User $user, Department $department): ?User
    {
        $isSupervisor = (int) $department->supervisor_id === (int) $user->id
            && $department->supervisor_id !== null;
        $isManager = (int) $department->manager_id === (int) $user->id
            && $department->manager_id !== null;
        $isParent = (int) $department->parent_manager_id === (int) $user->id
            && $department->parent_manager_id !== null;

        if ($isParent || in_array($user->org_position, ['ceo', 'executive_manager', 'internal_manager'], true)) {
            return null;
        }

        if ($isManager || $user->org_position === 'department_manager') {
            $ids = [$department->parent_manager_id];
        } elseif ($isSupervisor || $user->org_position === 'supervisor') {
            $ids = [$department->manager_id, $department->parent_manager_id];
        } else {
            $ids = [$department->supervisor_id, $department->manager_id, $department->parent_manager_id];
        }

        foreach ($ids as $id) {
            if ($id === null || (int) $id === (int) $user->id) {
                continue;
            }

            $candidate = User::query()->find($id);

            if ($candidate && $candidate->isActive() && ! $candidate->isBlocked()) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Returns the chain from the immediate manager to the organization root.
     * Throws for a cycle rather than silently producing an incorrect route.
     *
     * @return Collection<int, User>
     */
    public function managerChain(User $user): Collection
    {
        $managers = collect();
        $seen = [(int) $user->id => true];
        $current = $user;

        for ($depth = 0; $depth < 200; $depth++) {
            $manager = $this->directManager($current);

            if (! $manager) {
                return $managers;
            }

            $id = (int) $manager->id;
            if (isset($seen[$id])) {
                throw new DomainException('چرخه در ساختار مدیران سازمان شناسایی شد.');
            }

            $seen[$id] = true;
            $managers->push($manager);
            $current = $manager;
        }

        throw new DomainException('عمق ساختار سازمانی از حد مجاز فراتر رفته است.');
    }

    /** @return Collection<int, User> */
    public function activeManagerChain(User $user): Collection
    {
        return $this->managerChain($user)
            ->filter(fn (User $manager) => $manager->isActive() && ! $manager->isBlocked())
            ->values();
    }

    /** @return list<int> */
    public function subordinateIds(User $leader): array
    {
        $children = [];
        User::query()->with('department')->get()->each(function (User $user) use (&$children): void {
            $manager = $this->directManager($user);
            if ($manager) {
                $children[(int) $manager->id][] = (int) $user->id;
            }
        });

        $found = [];
        $visited = [(int) $leader->id => true];
        $queue = [(int) $leader->id];

        while ($queue !== []) {
            $parent = array_shift($queue);
            foreach ($children[$parent] ?? [] as $child) {
                if (isset($visited[$child])) {
                    continue;
                }
                $visited[$child] = true;
                $found[] = $child;
                $queue[] = $child;
            }
        }

        return $found;
    }

    public function wouldCreateCycle(User $subject, User $manager): bool
    {
        if ($subject->is($manager)) {
            return true;
        }

        try {
            return $this->managerChain($manager)
                ->contains(fn (User $ancestor) => $ancestor->is($subject));
        } catch (DomainException) {
            return true;
        }
    }

    public function assertDepartmentAcyclic(Department $department): void
    {
        $userIds = array_filter([
            $department->supervisor_id,
            $department->manager_id,
            $department->parent_manager_id,
        ]);

        $users = User::query()
            ->where('department_id', $department->id)
            ->orWhereIn('id', $userIds)
            ->get();

        foreach ($users as $user) {
            $this->managerChain($user);
        }
    }
}
