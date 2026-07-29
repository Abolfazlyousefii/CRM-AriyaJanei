<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // این خیلی مهم است

use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;




class UserManagementController extends Controller
{
    public function index()
    {
        $roles = Role::all(); // یا نام رول‌های شما

        $managers = User::role('Manager')->with('employees')->get();
        return view('admin.users.index', compact('managers', 'roles'));
    }

    public function updateRoles(Request $request, User $user)
    {
        $roles = $request->input('roles', []); // آرایه نقش‌های انتخاب شده
        $user->syncRoles($roles); // جایگزینی نقش‌ها
        $user->touch();
        return back()->with('success', 'نقش‌های کاربر به‌روزرسانی شد.');
    }


    // --- مدیر ---
    public function createManager()
    {
        return view('admin.users.create-manager');
    }

    public function storeManager(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^09\d{9}$/|unique:users,phone',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'manager_id' => "1", // اینجا درست مقداردهی میشه
        ]);

        $user->assignRole('Manager');
        $user->touch();

        return redirect()->route('admin.users.index')->with('success', 'مدیر با موفقیت ایجاد شد.');
    }

    public function editManager(User $manager)
    {
        return view('admin.users.edit-manager', compact('manager'));
    }
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $user->password = bcrypt('Ariya1404'); // پسورد دیفالت
        $user->force_password_reset = true;   // برای اجبار تغییر پسورد
        $user->save();

        return redirect()->back()->with('success', 'پسورد کاربر ریست شد و به Ariya1404 تغییر کرد.');
    }
 public function change(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

       $user = auth()->user();
$user->password = bcrypt($request->password);
$user->force_password_reset = false;
$user->save();


        return redirect()->route('dashboard')->with('success', 'پسورد با موفقیت تغییر یافت.');
    }
    public function updateManager(Request $request, User $manager)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'required','string','regex:/^09\d{9}$/',
                Rule::unique('users','phone')->ignore($manager->id),
            ],
        ]);

        $manager->update($request->only('name','phone'));

        return redirect()->route('admin.users.index')->with('success', 'اطلاعات مدیر بروزرسانی شد.');
    }

    public function destroyManager(User $manager)
    {
        $manager->delete();
        return redirect()->route('admin.users.index')->with('success', 'مدیر حذف شد.');
    }

    // --- کارمند ---
    public function createEmployee(User $manager)
    {
        return view('admin.users.create-employee', compact('manager'));
    }

    public function storeEmployee(Request $request, User $manager)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^09\d{9}$/|unique:users,phone',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'manager_id' => $manager->id, // اتصال به مدیر
        ]);

        $user->assignRole('User');
        $user->touch();

        return redirect()->route('admin.users.index')->with('success', 'کارمند با موفقیت ایجاد شد.');
    }

    public function editEmployee(User $employee)
    {
        return view('admin.users.edit-employee', compact('employee'));
    }

    public function updateEmployee(Request $request, User $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'required','string','regex:/^09\d{9}$/',
                Rule::unique('users','phone')->ignore($employee->id),
            ],
        ]);

        $employee->update($request->only('name','phone'));

        return redirect()->route('admin.users.index')->with('success', 'اطلاعات کارمند بروزرسانی شد.');
    }

    public function destroyEmployee(User $employee)
    {
        $employee->delete();
        return redirect()->route('admin.users.index')->with('success', 'کارمند حذف شد.');
    }
}
