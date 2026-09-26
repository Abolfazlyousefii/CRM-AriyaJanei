# فاز ۱ از بازطراحی مدیریت پرسنل — زیرساخت + فیکس دیدن گزارش‌ها

این یک تغییر محدود و کم‌ریسکه، جدا از هر تغییر UI. فقط این سه مورد رو انجام بده، به هیچ فایل دیگه‌ای دست نزن (به‌خصوص `LeaveController.php` که منطق تایید دو-مرحله‌ای خودش رو داره و در این فاز دست‌نخورده می‌مونه).

## 1. فیکس باگ: کاربران غیرفعال در لیست «گزارش‌ندادن‌ها»

فایل: `app/Http/Controllers/ReportController.php`، متد `reportsManagment()`.

الان `$availableUsers` و `$usersWithoutYesterdayReport` فقط با یک آرایه‌ی هاردکد `$excludedUserIds` فیلتر می‌شن و هیچ فیلتری روی `is_active` ندارن. به هر دو query (هم شاخه‌ی Admin، هم شاخه‌ی non-Admin) یک `->where('is_active', true)` اضافه کن. آرایه‌ی `$excludedUserIds` رو دست‌نخورده نگه دار (کارکرد دیگه‌ای داره، فقط فیلتر جدید رو اضافه‌ش کن، جایگزینش نکن).

## 2. دیدن گزارش توسط کل زنجیره‌ی مدیریتی، نه فقط مدیر مستقیم

الان تو شاخه‌ی `else` (کاربر non-Admin که Manager هست) کوئری‌ها این‌طورن:
```php
$availableUsers = User::query()->where('manager_id', $manager->id)...
$usersWithoutYesterdayReport = User::query()->where('manager_id', $manager->id)...
$reportsQuery = ...->whereHas('user', fn($q) => $q->where('manager_id', $manager->id)...)
```
یعنی مثلاً مدیر داخلی یا مدیرکل فقط زیردستان *مستقیم* خودش رو می‌بینه، نه کل زیرمجموعه‌ی چندسطحی (مثلاً سرپرست IT → کارمند IT).

باید در `app/Models/User.php` یک متد عمومی اضافه کنی:

```php
public function allSubordinateIds(): array
{
    $ids = [];
    $frontier = [$this->id];
    $visited = [$this->id => true];

    while (!empty($frontier)) {
        $children = static::query()
            ->whereIn('manager_id', $frontier)
            ->pluck('id')
            ->all();

        $frontier = [];
        foreach ($children as $childId) {
            if (isset($visited[$childId])) {
                continue; // جلوگیری از حلقه‌ی بی‌نهایت در صورت داده‌ی خراب
            }
            $visited[$childId] = true;
            $ids[] = $childId;
            $frontier[] = $childId;
        }
    }

    return $ids;
}
```

(اگه اسم بهتری به‌نظرت میاد یا الگوی مشابهی already تو `EligibleManager` یا جای دیگه هست، همون سبک رو حفظ کن، فقط باید ضدحلقه باشه.)

سپس در `ReportController::reportsManagment()` شاخه‌ی non-Admin رو به‌جای `where('manager_id', $manager->id)` به `whereIn('id', $manager->allSubordinateIds())` (برای availableUsers/usersWithoutYesterdayReport) و `whereIn('manager_id', ...)` معادلش برای `$reportsQuery` تغییر بده. مراقب باش کوئری‌های موجود خراب نشن — اگه مدیر مستقیم‌ترین سطح باشه (زیردست مستقیم داره ولی نوه‌ی زیردست نداره)، نتیجه باید دقیقاً همون قبلی بمونه، فقط برای مدیرهای بالاتر گسترده‌تر بشه.

## 3. زیرساخت دپارتمان (بدون تغییر UI)

یک migration جدید بساز:
- جدول `departments`: `id`, `name` (string, unique), timestamps.
- ستون nullable `department_id` روی `users` (foreign key به `departments`, `nullOnDelete()`).

یک seeder یا بخش کوچیک تو همون migration برای پر کردن چهار رکورد اولیه: «حسابداری»، «انبار»، «فروش»، «آیتی». (اگه با seeder راحت‌تری، از artisan seeder جدا استفاده کن و دستور اجراش رو تو گزارش نهایی بگو.)

هیچ کنترلر یا ویویی به این ستون فعلاً ارجاع نمی‌ده — این فقط زیرساخته برای فاز بعدی.

## 4. تست

- برای `allSubordinateIds()` یک تست بنویس با یک زنجیره‌ی حداقل ۳ سطحی (کارمند → سرپرست → مدیر داخلی → مدیرکل) و مطمئن شو مدیرکل همه رو می‌بینه، سرپرست فقط زیرمجموعه‌ی خودش رو.
- برای `reportsManagment` یک تست بنویس که مدیر یک‌سطح بالاتر از مدیر مستقیم هم بتونه گزارش کارمند ته زنجیره رو ببینه.
- برای فیلتر `is_active` یک تست بنویس که کاربر غیرفعال نه در `usersWithoutYesterdayReport` نه در `availableUsers` نیاد.
- کل `php artisan test` رو اجرا کن (تنها، نه هم‌زمان با اجرای دیگه‌ای) و خروجی کامل رو برام بفرست. انتظار دارم همون ۱۴ فیل قدیمی بمونه و هیچ فیل جدیدی اضافه نشه.

## نکات مهم

- به `LeaveController.php` دست نزن — منطق تایید دو-مرحله‌ای اون در فاز جدا بررسی می‌شه.
- به UI (`admin/users/*`, `admin/marketers/*`) دست نزن — این فاز فقط بک‌اند/دیتابیسه.
- commit یا push نکن. فقط تغییرات رو uncommitted نگه دار و گزارش بده چه فایل‌هایی عوض شدن + خروجی کامل تست.
