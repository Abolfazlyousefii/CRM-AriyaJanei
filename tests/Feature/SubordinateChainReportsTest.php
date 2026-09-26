<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubordinateChainReportsTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = 1001;
    private const INTERNAL = 1002;
    private const SUPERVISOR = 1003;
    private const EMPLOYEE = 1004;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Admin', 'Owner', 'InternalManager', 'Manager', 'User', 'Marketer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeUser(int $id, string|array $roles, ?int $managerId = null, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['id' => $id, 'manager_id' => $managerId], $overrides));
        $user->assignRole(...(array) $roles);

        return $user;
    }

    /**
     * Owner -> InternalManager -> Supervisor (Manager) -> Employee.
     * ReportController only admits Admin|Marketer|User|Manager, and the users tree is built from
     * the Manager role, so higher-level managers carry Manager alongside their own role.
     */
    private function buildChain(): void
    {
        $this->makeUser(self::OWNER, ['Owner', 'Manager']);
        $this->makeUser(self::INTERNAL, ['InternalManager', 'Manager'], self::OWNER);
        $this->makeUser(self::SUPERVISOR, 'Manager', self::INTERNAL);
        $this->makeUser(self::EMPLOYEE, 'User', self::SUPERVISOR);
    }

    private function submittedReport(int $userId): Report
    {
        return Report::create([
            'user_id' => $userId,
            'description' => 'daily report',
            'status' => Report::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function test_all_subordinate_ids_walks_the_whole_chain(): void
    {
        $this->buildChain();

        $sorted = fn (int $id) => collect(User::findOrFail($id)->allSubordinateIds())->sort()->values()->all();

        $this->assertSame([self::INTERNAL, self::SUPERVISOR, self::EMPLOYEE], $sorted(self::OWNER));
        $this->assertSame([self::SUPERVISOR, self::EMPLOYEE], $sorted(self::INTERNAL));
        $this->assertSame([self::EMPLOYEE], $sorted(self::SUPERVISOR));
        $this->assertSame([], $sorted(self::EMPLOYEE));
    }

    public function test_all_subordinate_ids_survives_a_management_cycle(): void
    {
        $a = $this->makeUser(1101, 'Manager');
        $b = $this->makeUser(1102, 'Manager', $a->id);
        $a->update(['manager_id' => $b->id]);

        $this->assertSame([$b->id], $a->fresh()->allSubordinateIds());
        $this->assertSame([$a->id], $b->fresh()->allSubordinateIds());
    }

    public function test_higher_level_manager_sees_reports_of_the_bottom_of_the_chain(): void
    {
        $this->buildChain();
        $report = $this->submittedReport(self::EMPLOYEE);
        $outsider = $this->makeUser(1201, 'User');
        $this->submittedReport($outsider->id);

        foreach ([self::OWNER, self::INTERNAL, self::SUPERVISOR] as $viewerId) {
            $response = $this->actingAs(User::findOrFail($viewerId))->get(route('user.reports.reportsManagment'));

            $response->assertOk();
            $this->assertSame([$report->id], $response->viewData('reports')->pluck('id')->all(), "viewer {$viewerId}");
            $this->assertTrue($response->viewData('availableUsers')->contains('id', self::EMPLOYEE));
            $this->assertFalse($response->viewData('availableUsers')->contains('id', $outsider->id));
        }
    }

    public function test_employee_does_not_see_the_reports_of_others(): void
    {
        $this->buildChain();
        $this->submittedReport(self::SUPERVISOR);

        $response = $this->actingAs(User::findOrFail(self::EMPLOYEE))->get(route('user.reports.reportsManagment'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('reports'));
    }

    public function test_direct_manager_result_is_unchanged_for_one_level(): void
    {
        $manager = $this->makeUser(1301, 'Manager');
        $direct = $this->makeUser(1302, 'User', $manager->id);
        $report = $this->submittedReport($direct->id);

        $response = $this->actingAs($manager)->get(route('user.reports.reportsManagment'));

        $this->assertSame([$report->id], $response->viewData('reports')->pluck('id')->all());
        $this->assertSame([$direct->id], $response->viewData('availableUsers')->pluck('id')->all());
    }

    public function test_inactive_users_are_hidden_from_non_admin_lists(): void
    {
        $this->buildChain();
        $inactive = $this->makeUser(1401, 'User', self::SUPERVISOR, ['is_active' => false]);

        $response = $this->actingAs(User::findOrFail(self::OWNER))->get(route('user.reports.reportsManagment'));

        foreach (['availableUsers', 'usersWithoutYesterdayReport'] as $key) {
            $this->assertFalse($response->viewData($key)->contains('id', $inactive->id), "{$key} must hide inactive users");
        }
        $this->assertTrue($response->viewData('usersWithoutYesterdayReport')->contains('id', self::EMPLOYEE));
    }

    public function test_inactive_users_are_hidden_from_admin_lists(): void
    {
        $admin = $this->makeUser(1501, 'Admin');
        $active = $this->makeUser(1502, 'User');
        $inactive = $this->makeUser(1503, 'User', null, ['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('user.reports.reportsManagment'));

        foreach (['availableUsers', 'usersWithoutYesterdayReport'] as $key) {
            $this->assertTrue($response->viewData($key)->contains('id', $active->id), "{$key} keeps active users");
            $this->assertFalse($response->viewData($key)->contains('id', $inactive->id), "{$key} must hide inactive users");
        }
    }

    public function test_departments_infrastructure_exists_and_is_seeded(): void
    {
        $this->assertSame(
            ['آیتی', 'انبار', 'حسابداری', 'فروش'],
            DB::table('departments')->pluck('name')->sort()->values()->all()
        );
        $this->assertTrue(Schema::hasColumn('users', 'department_id'));
        $this->assertNull(User::factory()->create()->fresh()->department_id);
    }
}
