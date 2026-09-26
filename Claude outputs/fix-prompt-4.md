# فاز ۳ — افزودن قابلیت‌های مسدودسازی/غیرفعال‌سازی/آرشیو به صفحه‌ی «پرسنل»

این فاز فقط ویو و کنترلر `personnel()` رو عوض می‌کنه؛ هیچ روت یا کنترلر جدیدی لازم نیست چون همه‌ی قابلیت‌ها (مسدودسازی، غیرفعال‌سازی، آرشیو) از قبل عمومی و آماده‌ن — فقط تو صفحه‌ی بازاریاب استفاده می‌شن، الان باید تو صفحه‌ی پرسنل هم بیان.

## 1. کنترلر: نشون‌دادن همه، نه فقط فعال‌ها

در `app/Http/Controllers/UserManagementController.php`، متد `personnel()`: خط `->where('is_active', true)` رو حذف کن. الان باید همه‌ی کاربرها (فعال، غیرفعال، مسدود) نشون داده بشن — دقیقاً مثل رفتار `MarketerController::index()` که همین الان فیلتر is_active نداره.

## 2. ویو: ستون وضعیت + دکمه‌های عملیات

در `resources/views/admin/personnel/index.blade.php`:

- یک ستون «وضعیت» قبل از ستون «عملیات» اضافه کن، دقیقاً همون منطق بج‌بندی که در `admin/marketers/index.blade.php` هست (غیرفعال / مسدود تا فلان تاریخ / فعال) — کپی کن همون بخش رو، همون کتابخانه‌ی `\Hekmatinasser\Verta\Verta` برای فرمت تاریخ.
- ستون «عملیات» رو به این ترکیب گسترش بده (کنار دکمه‌ی «ویرایش» موجود):
  - `@include('admin.users.partials.active-toggle', ['user' => $person])` — دقیقاً مثل صفحه‌ی بازاریاب.
  - دکمه‌ی «مسدود کن» که مودال `blockUserModal{{ $person->id }}` رو باز می‌کنه (فرم POST به `admin.users.block`، با فیلد `hours` و دکمه‌های سریع ۲۴/۷۲/۱۶۸ ساعت) — همون مودالی که در `admin/marketers/index.blade.php` هست رو عیناً کپی کن و بعد از جدول، برای هر `$person` توی `$personnel` تکرار کن (نه فقط `$marketers`).
  - اگه `$person->isBlocked()` بود، دکمه‌ی «آزادسازی» (فرم POST به `admin.users.unblock`).
  - دکمه‌ی «آرشیو» — فرم POST با `@method('DELETE')` که بر اساس همون متغیر `$isManagerial` موجود در این فایل، به `route('admin.users.destroyManager', $person)` یا `route('admin.users.destroyEmployee', $person)` می‌ره، با `onsubmit="return confirm('آیا مطمئن هستید؟')"`.

هیچ CSS/JS سفارشی جدید لازم نیست؛ کلاس‌های Bootstrap موجود (`btn-sm`, `btn-warning`, `btn-success`, `btn-outline-danger`, مودال‌های Bootstrap) رو مثل صفحه‌ی بازاریاب استفاده کن.

## 3. تست‌ها

- تست `test_personnel_page_lists_every_active_user_across_managers` در `tests/Feature/PersonnelSectionTest.php` رو آپدیت کن: چون این فاز رفتار عمداً عوض شده، حالا باید تست کنه که کاربر غیرفعال **هم** در `$ids` هست (نه که نباشه). اسم تست رو هم می‌تونی به چیزی مثل `test_personnel_page_lists_every_user_including_inactive` تغییر بدی. مطمئن شو صفحه بج «غیرفعال» رو براش نشون می‌ده (`assertSee`).
- تست جدید: یک کاربر مسدود (`blocked_until` در آینده) بساز، مطمئن شو بج «مسدود» و دکمه‌ی «آزادسازی» تو صفحه هست.
- تست جدید: POST به `admin.users.block` برای یک کاربر از طریق فرم صفحه‌ی پرسنل کار می‌کنه (یا حداقل مطمئن شو خود route/فرم درسته — چون خود کنترلر block از قبل تست شده، فقط باید مطمئن شی UI به route درست اشاره می‌کنه: `assertSee(route('admin.users.block', $person), false)`).
- تست جدید: دکمه‌ی آرشیو به route درست (`destroyManager` برای مدیر، `destroyEmployee` برای غیرمدیر) اشاره می‌کنه.
- کل `php artisan test` رو تنها اجرا کن، خروجی کامل رو بفرست. انتظار: همون ۱۴ فیل قدیمی، هیچ فیل جدید.

## نکات مهم

- به `UserBlockController`, `MarketerController`, `LeaveController` دست نزن — همه‌شون از قبل درستن.
- به منطق `personnel()` غیر از حذف اون یک خط فیلتر، چیز دیگه‌ای اضافه/کم نکن.
- commit/push نکن، فقط گزارش بده چه فایل‌هایی عوض شدن + خروجی کامل تست.
