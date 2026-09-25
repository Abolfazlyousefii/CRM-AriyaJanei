<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\EligibleManager;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketerController extends Controller
{
    public function __construct(private readonly UserAccountService $accounts)
    {
        $this->middleware('role:Admin|Marketer');
    }

    public function index()
    {
        $marketers = User::role('Marketer')->paginate(20);

        return view('admin.marketers.index', compact('marketers'));
    }

    public function create()
    {
        return redirect()->route('admin.users.create', ['role' => 'Marketer']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'password' => ['required', 'min:8', 'confirmed'],
            'manager_id' => ['nullable', 'integer', new EligibleManager],
        ], [
            'phone.regex' => 'فرمت شماره موبایل معتبر نیست. باید با 09 شروع شود و 11 رقم باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.confirmed' => 'رمز عبور و تکرار آن یکسان نیستند.',
        ]);

        $user = $this->accounts->create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => $request->password,
            'manager_id' => $request->integer('manager_id') ?: null,
        ], ['Marketer']);

        // لاگ ایجاد کاربر
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['action' => 'create'])
            ->log('ایجاد مارکتر جدید');

        return redirect()->route('admin.marketers.index')
            ->with('success', 'کاربر با موفقیت ثبت شد.');
    }

    public function edit(string $id)
    {
        return redirect()->route('admin.users.editEmployee', User::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $marketer = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'phone')->ignore($marketer->id),
            ],
            'manager_id' => ['nullable', 'integer', new EligibleManager($marketer)],
        ]);

        $oldData = $marketer->getOriginal();

        $this->accounts->update($marketer, [
            'name' => $request->name,
            'phone' => $request->phone,
            'manager_id' => $request->integer('manager_id') ?: null,
        ], $marketer->getRoleNames()->all());

        // لاگ ویرایش کاربر
        activity()
            ->causedBy(auth()->user())
            ->performedOn($marketer)
            ->withProperties(['old' => $oldData, 'new' => $marketer->toArray()])
            ->log('ویرایش اطلاعات مارکتر');

        return redirect()->route('admin.marketers.index')
            ->with('success', 'اطلاعات با موفقیت ویرایش شد.');
    }

    public function destroy(string $id)
    {
        $marketer = User::findOrFail($id);

        abort_if($marketer->is(auth()->user()), 422, 'امکان آرشیوکردن حساب خودتان وجود ندارد.');

        // لاگ حذف کاربر
        activity()
            ->causedBy(auth()->user())
            ->performedOn($marketer)
            ->withProperties(['action' => 'delete'])
            ->log('آرشیو مارکتر');

        $marketer->delete();

        return redirect()->route('admin.marketers.index')
            ->with('success', 'بازاریاب آرشیو شد و مشتریان و سوابق او محفوظ ماندند.');
    }
}
