<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ErpIntegrationTest extends TestCase
{
    private const TOKEN = 'test-only-sync-credential';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
        config()->set('services.external_sync.token', self::TOKEN);
        config()->set('services.erp.sync_rate_limit', 60);
        config()->set('services.erp.sync_audit_enabled', false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_authentication_error_contracts(): void
    {
        $this->getJson('/api/integrations/erp/users')->assertStatus(401)->assertExactJson([
            'error' => ['code' => 'unauthenticated', 'message' => 'Authentication is required.'],
        ]);
        $this->withToken('wrong')->getJson('/api/integrations/erp/users')->assertStatus(401);
        config()->set('services.external_sync.token');
        $this->withToken(self::TOKEN)->getJson('/api/integrations/erp/users')->assertStatus(500)->assertJsonPath('error.code', 'sync_token_not_configured');
    }

    public function test_cursor_contract_is_stable_and_safe(): void
    {
        $manager = $this->user(['name' => 'Manager']);
        $first = $this->user(['manager_id' => $manager->id, 'password' => 'hash', 'remember_token' => 'remember']);
        $second = $this->user();
        Role::create(['name' => 'Marketer', 'guard_name' => 'web']);
        $first->assignRole('Marketer');

        $response = $this->withToken(self::TOKEN)->getJson('/api/integrations/erp/users?cursor='.$manager->id.'&limit=1');
        $response->assertOk()
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.manager_id', $manager->id)
            ->assertJsonPath('data.0.roles.0', 'Marketer')
            ->assertJsonPath('data.0.is_seller', true)
            ->assertJsonPath('next_cursor', $first->id)
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('meta.schema_version', 1);
        $json = strtolower($response->getContent());
        foreach (['password', 'password_hash', 'remember_token', 'secret', 'token'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_empty_page_keeps_cursor_and_limit_is_validated(): void
    {
        $this->withToken(self::TOKEN)->getJson('/api/integrations/erp/users?cursor=99')
            ->assertOk()->assertJsonPath('next_cursor', 99)->assertJsonPath('has_more', false);
        $this->withToken(self::TOKEN)->getJson('/api/integrations/erp/users?limit=501')->assertStatus(422);
    }

    public function test_inactive_users_are_included_by_default_and_can_be_filtered(): void
    {
        $blocked = $this->user(['blocked_until' => now()->addDay()]);
        $this->withToken(self::TOKEN)->getJson('/api/integrations/erp/users')
            ->assertOk()->assertJsonPath('data.0.id', $blocked->id)->assertJsonPath('data.0.is_active', false);
        $this->withToken(self::TOKEN)->getJson('/api/integrations/erp/users?include_inactive=false')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_erp_access_requires_enabled_configuration_and_an_allowed_role(): void
    {
        $user = $this->user();
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $user->assignRole('Admin');
        config()->set('services.erp.enabled', true);
        config()->set('services.erp.access_roles', []);
        $this->assertFalse($user->canAccessErp());
        config()->set('services.erp.access_roles', ['Admin']);
        $this->assertTrue($user->canAccessErp());
    }

    public function test_legacy_sync_remains_compatible_and_safe(): void
    {
        $this->user(['password' => 'hash', 'remember_token' => 'remember']);
        Log::spy();
        $response = $this->withToken(self::TOKEN)->getJson('/api/external/users');
        $response->assertOk()->assertJsonPath('message', 'Users synced successfully.')->assertJsonStructure(['users' => ['data']]);
        $this->assertStringNotContainsString('password', strtolower($response->getContent()));
        $this->assertStringNotContainsString('remember_token', strtolower($response->getContent()));
    }

    public function test_launch_redirect_uses_only_configuration(): void
    {
        $user = $this->user();
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $user->assignRole('Admin');
        config()->set('services.erp.enabled', true);
        config()->set('services.erp.access_roles', ['Admin']);
        config()->set('services.erp.launch_url', 'https://inv.ariyajanebi.ir/sso');
        $this->actingAs($user)->get('/launch/erp?url=https://evil.example')->assertRedirect('https://inv.ariyajanebi.ir/sso');
    }

    public function test_launch_denies_unauthorized_users_and_legacy_token_is_disabled(): void
    {
        config()->set('services.erp.enabled', true);
        config()->set('services.erp.access_roles', ['Admin']);
        $this->actingAs($this->user())->get('/launch/erp')->assertForbidden();
        config()->set('services.legacy_client_token.enabled', false);
        $this->postJson('/api/token-for-client', ['phone' => '09120000000', 'secret' => 'not-real'])->assertStatus(410);
    }

    public function test_dashboard_source_has_no_old_ip_or_phone_auto_login(): void
    {
        $source = file_get_contents(resource_path('views/dashboard.blade.php'));
        $this->assertStringNotContainsString('192.168.1.207', $source);
        $this->assertStringNotContainsString('auto-login', $source);
        $this->assertStringContainsString("route('erp.launch')", $source);
    }

    private function user(array $attributes = []): User
    {
        return User::unguarded(fn () => User::query()->create(array_merge([
            'name' => 'User', 'phone' => '09'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => null, 'password' => 'hash', 'manager_id' => null,
        ], $attributes)));
    }

    private function createTables(): void
    {
        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('phone')->unique(); $table->string('email')->nullable();
            $table->string('password'); $table->rememberToken(); $table->foreignId('manager_id')->nullable();
            $table->timestamp('blocked_until')->nullable(); $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id'); $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
