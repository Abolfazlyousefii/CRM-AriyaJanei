<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PersonnelSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Admin', 'Manager', 'Marketer', 'User'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'Acting Admin', 'phone' => '09100000000']);
        $admin->assignRole('Admin');

        return $admin;
    }

    private function userWith(string $name, string $role, ?Department $department = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => $name,
            'department_id' => $department?->id,
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }

    public function test_personnel_page_lists_every_active_user_across_managers(): void
    {
        $sales = Department::create(['name' => 'Test Sales']);
        $it = Department::create(['name' => 'Test IT']);

        $managerA = $this->userWith('Manager Alpha', 'Manager', $sales);
        $managerB = $this->userWith('Manager Beta', 'Manager', $it);
        $underA = $this->userWith('Employee Under Alpha', 'User', $sales, ['manager_id' => $managerA->id]);
        $underB = $this->userWith('Employee Under Beta', 'User', $it, ['manager_id' => $managerB->id]);
        $orphan = $this->userWith('Orphan Marketer', 'Marketer');
        $inactive = $this->userWith('Inactive Person', 'User', $sales, ['is_active' => false]);

        $response = $this->actingAs($this->admin())->get(route('admin.personnel.index'));

        $response->assertOk()
            ->assertSee('Employee Under Alpha')
            ->assertSee('Employee Under Beta')
            ->assertSee('Test Sales');

        $ids = $response->viewData('personnel')->pluck('id');
        foreach ([$managerA, $managerB, $underA, $underB, $orphan] as $user) {
            $this->assertTrue($ids->contains($user->id), "{$user->name} should be listed");
        }
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_personnel_page_links_to_the_edit_form_matching_the_role(): void
    {
        $manager = $this->userWith('Manager Alpha', 'Manager');
        $employee = $this->userWith('Plain Employee', 'User', null, ['manager_id' => $manager->id]);

        $this->actingAs($this->admin())->get(route('admin.personnel.index'))
            ->assertOk()
            ->assertSee(route('admin.users.editManager', $manager), false)
            ->assertSee(route('admin.users.editEmployee', $employee), false)
            ->assertSee(route('admin.users.create'), false);
    }

    public function test_personnel_page_filters_by_department(): void
    {
        $sales = Department::create(['name' => 'Test Sales']);
        $it = Department::create(['name' => 'Test IT']);
        $inSales = $this->userWith('Sales Person', 'User', $sales);
        $inIt = $this->userWith('IT Person', 'User', $it);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.personnel.index', ['department_id' => $sales->id]));

        $response->assertOk();
        $ids = $response->viewData('personnel')->pluck('id');
        $this->assertTrue($ids->contains($inSales->id));
        $this->assertFalse($ids->contains($inIt->id));
        $this->assertSame($sales->id, $response->viewData('selectedDepartmentId'));
    }

    public function test_personnel_page_filters_by_role(): void
    {
        $marketer = $this->userWith('Some Marketer', 'Marketer');
        $employee = $this->userWith('Some Employee', 'User');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.personnel.index', ['role' => 'Marketer']));

        $response->assertOk();
        $ids = $response->viewData('personnel')->pluck('id');
        $this->assertTrue($ids->contains($marketer->id));
        $this->assertFalse($ids->contains($employee->id));
        $this->assertSame('Marketer', $response->viewData('selectedRole'));
    }

    public function test_personnel_page_is_forbidden_for_non_admins(): void
    {
        $this->actingAs($this->userWith('Regular Person', 'User'))
            ->get(route('admin.personnel.index'))
            ->assertForbidden();
    }

    public function test_generic_store_persists_department(): void
    {
        $department = Department::create(['name' => 'Test Warehouse']);

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Department Member',
            'phone' => '09120000020',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['User'],
            'department_id' => $department->id,
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['phone' => '09120000020', 'department_id' => $department->id]);
    }

    public function test_generic_store_rejects_unknown_department(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Ghost Department',
            'phone' => '09120000021',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['User'],
            'department_id' => 999999,
        ])->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('users', ['phone' => '09120000021']);
    }

    public function test_update_employee_changes_department(): void
    {
        $department = Department::create(['name' => 'Test Accounting']);
        $employee = $this->userWith('Movable Employee', 'User', null, ['phone' => '09120000022']);

        $this->actingAs($this->admin())->put(route('admin.users.updateEmployee', $employee), [
            'name' => 'Movable Employee',
            'phone' => '09120000022',
            'roles' => ['User'],
            'department_id' => $department->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($department->id, $employee->fresh()->department_id);
    }

    public function test_create_and_edit_forms_offer_department_select(): void
    {
        Department::create(['name' => 'Test Logistics']);
        $admin = $this->admin();
        $employee = $this->userWith('Editable Employee', 'User');

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertOk()->assertSee('name="department_id"', false)->assertSee('بدون دپارتمان')->assertSee('Test Logistics');

        $this->actingAs($admin)->get(route('admin.users.editEmployee', $employee))
            ->assertOk()->assertSee('name="department_id"', false);
    }

    public function test_all_three_section_pages_show_all_three_tabs_with_the_right_one_active(): void
    {
        $admin = $this->admin();
        $tabRoutes = ['admin.personnel.index', 'admin.marketers.index', 'admin.users.index'];

        foreach ($tabRoutes as $pageRoute) {
            $response = $this->actingAs($admin)->get(route($pageRoute));
            $response->assertOk()->assertSee('پرسنل')->assertSee('بازاریاب')->assertSee('مدیریت پرسنل');

            foreach ($tabRoutes as $tabRoute) {
                $response->assertSee('href="'.route($tabRoute).'"', false);
            }

            $activeHref = preg_quote('href="'.route($pageRoute).'"', '/');
            $this->assertMatchesRegularExpression(
                '/'.$activeHref.'\s+class="nav-link active/',
                $response->getContent(),
                "The {$pageRoute} tab should be active on its own page"
            );
            $this->assertSame(1, substr_count($response->getContent(), 'nav-link active'));
        }
    }
}
