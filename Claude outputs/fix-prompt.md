بخش بک‌اند (کنترلرها، روت‌ها، سرویس‌ها، تست‌ها) بررسی و تأیید شد و کاملاً درست است — به آن‌ها دست نزن.

مشکل: کاری که در گزارش قبلی ادعا کرده بودی روی View ها (Blade) انجام دادی، عملاً روی دیسک ذخیره/اعمال نشده. فایل‌های زیر هنوز حالت قبلی خودشان را دارند و هیچ‌کدام از تغییرات ادعاشده در آن‌ها دیده نمی‌شود. لطفاً همین الان این‌ها را واقعاً اصلاح کن:

## 1. `resources/views/admin/users/index.blade.php`

این فایل الان اصلاً از متغیر `$unassignedUsers` (که کنترلر می‌فرستد) استفاده نمی‌کند، و دکمه‌ی «افزودن کاربر» به فرم عمومی جدید (`admin.users.create`) در آن وجود ندارد. باید:

- یک دکمه‌ی «افزودن کاربر» اضافه کنی که به `route('admin.users.create')` لینک بدهد (کنار دکمه‌ی موجود «افزودن مدیر» / `admin.users.createManager`، بدون حذف آن).
- یک بخش جدید با عنوان دقیقاً `کاربران بدون مدیر مستقیم` اضافه کنی که روی `$unassignedUsers` لوپ بزند و لیست کاربران بدون مدیر و بدون نقش Manager را نشان بدهد (نام، نقش‌ها، دکمه‌ی ویرایش که به `admin.users.editEmployee` می‌رود).
- به‌جای مودال‌های تکراری inline موجود در همین فایل (`rolesModal{{ $employee->id }}` و مشابه‌هایش برای کارمندها)، از پارشیال جدید `resources/views/admin/users/partials/user-modals.blade.php` با `@include` استفاده کن — آن پارشیال الان ساخته شده ولی هیچ‌جا include نشده و کد مرده است. مراقب باش مودال‌های مخصوص مدیرها (`rolesModalManager`) که در پارشیال جدید نیستند دست‌نخورده بمانند.

## 2. `resources/views/admin/marketers/index.blade.php`

هنوز به روت‌های قدیمی لینک می‌دهد: `route('admin.marketers.create')` و `route('admin.marketers.edit', $item->id)`. با اینکه این روت‌ها الان ریدایرکت می‌کنند و از کار نمی‌افتند، برای یکدستی و برای پاس‌شدن تست `test_marketers_list_links_to_generic_forms_not_legacy_ones` باید مستقیماً لینک‌ها را عوض کنی به:
- `route('admin.users.create', ['role' => 'Marketer'])` به‌جای `admin.marketers.create`
- `route('admin.users.editEmployee', $item->id)` به‌جای `admin.marketers.edit`

## 3. تست کامل و اجباری قبل از هر گزارشی

بعد از این تغییرات:
1. `php artisan test --filter UnifiedUserManagementTest` را جدا اجرا کن و مطمئن شو هر ۱۰ تست (به‌خصوص `test_index_lists_users_without_direct_manager_in_a_separate_section` و `test_marketers_list_links_to_generic_forms_not_legacy_ones`) سبز می‌شوند.
2. کل `php artisan test` را اجرا کن و خروجی کامل تعداد pass/fail را بده (فقط ادعا نکن — عدد واقعی خروجی ترمینال را کپی کن).
3. هیچ‌کدام از فایل‌های بک‌اندی که قبلاً تأیید شده (کنترلرها، روت‌ها، سرویس‌ها، EligibleManager) را تغییر نده.

## 4. گزارش نهایی

در پایان فقط بگو دقیقاً کدام فایل‌ها تغییر کردند و خروجی واقعی `php artisan test` را بفرست. **هیچ commit یا push ای انجام نده** — تغییرات باید uncommitted بمانند تا دوباره بررسی و تأیید شود.
