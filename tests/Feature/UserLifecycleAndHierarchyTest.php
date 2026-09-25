<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIfBlocked;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Customer;
use App\Models\User;
use App\Rules\EligibleManager;
use App\Services\UserAccountService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserLifecycleAndHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        // this test rebuilds a private schema, so later RefreshDatabase tests must re-run migrations
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_archiving_user_preserves_customers_and_historical_marketer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Customer', 'phone' => '09120000001', 'user_id' => $user->id]);

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'user_id' => $user->id]);
        $this->assertTrue($customer->fresh()->marketer->is($user));
    }

    public function test_inactive_user_is_logged_out_on_the_next_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        [$request, $response] = $this->runMiddleware(new EnsureUserIsActive, $user);

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertGuest();
        $this->assertFalse($request->hasSession() && $request->session()->isStarted());
    }

    public function test_temporarily_blocked_user_is_logged_out(): void
    {
        $user = User::factory()->create(['blocked_until' => now()->addHour()]);

        [, $response] = $this->runMiddleware(new CheckIfBlocked, $user);

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertGuest();
    }

    public function test_account_service_saves_roles_and_manager_atomically(): void
    {
        Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        Role::create(['name' => 'Marketer', 'guard_name' => 'web']);
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $user = app(UserAccountService::class)->create([
            'name' => 'Marketer', 'phone' => '09120000002', 'password' => 'password',
            'manager_id' => $manager->id,
        ], ['Marketer']);

        $this->assertTrue($user->manager->is($manager));
        $this->assertTrue($user->hasRole('Marketer'));
    }

    public function test_user_cannot_be_their_own_manager(): void
    {
        Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Manager');

        $validator = Validator::make(['manager_id' => $user->id], [
            'manager_id' => [new EligibleManager($user)],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_management_cycles_are_rejected(): void
    {
        Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $parent = User::factory()->create();
        $parent->assignRole('Manager');
        $child = User::factory()->create(['manager_id' => $parent->id]);
        $child->assignRole('Manager');

        $validator = Validator::make(['manager_id' => $child->id], [
            'manager_id' => [new EligibleManager($parent)],
        ]);

        $this->assertTrue($validator->fails());
    }

    private function runMiddleware(object $middleware, User $user): array
    {
        $request = Request::create('/private');
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));
        Auth::login($user);
        $response = $middleware->handle($request, fn () => response('ok'));

        return [$request, $response];
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manager_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('blocked_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();
            $table->foreignId('deactivated_by')->nullable();
            $table->boolean('force_password_reset')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_number')->nullable();
            $table->string('name');
            $table->string('phone')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }
}
