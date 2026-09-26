# فاز ۲ از بازطراحی مدیریت پرسنل — سه‌تب: پرسنل / بازاریاب / مدیریت پرسنل

این فاز فقط UI و یک متد جدید کنترلره، روی همون جدول `users` و `departments` که در فاز ۱ ساختیم. به `LeaveController.php` و منطق داخلی `MarketerController` (store/update/destroy/index) و منطق tree موجود در `UserManagementController::index()` دست نزن — فقط موارد زیر رو انجام بده.

## 1. تب ناوبری مشترک

یک partial جدید بساز: `resources/views/admin/users/partials/section-tabs.blade.php` — سه لینک:
- «پرسنل» → `route('admin.personnel.index')`
- «بازاریاب» → `route('admin.marketers.index')`
- «مدیریت پرسنل» → `route('admin.users.index')`

تب فعال با کلاس active مشخص بشه (بر اساس `request()->routeIs(...)`). این partial رو بالای هر سه صفحه (`admin/personnel/index.blade.php` جدید، `admin/marketers/index.blade.php` موجود، `admin/users/index.blade.php` موجود) اضافه کن. **فقط همین یک خط `@include` رو به دو صفحه‌ی موجود اضافه کن، هیچ چیز دیگه‌ای توشون عوض نکن.**

عنوان صفحه‌ی `admin/users/index.blade.php` (همون درخت مدیریتی فعلی) رو از هرچی الان هست به «مدیریت پرسنل» تغییر بده (فقط متن heading، نه منطق).

## 2. متد و صفحه‌ی «پرسنل»

در `app/Http/Controllers/UserManagementController.php` یک متد اضافه کن:

```php
public function personnel(Request $request)
{
    $query = User::query()->where('is_active', true)->with(['roles', 'department', 'manager']);

    if ($request->filled('role')) {
        $query->whereHas('roles', fn ($q) => $q->where('name', $request->string('role')));
    }

    if ($request->filled('department_id')) {
        $query->where('department_id', $request->integer('department_id'));
    }

    $personnel = $query->orderBy('name')->paginate(30)->withQueryString();

    return view('admin.personnel.index', [
        'personnel' => $personnel,
        'roles' => Role::query()->orderBy('name')->get(),
        'departments' => Department::query()->orderBy('name')->get(),
        'selectedRole' => $request->string('role')->toString() ?: null,
        'selectedDepartmentId' => $request->integer('department_id') ?: null,
    ]);
}
```

(اگه رابطه‌ی `department()` روی مدل `User` هنوز اضافه نشده، یک `belongsTo(Department::class)` بهش اضافه کن. مدل `Department` رو هم اگه نیست بساز، خیلی ساده: `class Department extends Model { protected $fillable = ['name']; public function users() { return $this->hasMany(User::class); } }`.)

روت جدید در `routes/web.php` (کنار بقیه‌ی روت‌های `admin.users.*`، همون middleware گروه فعلی):
```php
Route::get('/personnel', [UserManagementController::class, 'personnel'])->name('personnel.index');
```
دقت کن اسم نهایی روت با توجه به prefix گروه باید `admin.personnel.index` بشه — همون الگویی که `admin.users.index` رو ساخته رو نگاه کن و دقیقاً همون‌جوری اضافه‌ش کن.

صفحه‌ی `resources/views/admin/personnel/index.blade.php` (جدید): یک جدول ساده — ستون‌های نام، شماره، نقش‌ها (badge)، دپارتمان، مدیر مستقیم، عملیات (ویرایش → `admin.users.editEmployee` یا `editManager` بسته به نقش، همون منطق تشخیصی که تو `index.blade.php` فعلی برای لینک‌دهی هست رو کپی نکن، ساده بگیر: اگه کاربر نقش مدیریتی داره برو `editManager` وگرنه `editEmployee`). بالای جدول: دکمه‌ی «افزودن کاربر» → `route('admin.users.create')`، و دو تا فیلتر ساده (select نقش، select دپارتمان) که با GET همون صفحه رو دوباره لود کنن.

## 3. دپارتمان در فرم افزودن/ویرایش کاربر

یک partial جدید: `resources/views/admin/users/partials/department-select.blade.php` — یک `<select name="department_id">` با option خالی «بدون دپارتمان» + لیست `$departments`، مقدار انتخاب‌شده از `old('department_id')` یا `$selectedDepartmentId ?? null`.

این partial رو به `admin/users/create.blade.php` (کنار `manager-select`/`role-selector` موجود) و به فرم‌های `edit-employee.blade.php`/`edit-manager.blade.php` اضافه کن. باید `$departments` از کنترلر به این ویوها پاس داده بشه — در `UserManagementController::accessOptions()` یک کلید `'departments' => Department::query()->orderBy('name')->get()` اضافه کن (این متد همین الان هم برای create/edit استفاده می‌شه، پس با این اضافه خودکار در همه جا میاد).

در `UserManagementController`:
- `identityRules()`: نیازی به تغییر نیست (department اختیاریه).
- به `store()`, `storeEmployee()`, `storeManager()`, `updateEmployee()`, `updateManager()` یک ولیدیشن اضافه کن: `'department_id' => ['nullable', 'integer', 'exists:departments,id']`, و مقدارش رو در آرایه‌ای که به `$this->accounts->create(...)`/`update(...)` پاس می‌دی اضافه کن (کلید `department_id`).

## 4. تست

- تست GET برای `admin.personnel.index`: چند کاربر با نقش/دپارتمان مختلف بساز، مطمئن شو صفحه ۲۰۰ می‌ده و لیست همه‌ی کاربران فعال رو داره (نه فقط زیرمجموعه‌ی یک مدیر — این صفحه برای Admin/مدیران بالا کل لیسته، مثل قبل middleware موجود کنترلر رو رعایت کن).
- تست فیلتر بر اساس `department_id` و `role`.
- تست اینکه ساخت کاربر جدید با `department_id` در دیتابیس ذخیره می‌شه.
- تست وجود هر سه لینک تب در هر سه صفحه (`admin.personnel.index`, `admin.marketers.index`, `admin.users.index`).
- کل `php artisan test` رو تنها (بدون اجرای هم‌زمان) بزن و خروجی کامل رو بفرست. انتظار: همون ۱۴ فیل قدیمی + هیچ فیل جدید.

## نکات مهم

- به `LeaveController.php`, منطق CRUD موجود `MarketerController` (store/update/destroy/index)، و منطق tree توی `UserManagementController::index()` دست نزن.
- migration جدیدی لازم نیست (جدول `departments` و ستون `department_id` از فاز ۱ موجودن).
- commit یا push نکن. فقط گزارش بده چه فایل‌هایی عوض/اضافه شدن + خروجی کامل تست.
