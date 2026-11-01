<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardLeaveAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_executive_can_find_leave_management_on_dashboard(): void
    {
        Role::findOrCreate('Admin', 'web');
        $executive = User::factory()->create(['org_position' => 'executive_manager']);
        $executive->syncRoles(['Admin']);

        $this->actingAs($executive)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('leaves').'"', false)
            ->assertSee('مدیریت مرخصی‌ها');

        $this->get(route('leaves'))->assertOk();
    }

    public function test_employee_with_custom_role_can_find_their_leave_page(): void
    {
        Role::findOrCreate('ITUser', 'web');
        $employee = User::factory()->create();
        $employee->syncRoles(['ITUser']);

        $this->actingAs($employee)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('leaves').'"', false)
            ->assertSee('مرخصی‌های من');

        $this->get(route('leaves'))->assertOk();
    }
}
