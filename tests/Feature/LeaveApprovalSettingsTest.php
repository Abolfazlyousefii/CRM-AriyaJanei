<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveApprovalService;
use Hekmatinasser\Verta\Verta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveApprovalSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role = 'User'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function select(User ...$users): void
    {
        DB::table('leave_approvers')->insert(array_map(fn ($user) => ['user_id' => $user->id], $users));
    }

    private function leave(User $owner, string $status = 'pending', array $extra = []): Leave
    {
        return Leave::create(array_merge([
            'user_id' => $owner->id, 'leave_type' => 'استحقاقی', 'leave_unit' => 'روزانه',
            'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(),
            'start_time' => '08:00', 'end_time' => '16:00', 'status' => $status,
        ], $extra));
    }

    public function test_daily_and_hourly_requests_accept_past_today_tomorrow_and_long_periods(): void
    {
        $owner = $this->person();
        $approver = $this->person();
        $this->select($approver);
        foreach (['روزانه', 'ساعتی'] as $unit) {
            foreach ([-30, 0, 1, 400] as $offset) {
                $date = Verta::instance(now()->addDays($offset))->format('Y/m/d');
                $this->actingAs($owner)->post(route('leaves.store'), [
                    'leave_type' => 'استحقاقی', 'leave_unit' => $unit,
                    'start_date' => $date, 'end_date' => Verta::instance(now()->addDays($offset + 90))->format('Y/m/d'),
                    'start_time' => '08:00', 'end_time' => '16:00',
                ])->assertSessionHasNoErrors()->assertRedirect(route('leaves'));
            }
        }
        $this->assertSame(8, Leave::count());
        $this->assertSame(8, Leave::where('status', 'pending')->count());
        $this->assertDatabaseHas('notifications', ['user_id' => $approver->id, 'title' => 'درخواست مرخصی برای بررسی']);
        $this->actingAs($owner)->get(route('leaves.create'))->assertOk()->assertDontSee('data-jdp-min-date', false);
    }

    public function test_only_selected_people_can_decide_even_when_old_manager_substitute_and_admin_exist(): void
    {
        $owner = $this->person();
        $manager = $this->person('Manager');
        $substitute = $this->person();
        $admin = $this->person('Admin');
        $first = $this->person();
        $second = $this->person();
        $this->select($first, $second);
        $leave = $this->leave($owner, 'pending', ['manager_id' => $manager->id, 'substitute_user_id' => $substitute->id]);
        foreach ([$manager, $substitute, $admin, $owner] as $person) {
            $this->actingAs($person)->patch(route('leaves.approve', $leave))->assertForbidden();
            $this->patch(route('leaves.reject', $leave))->assertForbidden();
        }
        $this->actingAs($second)->get(route('leaves'))->assertOk()->assertSee(route('leaves.approve', $leave), false);
        $this->patch(route('leaves.approve', $leave))->assertRedirect();
        $this->assertDatabaseHas('leaves', ['id' => $leave->id, 'status' => 'final_approved', 'super_manager_id' => $second->id]);
        $this->actingAs($first)->patch(route('leaves.reject', $leave))->assertForbidden();
    }

    public function test_selected_approver_can_reject_legacy_pending_requests(): void
    {
        $owner = $this->person();
        $approver = $this->person();
        $this->select($approver);
        foreach (['pending', 'manager_approved', 'accounting_approved'] as $status) {
            $leave = $this->leave($owner, $status);
            $this->actingAs($approver)->patch(route('leaves.reject', $leave))->assertRedirect();
            $this->assertSame('final_rejected', $leave->fresh()->status);
        }
    }

    public function test_admin_can_change_approvers_and_reroute_existing_pending_requests(): void
    {
        $admin = $this->person('Admin');
        $old = $this->person();
        $first = $this->person();
        $second = $this->person();
        $owner = $this->person();
        $this->select($old);
        $leave = $this->leave($owner, 'manager_approved');
        $this->actingAs($admin)->get(route('admin.leaves.settings.edit'))->assertOk()->assertSee($first->name);
        $this->put(route('admin.leaves.settings.update'), ['approver_ids' => [$first->id, $second->id]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseMissing('leave_approvers', ['user_id' => $old->id]);
        foreach ([$first, $second] as $person) {
            $this->assertDatabaseHas('notifications', ['user_id' => $person->id, 'leave_id' => $leave->id]);
        }
        $this->actingAs($old)->patch(route('leaves.approve', $leave))->assertForbidden();
        $this->actingAs($first)->patch(route('leaves.approve', $leave))->assertRedirect();
    }

    public function test_settings_require_admin_and_at_least_one_valid_active_person(): void
    {
        $ordinary = $this->person();
        $this->actingAs($ordinary)->get(route('admin.leaves.settings.edit'))->assertForbidden();
        $this->put(route('admin.leaves.settings.update'), ['approver_ids' => [$ordinary->id]])->assertForbidden();
        $admin = $this->person('Admin');
        $ordinary->update(['is_active' => false]);
        $this->actingAs($admin)->put(route('admin.leaves.settings.update'), ['approver_ids' => []])->assertSessionHasErrors('approver_ids');
        $this->put(route('admin.leaves.settings.update'), ['approver_ids' => [$ordinary->id]])->assertSessionHasErrors('approver_ids.0');
        $this->put(route('admin.leaves.settings.update'), ['approver_ids' => [999999]])->assertSessionHasErrors('approver_ids.0');
    }

    public function test_no_selection_does_not_grant_approval_to_admin_and_closed_leaves_cannot_be_reopened(): void
    {
        $admin = $this->person('Admin');
        $owner = $this->person();
        $leave = $this->leave($owner);
        $this->actingAs($admin)->patch(route('leaves.approve', $leave))->assertForbidden();
        $this->select($admin);
        $leave->update(['status' => 'final_approved']);
        $this->assertFalse($owner->can('update', $leave));
        $this->actingAs($owner)->delete(route('leaves.destroy', $leave))->assertForbidden();
        $this->assertFalse(app(LeaveApprovalService::class)->canDecide($admin, $leave));
    }
}
