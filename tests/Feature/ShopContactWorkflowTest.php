<?php

namespace Tests\Feature;

use App\Models\ShopContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopContactWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role): User
    {
        Role::findOrCreate($role, 'web');

        return User::factory()->create()->assignRole($role);
    }

    public function test_simple_form_normalizes_persian_phone_and_defaults_optional_choices(): void
    {
        $marketer = $this->person('Marketer');
        $data = ['city' => 'تهران', 'shop_name' => 'ثبت آسان', 'address' => 'خیابان آزادی',
            'owner_phone' => '۰۹۱۲ ۱۲۳ ۴۵۶۷', 'customer_type' => 'accessories', 'visit_result' => 'unknown', 'lat' => 35.7, 'lng' => 51.4];
        $this->actingAs($marketer)->post('/field-visits/quick', $data)->assertSessionHasNoErrors()->assertRedirect('/field-visits');
        $this->assertDatabaseHas('shop_contacts', ['user_id' => $marketer->id, 'owner_phone' => '09121234567',
            'relation_type' => 'مرتبط', 'cooperation_interest' => 'نیاز به مذاکره دارد']);
        $this->post('/field-visits/quick', array_merge($data, ['owner_phone' => 'شماره غلط']))->assertSessionHasErrors('owner_phone');
        $this->post('/field-visits/quick', array_merge($data, ['lat' => null]))->assertSessionHasErrors('lat');
        $this->assertSame(1, ShopContact::count());
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'city' => 'تهران', 'relation_type' => 'غیر مرتبط', 'address' => 'خیابان آزادی',
            'shop_name' => 'فروشگاه نمونه', 'owner_name' => 'مالک نمونه', 'owner_phone' => '09121234567',
            'lat' => 35.7, 'lng' => 51.4, 'cooperation_interest' => 'دارد',
            'customer_type' => 'other', 'visit_result' => 'interested', 'nr_activity' => 'سوپرمارکت', 'nr_goods' => ['کابل'], 'follow_up_status' => 'new',
        ], $extra);
    }

    private function contact(?User $user, array $extra = []): ShopContact
    {
        return ShopContact::create(array_merge($this->payload(), [
            'user_id' => $user?->id, 'visitor_name' => $user?->name ?? 'بازدیدکننده قدیمی',
        ], $extra));
    }

    public function test_submission_uses_authenticated_owner_and_requires_coordinates(): void
    {
        $marketer = $this->person('Marketer');
        $other = $this->person('Marketer');
        $this->actingAs($marketer)->post('/field-visits', $this->payload([
            'user_id' => $other->id, 'visitor_name' => 'جعلی',
        ]))->assertSessionHasNoErrors()->assertRedirect('/field-visits');
        $this->assertDatabaseHas('shop_contacts', ['user_id' => $marketer->id, 'visitor_name' => $marketer->name]);
        $this->actingAs($marketer)->post('/field-visits', $this->payload(['lat' => null]))->assertSessionHasErrors('lat');
        $this->assertSame(1, ShopContact::count());
    }

    public function test_marketer_cannot_read_edit_delete_or_export_someone_elses_record(): void
    {
        $one = $this->person('Marketer');
        $two = $this->person('Marketer');
        $mine = $this->contact($one, ['shop_name' => 'مجاز']);
        $theirs = $this->contact($two, ['shop_name' => 'محرمانه']);
        $this->contact(null, ['shop_name' => 'قدیمی بدون مالک']);
        $this->actingAs($one)->get('/field-visits')->assertOk()->assertSee('مجاز')->assertDontSee('محرمانه')->assertDontSee('قدیمی بدون مالک');
        $this->get('/field-visits/'.$theirs->id)->assertForbidden();
        $this->get('/admin/contacts/'.$theirs->id.'/edit')->assertForbidden();
        $this->put('/admin/contacts/'.$theirs->id, $this->payload())->assertForbidden();
        $this->delete('/admin/contacts/'.$theirs->id)->assertForbidden();
        $csv = $this->get('/admin/contacts/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('مجاز', $csv);
        $this->assertStringNotContainsString('محرمانه', $csv);
        $this->get('/field-visits/'.$mine->id)->assertOk();
    }

    public function test_manager_sees_only_team_and_global_roles_see_legacy_records(): void
    {
        $manager = $this->person('Manager');
        $team = $this->person('Marketer');
        $team->update(['manager_id' => $manager->id]);
        $outsider = $this->person('Marketer');
        $this->contact($team, ['shop_name' => 'تیم من']);
        $this->contact($outsider, ['shop_name' => 'تیم دیگر']);
        $this->contact(null, ['shop_name' => 'ثبت قدیمی']);
        $this->actingAs($manager)->get('/field-visits')->assertOk()->assertSee('تیم من')->assertDontSee('تیم دیگر')->assertDontSee('ثبت قدیمی');
        foreach (['Admin', 'Owner', 'Sales'] as $role) {
            $this->actingAs($this->person($role))->get('/field-visits')->assertOk()->assertSee('تیم دیگر')->assertSee('ثبت قدیمی');
        }
    }

    public function test_filters_statistics_and_export_use_the_same_scope(): void
    {
        $admin = $this->person('Admin');
        $one = $this->person('Marketer');
        $two = $this->person('Marketer');
        $this->contact($one, ['shop_name' => '=SUM(1,2)', 'city' => 'شیراز', 'next_follow_up_at' => '2026-10-09']);
        $this->contact($two, ['shop_name' => 'خارج فیلتر', 'city' => 'تهران']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 12:00:00'));
        $query = '?city='.urlencode('شیراز').'&user_id='.$one->id;
        $this->actingAs($admin)->get('/field-visits'.$query)->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 1 && $stats['overdue'] === 1)
            ->assertDontSee('خارج فیلتر');
        $csv = $this->get('/field-visits/export'.$query)->assertOk()->streamedContent();
        $this->assertStringContainsString("'=SUM", $csv);
        $this->assertStringNotContainsString('خارج فیلتر', $csv);
    }

    public function test_legacy_routes_require_auth_and_cannot_impersonate_a_visitor(): void
    {
        foreach (['/c', '/admin/contacts/export', '/contact/999', '/field-visits'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->post('/contact/999', $this->payload())->assertRedirect('/login');
        $this->actingAs($this->person('User'))->get('/field-visits')->assertForbidden();
        $marketer = $this->person('Marketer');
        $this->actingAs($marketer)->post('/contact/999', $this->payload())->assertRedirect('/field-visits');
        $this->assertDatabaseHas('shop_contacts', ['user_id' => $marketer->id, 'visitor_name' => $marketer->name]);
    }

    public function test_follow_up_and_edit_preserve_owner_and_validate_data(): void
    {
        $marketer = $this->person('Marketer');
        $contact = $this->contact($marketer);
        $this->actingAs($marketer)->patch('/field-visits/'.$contact->id.'/follow-up', [
            'follow_up_status' => 'negotiating', 'next_follow_up_at' => '2026-10-15', 'follow_up_note' => 'تماس مجدد',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shop_contacts', ['id' => $contact->id, 'follow_up_status' => 'negotiating']);
        $this->put('/admin/contacts/'.$contact->id, $this->payload(['user_id' => 999, 'lat' => 100]))->assertSessionHasErrors('lat');
        $this->put('/admin/contacts/'.$contact->id, $this->payload(['shop_name' => 'ویرایش شد', 'user_id' => 999]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shop_contacts', ['id' => $contact->id, 'user_id' => $marketer->id, 'shop_name' => 'ویرایش شد']);
        $this->actingAs($marketer)->delete('/admin/contacts/'.$contact->id)->assertForbidden();
    }

    public function test_dashboard_exposes_actions_only_to_eligible_roles(): void
    {
        $this->actingAs($this->person('Marketer'))->get('/dashboard')->assertOk()
            ->assertSee(route('field-visits.create'), false)->assertSee(route('field-visits.index'), false);
        $this->actingAs($this->person('Manager'))->get('/dashboard')->assertOk()
            ->assertSee(route('field-visits.index'), false)->assertDontSee(route('field-visits.create'), false);
        $this->actingAs($this->person('User'))->get('/dashboard')->assertOk()
            ->assertDontSee(route('field-visits.index'), false)->assertDontSee(route('field-visits.create'), false);
    }

    public function test_field_visits_home_shortcuts_are_separate_from_dashboard_tabs(): void
    {
        foreach (['Marketer', 'Manager', 'Admin', 'Owner', 'Sales'] as $role) {
            $response = $this->actingAs($this->person($role))->get('/dashboard')->assertOk();
            $html = $response->getContent();
            $this->assertMatchesRegularExpression('/<section[^>]*id="field-visits-home"[\s\S]*?<\/section>/', $html);
            preg_match('/<section[^>]*id="field-visits-home"[\s\S]*?<\/section>/', $html, $matches);
            $section = $matches[0];
            $this->assertStringContainsString('href="'.route('field-visits.index').'"', $section);
            if ($role === 'Manager') {
                $this->assertStringNotContainsString('href="'.route('field-visits.create').'"', $section);
            } else {
                $this->assertStringContainsString('href="'.route('field-visits.create').'"', $section);
            }
            $this->assertStringNotContainsString('sd-link-item', $section);
        }
        $this->actingAs($this->person('User'))->get('/dashboard')->assertOk()->assertDontSee('id="field-visits-home"', false);
    }

    public function test_nested_team_and_filter_tampering_cannot_escape_manager_scope(): void
    {
        $manager = $this->person('Manager');
        $lead = $this->person('Manager');
        $lead->update(['manager_id' => $manager->id]);
        $member = $this->person('Marketer');
        $member->update(['manager_id' => $lead->id]);
        $outsider = $this->person('Marketer');
        $this->contact($member, ['shop_name' => 'زیرمجموعه غیرمستقیم']);
        $hidden = $this->contact($outsider, ['shop_name' => 'خارج تیم']);
        $this->actingAs($manager)->get('/field-visits')->assertOk()->assertSee('زیرمجموعه غیرمستقیم')->assertDontSee('خارج تیم');
        $this->get('/field-visits?user_id='.$outsider->id)->assertOk()->assertViewHas('stats', fn ($stats) => $stats['total'] === 0);
        $this->get('/field-visits/'.$hidden->id)->assertForbidden();
        $this->assertStringNotContainsString('خارج تیم', $this->get('/field-visits/export')->streamedContent());
    }

    public function test_report_map_covers_filtered_results_beyond_the_first_table_page(): void
    {
        $user = $this->person('Marketer');
        for ($i = 0; $i < 26; $i++) {
            $this->contact($user);
        }
        $this->actingAs($user)->get('/field-visits')->assertOk()->assertViewHas('mapPoints', fn ($points) => $points->count() === 26)
            ->assertViewHas('contacts', fn ($contacts) => $contacts->count() === 25);
        $this->get('/field-visits?to=2026-12-31')->assertOk();
    }

    public function test_marketer_sees_simple_form_and_can_register_without_the_detailed_questionnaire(): void
    {
        $user = $this->person('Marketer');
        $this->actingAs($user)->get('/field-visits/create')->assertOk()->assertViewIs('field-visits.quick-form');
        $this->get('/field-visits/create?detailed=1')->assertOk()->assertViewIs('field-visits.form');
        $this->post('/field-visits/quick', [
            'city' => 'تهران', 'address' => 'خیابان آزادی', 'shop_name' => 'ثبت ساده ویزیتور',
            'owner_phone' => '09121234567', 'lat' => 35.7, 'lng' => 51.4,
            'relation_type' => 'مرتبط', 'cooperation_interest' => 'نیاز به مذاکره دارد', 'customer_type' => 'accessories', 'visit_result' => 'unknown',
            'user_id' => 999, 'visitor_name' => 'جعلی',
        ])->assertSessionHasNoErrors()->assertRedirect('/field-visits');
        $this->assertDatabaseHas('shop_contacts', ['user_id' => $user->id, 'visitor_name' => $user->name, 'shop_name' => 'ثبت ساده ویزیتور', 'activity_field' => null]);
        $this->post('/field-visits/quick', ['shop_name' => 'فاقد موقعیت'])->assertSessionHasErrors('lat');
    }

    public function test_simple_edit_preserves_details_and_rejects_other_owners(): void
    {
        $user = $this->person('Marketer');
        $other = $this->person('Marketer');
        $contact = $this->contact($user);
        $data = $this->payload(['shop_name' => 'ویرایش ساده', 'user_id' => $other->id]);
        unset($data['nr_activity'], $data['nr_goods']);
        $this->actingAs($user)->put('/field-visits/'.$contact->id.'/quick', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shop_contacts', ['id' => $contact->id, 'user_id' => $user->id, 'nr_activity' => 'سوپرمارکت', 'shop_name' => 'ویرایش ساده']);
        $this->actingAs($other)->put('/field-visits/'.$contact->id.'/quick', $data)->assertForbidden();
        $this->actingAs($this->person('User'))->post('/field-visits/quick', $data)->assertForbidden();
    }
}
