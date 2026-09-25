<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UnifiedUserManagementTest extends TestCase
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
        $admin = User::factory()->create(['phone' => '09100000000']);
        $admin->assignRole('Admin');

        return $admin;
    }

    public function test_index_lists_users_without_direct_manager_in_a_separate_section(): void
    {
        $manager = User::factory()->create(['name' => 'Some Manager']);
        $manager->assignRole('Manager');
        $underManager = User::factory()->create(['name' => 'Under Manager Employee', 'manager_id' => $manager->id]);
        $orphan = User::factory()->create(['name' => 'Orphan Marketer Person']);
        $orphan->assignRole('Marketer');

        $response = $this->actingAs($this->admin())->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('کاربران بدون مدیر مستقیم')
            ->assertSee('Orphan Marketer Person');
        $unassigned = $response->viewData('unassignedUsers');
        $this->assertTrue($unassigned->contains('id', $orphan->id));
        $this->assertFalse($unassigned->contains('id', $manager->id));
        $this->assertFalse($unassigned->contains('id', $underManager->id));
    }

    public function test_unassigned_users_exclude_managers_but_include_the_acting_admin(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.users.index'));

        $this->assertSame(1, $response->viewData('unassignedUsers')->count());
    }

    public function test_generic_create_form_renders_with_optional_manager_and_preselected_role(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['role' => 'Marketer']))
            ->assertOk()
            ->assertSee('بدون مدیر مستقیم')
            ->assertSeeInOrder(['value="Marketer"', 'checked'], false);
    }

    public function test_generic_store_creates_a_marketer_without_manager(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'New Marketer',
            'phone' => '09120000010',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['Marketer'],
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $user = User::where('phone', '09120000010')->firstOrFail();
        $this->assertNull($user->manager_id);
        $this->assertTrue($user->hasRole('Marketer'));
    }

    public function test_generic_store_can_assign_a_manager(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Staff',
            'phone' => '09120000011',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['User'],
            'manager_id' => $manager->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($manager->id, User::where('phone', '09120000011')->value('manager_id'));
    }

    public function test_generic_store_requires_at_least_one_role(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'No Role',
            'phone' => '09120000012',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['phone' => '09120000012']);
    }

    public function test_generic_create_is_forbidden_for_non_admins(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_legacy_marketer_create_and_edit_redirect_to_generic_forms(): void
    {
        $admin = $this->admin();
        $marketer = User::factory()->create();
        $marketer->assignRole('Marketer');

        $this->actingAs($admin)->get(route('admin.marketers.create'))
            ->assertRedirect(route('admin.users.create', ['role' => 'Marketer']));

        $this->actingAs($admin)->get(route('admin.marketers.edit', $marketer->id))
            ->assertRedirect(route('admin.users.editEmployee', $marketer->id));
    }

    public function test_legacy_marketer_store_still_works_as_safety_net(): void
    {
        $this->actingAs($this->admin())->post(route('admin.marketers.store'), [
            'name' => 'Legacy Marketer',
            'phone' => '09120000013',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.marketers.index'));

        $this->assertTrue(User::where('phone', '09120000013')->firstOrFail()->hasRole('Marketer'));
    }

    public function test_marketers_list_links_to_generic_forms_not_legacy_ones(): void
    {
        $marketer = User::factory()->create();
        $marketer->assignRole('Marketer');

        $this->actingAs($this->admin())->get(route('admin.marketers.index'))
            ->assertOk()
            ->assertSee(route('admin.users.create', ['role' => 'Marketer']), false)
            ->assertSee(route('admin.users.editEmployee', $marketer->id), false)
            ->assertDontSee(route('admin.marketers.create'), false);
    }
}
