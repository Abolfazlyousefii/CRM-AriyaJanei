<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Rules\EligibleManager;
use App\Services\OrganizationHierarchyService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizationStructureController extends Controller
{
    public function board(): \Illuminate\View\View
    {
        return view('admin.organization.board');
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'departments' => Department::query()
                ->with(['supervisor:id,name', 'manager:id,name', 'parentManager:id,name'])
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'personnel' => User::query()
                ->with(['department:id,name', 'manager:id,name', 'roles:id,name'])
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'department_id', 'manager_id', 'org_position', 'is_active', 'blocked_until']),
        ]);
    }

    public function storeDepartment(Request $request, OrganizationHierarchyService $hierarchy): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
        ], $this->leaderRules(), $this->membershipRules()));

        $department = DB::transaction(function () use ($validated, $hierarchy): Department {
            Department::query()->orderBy('id')->lockForUpdate()->get();
            $department = Department::create(collect($validated)->except(['member_ids', 'expected_member_ids', 'use_unit_routing'])->all());
            $this->updateMembership($department, $validated, $hierarchy);
            $this->assertValidLeadership($department, $hierarchy);

            return $department;
        });

        return response()->json(['department' => $department->fresh()], 201);
    }

    public function updateDepartment(
        Request $request,
        Department $department,
        OrganizationHierarchyService $hierarchy
    ): JsonResponse {
        $validated = $request->validate(array_merge([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
        ], $this->leaderRules(), $this->membershipRules()));

        DB::transaction(function () use ($department, $validated, $hierarchy): void {
            Department::query()->orderBy('id')->lockForUpdate()->get();
            $department->refresh();
            $this->assertMembershipSnapshot($department, $validated);
            $department->update(collect($validated)->except(['member_ids', 'expected_member_ids', 'use_unit_routing'])->all());
            $this->updateMembership($department, $validated, $hierarchy);
            $this->assertValidLeadership($department->fresh(), $hierarchy);
        });

        return response()->json(['department' => $department->fresh()]);
    }

    public function updatePlacement(
        Request $request,
        User $user,
        OrganizationHierarchyService $hierarchy
    ): JsonResponse {
        $validated = $request->validate([
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'org_position' => ['sometimes', 'nullable', Rule::in(OrganizationHierarchyService::POSITIONS)],
            'manager_id' => ['sometimes', 'nullable', 'integer', new EligibleManager($user)],
        ]);

        DB::transaction(function () use ($user, $validated, $hierarchy): void {
            Department::query()->orderBy('id')->lockForUpdate()->get();
            $user->refresh();
            $oldDepartmentId = $user->department_id;
            $user->update($validated);
            $current = $user->fresh();

            try {
                $hierarchy->managerChain($current);

                if ($current->department_id) {
                    $this->assertValidLeadership($current->department, $hierarchy);
                }

                if ($oldDepartmentId && $oldDepartmentId !== $current->department_id) {
                    $oldDepartment = Department::find($oldDepartmentId);
                    if ($oldDepartment) {
                        $this->assertValidLeadership($oldDepartment, $hierarchy);
                    }
                }
            } catch (DomainException $exception) {
                throw ValidationException::withMessages(['manager_id' => $exception->getMessage()]);
            }
        });

        return response()->json([
            'person' => $user->fresh()->load(['department:id,name', 'manager:id,name']),
        ]);
    }

    private function leaderRules(): array
    {
        return [
            'supervisor_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'manager_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'parent_manager_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ];
    }

    private function membershipRules(): array
    {
        return [
            'member_ids' => ['sometimes', 'array', 'max:2000'],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'expected_member_ids' => ['sometimes', 'array', 'max:2000'],
            'expected_member_ids.*' => ['integer', 'distinct'],
            'use_unit_routing' => ['sometimes', 'boolean'],
        ];
    }

    private function assertMembershipSnapshot(Department $department, array $data): void
    {
        if (! array_key_exists('expected_member_ids', $data)) return;
        $actual = User::where('department_id', $department->id)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $expected = collect($data['expected_member_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
        abort_if($actual !== $expected, 409, 'اعضای واحد هم‌زمان تغییر کرده‌اند. فرم را ببندید و صفحه را تازه‌سازی کنید.');
    }

    private function updateMembership(Department $department, array $data, OrganizationHierarchyService $hierarchy): void
    {
        if (! array_key_exists('member_ids', $data)) return;

        $ids = array_map('intval', $data['member_ids']);
        $people = User::query()->where(fn ($q) => $q->where('department_id', $department->id)->orWhereIn('id', $ids))
            ->orderBy('id')->lockForUpdate()->get();
        $oldDepartments = $people->pluck('department_id')->filter()->unique();
        foreach ($people as $person) {
            $selected = in_array((int) $person->id, $ids, true);
            if ($selected && $person->department_id && (int) $person->department_id !== (int) $department->id &&
                Department::query()->whereKey($person->department_id)->where(fn ($q) => $q
                    ->where('supervisor_id', $person->id)->orWhere('manager_id', $person->id))->exists()) {
                throw ValidationException::withMessages(['member_ids' => 'ابتدا مسئول جایگزین واحد قبلی را تعیین کنید: '.$person->name]);
            }
            $person->department_id = $selected ? $department->id : null;
            if ($selected && ($data['use_unit_routing'] ?? false)) $person->manager_id = null;
            // Organizational titles never grant software roles. Company titles remain independent.
            if (! in_array($person->org_position, ['ceo', 'executive_manager', 'internal_manager'], true)) {
                $person->org_position = $selected
                    ? ((int) $department->supervisor_id === (int) $person->id ? 'supervisor'
                        : ((int) $department->manager_id === (int) $person->id ? 'department_manager' : 'staff'))
                    : 'staff';
            }
            $person->save();
        }
        foreach ($oldDepartments->push($department->id)->unique() as $id) {
            $this->assertValidLeadership(Department::findOrFail($id), $hierarchy);
        }
    }

    private function assertValidLeadership(Department $department, OrganizationHierarchyService $hierarchy): void
    {
        $ids = array_values(array_filter([
            $department->supervisor_id,
            $department->manager_id,
            $department->parent_manager_id,
        ], fn ($id) => $id !== null));

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'manager_id' => 'سرپرست، مدیر واحد و مسئول بالادستی باید افراد متفاوتی باشند.',
            ]);
        }

        foreach (['supervisor_id', 'manager_id'] as $field) {
            $leaderId = $department->{$field};
            if ($leaderId !== null && (int) User::query()->whereKey($leaderId)->value('department_id') !== (int) $department->id) {
                throw ValidationException::withMessages([
                    $field => 'سرپرست و مدیر واحد باید عضو همین دپارتمان باشند.',
                ]);
            }
        }

        try {
            $hierarchy->assertDepartmentAcyclic($department);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['manager_id' => $exception->getMessage()]);
        }
    }
}
