<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Rules\EligibleManager;
use App\Services\OrganizationHierarchyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationHierarchyPhaseOneTest extends TestCase
{
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createIsolatedSchema();

        foreach (['Admin', 'User', 'Manager'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function person(array $attributes = [], string $role = 'User'): User
    {
        $number = ++$this->sequence;
        $person = User::create(array_merge([
            'name' => "Personnel {$number}",
            'phone' => sprintf('0912%07d', $number),
            'password' => 'sample-password',
        ], $attributes));
        $person->assignRole($role);

        return $person;
    }

    public function test_warehouse_chain_goes_from_supervisor_to_ceo(): void
    {
        $ceo = $this->person(['org_position' => 'ceo']);
        $executive = $this->person(['org_position' => 'executive_manager', 'manager_id' => $ceo->id]);
        $internal = $this->person(['org_position' => 'internal_manager', 'manager_id' => $executive->id]);
        $warehouse = Department::create(['name' => 'انبار', 'parent_manager_id' => $internal->id]);

        $manager = $this->person(['department_id' => $warehouse->id, 'org_position' => 'department_manager']);
        $supervisor = $this->person(['department_id' => $warehouse->id, 'org_position' => 'supervisor']);
        $employee = $this->person(['department_id' => $warehouse->id]);
        $warehouse->update(['manager_id' => $manager->id, 'supervisor_id' => $supervisor->id]);

        $service = app(OrganizationHierarchyService::class);

        $this->assertSame(
            [$supervisor->id, $manager->id, $internal->id, $executive->id, $ceo->id],
            $service->managerChain($employee->fresh())->pluck('id')->all()
        );
        $this->assertSame([$manager->id, $internal->id, $executive->id, $ceo->id],
            $service->managerChain($supervisor->fresh())->pluck('id')->all());
        $this->assertContains($employee->id, $service->subordinateIds($internal));
    }

    public function test_sales_skips_missing_supervisor_and_department_manager(): void
    {
        $internal = $this->person(['org_position' => 'internal_manager']);
        $sales = Department::create(['name' => 'فروش', 'parent_manager_id' => $internal->id]);
        $seller = $this->person(['department_id' => $sales->id]);

        $this->assertSame([$internal->id],
            app(OrganizationHierarchyService::class)->managerChain($seller->fresh())->pluck('id')->all());
    }

    public function test_an_explicit_manager_overrides_department_defaults(): void
    {
        $internal = $this->person(['org_position' => 'internal_manager']);
        $supervisor = $this->person(['org_position' => 'supervisor']);
        $special = $this->person(['org_position' => 'supervisor']);
        $sales = Department::create(['name' => 'فروش', 'parent_manager_id' => $internal->id, 'supervisor_id' => $supervisor->id]);
        $seller = $this->person(['department_id' => $sales->id, 'manager_id' => $special->id]);

        $this->assertSame([$special->id],
            app(OrganizationHierarchyService::class)->managerChain($seller->fresh())->pluck('id')->all());
    }

    public function test_department_leadership_update_is_transactional_and_rejects_cycles(): void
    {
        $admin = $this->person([], 'Admin');
        $department = Department::create(['name' => 'انبار']);
        $worker = $this->person(['department_id' => $department->id]);
        $manager = $this->person(['department_id' => $department->id, 'manager_id' => $worker->id], 'Manager');

        $this->actingAs($admin)->patchJson(
            route('admin.organization.departments.update', $department),
            ['supervisor_id' => $worker->id, 'manager_id' => $manager->id]
        )->assertUnprocessable()->assertJsonValidationErrors('manager_id');

        $this->assertNull($department->fresh()->supervisor_id);
        $this->assertNull($department->fresh()->manager_id);
    }

    public function test_department_leadership_rejects_same_person_in_two_positions(): void
    {
        $admin = $this->person([], 'Admin');
        $leader = $this->person(['org_position' => 'department_manager']);
        $department = Department::create(['name' => 'انبار']);

        $this->actingAs($admin)->patchJson(
            route('admin.organization.departments.update', $department),
            ['manager_id' => $leader->id, 'supervisor_id' => $leader->id]
        )->assertUnprocessable()->assertJsonValidationErrors('manager_id');
    }

    public function test_administrator_can_place_personnel_without_changing_login_roles(): void
    {
        $admin = $this->person([], 'Admin');
        $department = Department::create(['name' => 'آیتی']);
        $supervisor = $this->person(['org_position' => 'supervisor']);
        $worker = $this->person();

        $this->actingAs($admin)->patchJson(
            route('admin.organization.personnel.update', $worker),
            [
                'department_id' => $department->id,
                'org_position' => 'staff',
                'manager_id' => $supervisor->id,
            ]
        )->assertOk();

        $this->assertSame($supervisor->id, $worker->fresh()->manager_id);
        $this->assertSame($department->id, $worker->fresh()->department_id);
        $this->assertTrue($worker->fresh()->hasRole('User'));
        $this->assertFalse($worker->fresh()->hasRole('Manager'));
    }

    public function test_regular_employee_cannot_modify_organization_structure(): void
    {
        $employee = $this->person();
        $department = Department::create(['name' => 'فروش']);

        $this->actingAs($employee)->patchJson(
            route('admin.organization.departments.update', $department),
            ['name' => 'تغییر غیرمجاز']
        )->assertForbidden();

        $this->assertSame('فروش', $department->fresh()->name);
    }

    public function test_manager_validation_rejects_an_organizational_cycle(): void
    {
        $supervisor = $this->person(['org_position' => 'supervisor']);
        $employee = $this->person(['manager_id' => $supervisor->id], 'Manager');

        $validator = Validator::make(
            ['manager_id' => $employee->id],
            ['manager_id' => [new EligibleManager($supervisor)]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_personnel_placement_cannot_create_self_referential_manager(): void
    {
        $admin = $this->person([], 'Admin');
        $employee = $this->person(['org_position' => 'supervisor']);

        $this->actingAs($admin)->patchJson(
            route('admin.organization.personnel.update', $employee),
            ['manager_id' => $employee->id]
        )->assertUnprocessable()->assertJsonValidationErrors('manager_id');
    }

    private function createIsolatedSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->foreignId('manager_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('org_position', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivation_reason')->nullable();
            $table->foreignId('deactivated_by')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('supervisor_id')->nullable();
            $table->foreignId('manager_id')->nullable();
            $table->foreignId('parent_manager_id')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
