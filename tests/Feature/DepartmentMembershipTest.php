<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        return $user;
    }

    public function test_members_and_optional_leaders_can_be_configured_together_without_changing_roles(): void
    {
        $unit = Department::create(['name' => 'خدمات']);
        $leader = User::factory()->create();
        $worker = User::factory()->create(['manager_id' => $leader->id]);
        $worker->assignRole(Role::findOrCreate('Marketer', 'web'));
        $unselected = User::factory()->create();
        $this->actingAs($this->admin())->patchJson(route('admin.organization.departments.update', $unit), [
            'member_ids' => [$leader->id, $worker->id], 'supervisor_id' => $leader->id,
            'manager_id' => null, 'use_unit_routing' => true,
        ])->assertOk();
        $this->assertSame($unit->id, $worker->fresh()->department_id);
        $this->assertNull($worker->fresh()->manager_id);
        $this->assertSame('supervisor', $leader->fresh()->org_position);
        $this->assertSame('staff', $worker->fresh()->org_position);
        $this->assertTrue($worker->fresh()->hasRole('Marketer'));
        $this->assertNull($unselected->fresh()->department_id);
    }

    public function test_unit_can_have_no_leaders_and_members_can_be_removed(): void
    {
        $unit = Department::create(['name' => 'بدون مسئول']);
        $member = User::factory()->create(['department_id' => $unit->id]);
        $this->actingAs($this->admin())->patchJson(route('admin.organization.departments.update', $unit), [
            'member_ids' => [], 'supervisor_id' => null, 'manager_id' => null,
        ])->assertOk();
        $this->assertNull($member->fresh()->department_id);
    }

    public function test_transfer_of_another_units_leader_rolls_back_all_members(): void
    {
        $source = Department::create(['name' => 'مبدأ']);
        $target = Department::create(['name' => 'مقصد']);
        $leader = User::factory()->create(['department_id' => $source->id]);
        $source->update(['supervisor_id' => $leader->id]);
        $worker = User::factory()->create();
        $this->actingAs($this->admin())->patchJson(route('admin.organization.departments.update', $target), [
            'member_ids' => [$worker->id, $leader->id],
        ])->assertUnprocessable();
        $this->assertNull($worker->fresh()->department_id);
        $this->assertSame($source->id, $leader->fresh()->department_id);
    }

    public function test_cyclic_routing_rejects_the_entire_change(): void
    {
        $unit = Department::create(['name' => 'چرخه']);
        $leader = User::factory()->create();
        $worker = User::factory()->create();
        $leader->update(['manager_id' => $worker->id]);
        $this->actingAs($this->admin())->patchJson(route('admin.organization.departments.update', $unit), [
            'member_ids' => [$leader->id, $worker->id], 'supervisor_id' => $leader->id,
            'use_unit_routing' => false,
        ])->assertUnprocessable();
        $this->assertNull($worker->fresh()->department_id);
        $this->assertNull($unit->fresh()->supervisor_id);
    }

    public function test_non_admin_cannot_bulk_assign_members(): void
    {
        $unit = Department::create(['name' => 'محرمانه']);
        $person = User::factory()->create();
        $this->actingAs($person)->patchJson(route('admin.organization.departments.update', $unit), [
            'member_ids' => [$person->id],
        ])->assertForbidden();
        $this->assertNull($person->fresh()->department_id);
    }

    public function test_stale_membership_preview_cannot_overwrite_new_members(): void
    {
        $unit = Department::create(['name' => 'هم‌زمان']);
        $member = User::factory()->create(['department_id' => $unit->id]);
        $this->actingAs($this->admin())->patchJson(route('admin.organization.departments.update', $unit), [
            'member_ids' => [], 'expected_member_ids' => [],
        ])->assertConflict();
        $this->assertSame($unit->id, $member->fresh()->department_id);
    }

    public function test_new_unit_can_be_created_with_members_and_a_manager(): void
    {
        $manager = User::factory()->create();
        $this->actingAs($this->admin())->postJson(route('admin.organization.departments.store'), [
            'name' => 'واحد جدید', 'member_ids' => [$manager->id], 'manager_id' => $manager->id,
        ])->assertCreated();
        $unit = Department::where('name', 'واحد جدید')->firstOrFail();
        $this->assertSame($unit->id, $manager->fresh()->department_id);
        $this->assertSame('department_manager', $manager->fresh()->org_position);
    }

    public function test_company_managers_are_independent_of_units_and_have_no_fixed_count(): void
    {
        $this->actingAs($this->admin());
        foreach (User::factory()->count(4)->create() as $person) {
            $this->patchJson(route('admin.organization.personnel.update', $person), [
                'org_position' => 'executive_manager',
            ])->assertOk();
            $this->assertNull($person->fresh()->department_id);
            $this->assertSame('executive_manager', $person->fresh()->org_position);
            $this->assertCount(0, $person->fresh()->roles);
        }
    }
}
