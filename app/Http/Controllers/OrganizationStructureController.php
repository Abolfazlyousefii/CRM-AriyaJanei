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
    public function index(): JsonResponse
    {
        return response()->json([
            'departments' => Department::query()
                ->with(['supervisor:id,name', 'manager:id,name', 'parentManager:id,name'])
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'personnel' => User::query()
                ->with(['department:id,name', 'manager:id,name'])
                ->orderBy('name')
                ->get(['id', 'name', 'department_id', 'manager_id', 'org_position', 'is_active']),
        ]);
    }

    public function storeDepartment(Request $request, OrganizationHierarchyService $hierarchy): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
        ], $this->leaderRules()));

        $department = DB::transaction(function () use ($validated, $hierarchy): Department {
            $department = Department::create($validated);
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
        ], $this->leaderRules()));

        DB::transaction(function () use ($department, $validated, $hierarchy): void {
            $department->update($validated);
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

        $oldDepartmentId = $user->department_id;

        DB::transaction(function () use ($user, $validated, $hierarchy, $oldDepartmentId): void {
            $user->update($validated);
            $current = $user->fresh();

            try {
                $hierarchy->managerChain($current);

                if ($current->department_id) {
                    $hierarchy->assertDepartmentAcyclic($current->department);
                }

                if ($oldDepartmentId && $oldDepartmentId !== $current->department_id) {
                    $oldDepartment = Department::find($oldDepartmentId);
                    if ($oldDepartment) {
                        $hierarchy->assertDepartmentAcyclic($oldDepartment);
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

        try {
            $hierarchy->assertDepartmentAcyclic($department);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['manager_id' => $exception->getMessage()]);
        }
    }
}
