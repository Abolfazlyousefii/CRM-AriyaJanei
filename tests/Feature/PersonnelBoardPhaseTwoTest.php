<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelBoardPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Admin', 'User', 'Manager', 'Marketer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function loginAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        return $admin;
    }

    public function test_admin_can_open_the_organization_board_and_primary_management_screen(): void
    {
        $this->actingAs($this->loginAdmin())
            ->get(route('admin.organization.board'))
            ->assertOk()
            ->assertSee('ساختار سازمانی')
            ->assertSee('مدیریت واحدها')
            ->assertSee('org-board', false)
            ->assertSee('org-personnel.js', false);

        $this->get(route('admin.users.index'))
            ->assertOk()->assertSee('ساختار سازمانی');
    }

    public function test_non_admin_cannot_open_or_read_personnel_board(): void
    {
        $person = User::factory()->create();
        $person->assignRole('User');

        $this->actingAs($person)
            ->get(route('admin.organization.board'))->assertForbidden();

        $this->getJson(route('admin.organization.index'))->assertForbidden();
    }

    public function test_data_endpoint_includes_roles_and_organizational_placement_without_password(): void
    {
        $admin = $this->loginAdmin();
        $unit = Department::create(['name' => 'بازرگانی']);
        $employee = User::factory()->create([
            'name' => 'Board Employee',
            'department_id' => $unit->id,
            'org_position' => 'staff',
        ]);
        $employee->assignRole('User');

        $response = $this->actingAs($admin)->getJson(route('admin.organization.index'))
            ->assertOk()->assertJsonFragment([
                'name' => 'Board Employee',
                'org_position' => 'staff',
                'department_id' => $unit->id,
            ]);

        $member = collect($response->json('personnel'))->firstWhere('id', $employee->id);
        $this->assertSame('User', $member['roles'][0]['name']);
        $this->assertArrayNotHasKey('password', $member);
        $this->assertArrayNotHasKey('remember_token', $member);
    }

    public function test_admin_updates_department_leadership_and_personnel_from_board_endpoints(): void
    {
        $admin = $this->loginAdmin();
        $department = Department::create(['name' => 'انبار']);
        $supervisor = User::factory()->create([
            'department_id' => $department->id, 'org_position' => 'supervisor',
        ]);
        $supervisor->assignRole('Manager');
        $worker = User::factory()->create();

        $this->actingAs($admin)->patchJson(
            route('admin.organization.departments.update', $department),
            ['supervisor_id' => $supervisor->id]
        )->assertOk();
        $this->assertSame($supervisor->id, $department->fresh()->supervisor_id);

        $this->patchJson(route('admin.organization.personnel.update', $worker), [
            'department_id' => $department->id,
            'org_position' => 'staff',
            'manager_id' => null,
        ])->assertOk();

        $this->assertSame($department->id, $worker->fresh()->department_id);
    }

    public function test_invalid_org_manager_is_rejected_without_changing_personnel(): void
    {
        $admin = $this->loginAdmin();
        $worker = User::factory()->create();

        $this->actingAs($admin)->patchJson(route('admin.organization.personnel.update', $worker), [
            'manager_id' => $worker->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('manager_id');

        $this->assertNull($worker->fresh()->manager_id);
    }

    public function test_unit_supervisor_must_be_member_of_that_unit(): void
    {
        $admin = $this->loginAdmin();
        $accounting = Department::create(['name' => 'مالی']);
        $warehouse = Department::create(['name' => 'انبار']);
        $outsider = User::factory()->create(['department_id' => $warehouse->id]);

        $this->actingAs($admin)->patchJson(
            route('admin.organization.departments.update', $accounting),
            ['supervisor_id' => $outsider->id]
        )->assertUnprocessable()->assertJsonValidationErrors('supervisor_id');
        $this->assertNull($accounting->fresh()->supervisor_id);
    }

    public function test_department_creation_works_with_optional_leadership(): void
    {
        $this->actingAs($this->loginAdmin())->postJson(
            route('admin.organization.departments.store'),
            ['name' => 'فناوری اطلاعات', 'manager_id' => null, 'supervisor_id' => null]
        )->assertCreated();

        $this->assertDatabaseHas('departments', ['name' => 'فناوری اطلاعات']);
    }
}
