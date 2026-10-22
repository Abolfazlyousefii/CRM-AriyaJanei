<?php

namespace Tests\Feature;

use App\Models\ShopContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WholesaleFieldVisitTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role = 'Marketer'): User
    {
        Role::findOrCreate($role, 'web');

        return User::factory()->create()->assignRole($role);
    }

    private function data(array $extra = []): array
    {
        return array_merge(['shop_name' => 'عمده تست', 'owner_phone' => '۰۹۱۲۱۲۳۴۵۶۷', 'lat' => 35.71, 'lng' => 51.42,
            'customer_type' => 'accessories', 'visit_result' => 'price_list'], $extra);
    }

    public function test_wholesale_profile_can_be_saved_without_typing_city_or_address(): void
    {
        $user = $this->person();
        $this->actingAs($user)->post('/field-visits/quick', $this->data(['buyer_name' => 'کارشناس خرید نمونه',
            'requested_products' => ['cable', 'charger'], 'purchase_volume' => '50_100', 'purchase_cycle' => 'weekly',
            'preferred_payment' => 'cheque', 'purchase_priorities' => ['price', 'delivery'], 'next_action' => 'send_prices']))
            ->assertSessionHasNoErrors()->assertRedirect('/field-visits');
        $c = ShopContact::firstOrFail();
        $this->assertSame('', $c->city);
        $this->assertSame('', $c->address);
        $this->assertSame(['cable', 'charger'], $c->requested_products);
        $this->assertSame(['price', 'delivery'], $c->purchase_priorities);
        $this->assertSame($user->id, $c->user_id);
        $this->assertSame('کارشناس خرید نمونه', $c->buyer_name);
        $this->assertSame('09121234567', $c->owner_phone);
        $this->get('/field-visits/'.$c->id)->assertOk()->assertSee('کارشناس خرید نمونه')->assertSee('کابل')->assertSee('۵۰ تا ۱۰۰ میلیون تومان');
        $this->get('/field-visits/'.$c->id.'/edit')->assertOk()->assertSee('send_prices');
        $this->put('/field-visits/'.$c->id, ['shop_name' => 'incomplete'])->assertSessionHasErrors();
        $this->assertSame(['cable', 'charger'], $c->fresh()->requested_products);
        $this->put('/field-visits/'.$c->id.'/quick', $this->data(['requested_products' => null, 'purchase_priorities' => null]))->assertSessionHasNoErrors();
        $this->assertSame([], $c->fresh()->requested_products);
        $this->assertSame([], $c->fresh()->purchase_priorities);
    }

    public function test_wholesale_values_are_validated_and_ownership_is_protected(): void
    {
        $one = $this->person();
        $two = $this->person();
        $this->actingAs($one)->post('/field-visits/quick', $this->data(['user_id' => $two->id, 'purchase_volume' => 'invented',
            'requested_products' => ['invalid']]))->assertSessionHasErrors(['purchase_volume', 'requested_products.0']);
        $this->post('/field-visits/quick', $this->data(['customer_type' => null, 'visit_result' => null]))->assertSessionHasErrors(['customer_type', 'visit_result']);
        $this->post('/field-visits/quick', $this->data(['requested_products' => ['cable']]))->assertSessionHasNoErrors();
        $c = ShopContact::firstOrFail();
        $this->actingAs($two)->put('/field-visits/'.$c->id.'/quick', $this->data())->assertForbidden();
        $this->get('/field-visits/'.$c->id)->assertForbidden();
    }

    public function test_wholesale_filters_statistics_and_csv_share_the_same_owner_scope(): void
    {
        $one = $this->person();
        $two = $this->person();
        $this->actingAs($one)->post('/field-visits/quick', $this->data(['requested_products' => ['cable'], 'purchase_volume' => '50_100']))->assertSessionHasNoErrors();
        $this->actingAs($two)->post('/field-visits/quick', $this->data(['shop_name' => 'محرمانه', 'requested_products' => ['cable'], 'purchase_volume' => 'over_300']))->assertSessionHasNoErrors();
        $url = '/field-visits?requested_product=cable&visit_result=price_list';
        $this->actingAs($one)->get($url)->assertOk()->assertViewHas('stats', fn ($s) => $s['total'] === 1)
            ->assertViewHas('productDemand', fn ($d) => $d['cable'] === 1)->assertDontSee('محرمانه');
        $csv = $this->get('/field-visits/export?purchase_volume=50_100')->assertOk()->streamedContent();
        $this->assertStringContainsString('کابل', $csv);
        $this->assertStringNotContainsString('محرمانه', $csv);
        $this->actingAs($this->person('Admin'))->get($url)->assertOk()->assertViewHas('stats', fn ($s) => $s['total'] === 2);
    }

    public function test_reverse_location_requires_access_and_caches_provider_response(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['address' => ['city' => 'تهران', 'road' => 'خیابان نمونه', 'house_number' => '۱۲'], 'display_name' => 'تهران، خیابان نمونه'])]);
        $url = '/field-visits/location?lat=35.71&lng=51.42';
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->person('User'))->getJson($url)->assertForbidden();
        $this->actingAs($this->person())->getJson($url)->assertOk()->assertJsonPath('city', 'تهران')->assertJsonPath('available', true);
        $this->getJson($url)->assertOk()->assertJsonPath('city', 'تهران');
        Http::assertSentCount(1);
        $this->getJson('/field-visits/location?lat=35.73&lng=51.43')->assertOk()->assertJsonPath('available', false);
        Http::assertSentCount(1);
        $this->getJson('/field-visits/location?lat=91&lng=51')->assertUnprocessable();
    }

    public function test_reverse_failure_does_not_make_manual_address_mandatory(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $this->actingAs($this->person())->getJson('/field-visits/location?lat=35.72&lng=51.44')->assertOk()->assertJsonPath('available', false);
        $this->post('/field-visits/quick', $this->data())->assertSessionHasNoErrors();
    }
}
