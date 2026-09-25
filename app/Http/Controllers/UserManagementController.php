<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\EligibleManager;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(private readonly UserAccountService $accounts) {}

    public function index()
    {
        $roles = Role::query()->orderBy('name')->get();
        $managers = User::role('Manager')
            ->with(['roles', 'employees.roles', 'employees.manager'])
            ->orderBy('name')->get();

        $unassignedUsers = User::query()
            ->whereNull('manager_id')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Manager'))
            ->with('roles')
            ->orderBy('name')->get();

        return view('admin.users.index', compact('managers', 'roles', 'unassignedUsers'));
    }

    public function create(Request $request)
    {
        $preselected = Role::query()->where('guard_name', 'web')
            ->whereIn('name', (array) $request->query('role', []))->pluck('name')->all();

        return view('admin.users.create', array_merge($this->accessOptions(), ['selectedRoles' => $preselected]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge($this->identityRules(), $this->roleRules(), [
            'manager_id' => ['nullable', 'integer', new EligibleManager],
        ]));

        $this->accounts->create([
            'name' => $validated['name'], 'phone' => $validated['phone'],
            'password' => $validated['password'], 'manager_id' => $validated['manager_id'] ?? null,
        ], $validated['roles']);

        return redirect()->route('admin.users.index')->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    public function updateRoles(Request $request, User $user)
    {
        $roles = $request->validate($this->roleRules())['roles'];
        $this->ensureManagerKeepsManagerialRole($user, $roles);
        $this->accounts->syncRoles($user, $roles);

        return back()->with('success', 'نقش‌های کاربر با موفقیت به‌روزرسانی شد.');
    }

    public function createManager()
    {
        return view('admin.users.create-manager', $this->accessOptions());
    }

    public function storeManager(Request $request)
    {
        $validated = $request->validate(array_merge($this->identityRules(), $this->roleRules(), [
            'manager_id' => ['nullable', 'integer', new EligibleManager],
        ]));
        $this->ensureAtLeastOneManagerialRole($validated['roles']);

        $this->accounts->create([
            'name' => $validated['name'], 'phone' => $validated['phone'],
            'password' => $validated['password'], 'manager_id' => $validated['manager_id'] ?? null,
        ], $validated['roles']);

        return redirect()->route('admin.users.index')->with('success', 'مدیر با موفقیت ایجاد شد.');
    }

    public function editManager(User $manager)
    {
        return view('admin.users.edit-manager', array_merge(compact('manager'), $this->accessOptions($manager)));
    }

    public function updateManager(Request $request, User $manager)
    {
        $validated = $request->validate(array_merge($this->identityRules($manager), $this->roleRules(), [
            'manager_id' => ['nullable', 'integer', new EligibleManager($manager)],
        ]));
        $this->ensureAtLeastOneManagerialRole($validated['roles']);

        $this->accounts->update($manager, [
            'name' => $validated['name'], 'phone' => $validated['phone'],
            'manager_id' => $validated['manager_id'] ?? null,
        ], $validated['roles']);

        return redirect()->route('admin.users.index')->with('success', 'اطلاعات و دسترسی‌های مدیر به‌روزرسانی شد.');
    }

    public function destroyManager(User $manager)
    {
        abort_if($manager->is(auth()->user()), 422, 'امکان آرشیوکردن حساب خودتان وجود ندارد.');
        $manager->delete();

        return redirect()->route('admin.users.index')->with('success', 'مدیر آرشیو شد و اطلاعات مرتبط او محفوظ ماند.');
    }

    public function createEmployee(User $manager)
    {
        return view('admin.users.create-employee', array_merge(compact('manager'), $this->accessOptions()));
    }

    public function storeEmployee(Request $request, User $manager)
    {
        $validated = $request->validate(array_merge($this->identityRules(), $this->roleRules(), [
            'manager_id' => ['required', 'integer', new EligibleManager],
        ]));

        $this->accounts->create([
            'name' => $validated['name'], 'phone' => $validated['phone'],
            'password' => $validated['password'], 'manager_id' => $validated['manager_id'],
        ], $validated['roles']);

        return redirect()->route('admin.users.index')->with('success', 'کاربر با نقش‌ها و مدیر انتخاب‌شده ایجاد شد.');
    }

    public function editEmployee(User $employee)
    {
        return view('admin.users.edit-employee', array_merge(compact('employee'), $this->accessOptions($employee)));
    }

    public function updateEmployee(Request $request, User $employee)
    {
        $validated = $request->validate(array_merge($this->identityRules($employee), $this->roleRules(), [
            'manager_id' => ['nullable', 'integer', new EligibleManager($employee)],
        ]));
        $this->ensureManagerKeepsManagerialRole($employee, $validated['roles']);

        $this->accounts->update($employee, [
            'name' => $validated['name'], 'phone' => $validated['phone'],
            'manager_id' => $validated['manager_id'] ?? null,
        ], $validated['roles']);

        return redirect()->route('admin.users.index')->with('success', 'اطلاعات، نقش‌ها و مدیر مستقیم کاربر به‌روزرسانی شد.');
    }

    public function destroyEmployee(User $employee)
    {
        abort_if($employee->is(auth()->user()), 422, 'امکان آرشیوکردن حساب خودتان وجود ندارد.');
        $employee->delete();

        return redirect()->route('admin.users.index')->with('success', 'کاربر آرشیو شد و اطلاعات مرتبط او محفوظ ماند.');
    }

    public function resetPassword(int $id)
    {
        $user = User::findOrFail($id);
        $user->password = bcrypt('Ariya1404');
        $user->force_password_reset = true;
        $user->save();

        return back()->with('success', 'رمز عبور کاربر ریست شد و در ورود بعدی باید تغییر کند.');
    }

    public function change(Request $request)
    {
        $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user = $request->user();
        $user->password = $request->string('password');
        $user->force_password_reset = false;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'رمز عبور با موفقیت تغییر یافت.');
    }

    private function accessOptions(?User $subject = null): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(),
            'managers' => User::query()->eligibleManagers()
                ->when($subject, fn ($query) => $query->whereKeyNot($subject->id))
                ->orderBy('name')->get(),
        ];
    }

    private function identityRules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', Rule::unique('users', 'phone')->ignore($user?->id)],
            'password' => $user ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    private function roleRules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    private function ensureAtLeastOneManagerialRole(array $roles): void
    {
        if (! collect($roles)->contains(fn (string $role): bool => User::isManagerialRole($role))) {
            throw ValidationException::withMessages(['roles' => 'برای مدیر باید حداقل یک نقش مدیریتی انتخاب شود.']);
        }
    }

    private function ensureManagerKeepsManagerialRole(User $user, array $roles): void
    {
        if ($user->employees()->exists()) {
            $this->ensureAtLeastOneManagerialRole($roles);
        }
    }
}
