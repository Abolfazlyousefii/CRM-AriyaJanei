<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class LoginSessionRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }

    public function test_login_page_is_not_cached(): void
    {
        $this->get('/login')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_login_can_refresh_its_session_token_without_caching(): void
    {
        $response = $this->getJson('/login');
        $response->assertOk()->assertJsonStructure(['token'])->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(session()->token(), $response->json('token'));
    }

    public function test_stale_login_returns_to_a_fresh_form_without_flashing_password(): void
    {
        $this->post('/login', ['_token' => 'expired', 'phone' => '09000000000', 'password' => 'never-flash-this'])
            ->assertRedirect('/login')->assertSessionHas('status')->assertSessionHasInput('phone', '09000000000');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertArrayNotHasKey('_token', session()->getOldInput());
        $this->assertGuest();
    }

    public function test_json_login_and_other_forms_still_reject_invalid_csrf(): void
    {
        $this->postJson('/login', ['_token' => 'expired'])->assertStatus(419);
        $this->post('/register', ['_token' => 'expired'])->assertStatus(419);
        $this->assertGuest();
    }
}
