<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use App\Models\CustomerNote;
use App\Models\Report;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta; 
class AdminController extends Controller
{
public function reports(Request $request)
{
    $reportScope = app(\App\Services\ReportAccessService::class)->visibleTeamIds($request->user());
    // 📊 داده‌های نمودار دایره‌ای
    $referenceData = Customer::selectRaw('reference_type_id, COUNT(*) as count')
        ->groupBy('reference_type_id')
        ->with('referenceType:id,name')
        ->get()
        ->map(fn($item) => [
            'label' => optional($item->referenceType)->name ?? 'نامشخص',
            'count' => $item->count,
        ]);

    // 📈 داده‌های نمودار میله‌ای
    $cityData = Customer::selectRaw('address, COUNT(*) as count')
        ->whereNotNull('address')
        ->where('address', '<>', '')
        ->groupBy('address')
        ->orderByDesc('count')
        ->get()
        ->map(fn($item) => [
            'city' => $item->address,
            'count' => $item->count,
        ]);

    // 📋 جدول بازاریاب‌ها
    $marketerStats = User::role('Marketer')
        ->when($reportScope !== null, fn ($query) => $query->whereIn('id', $reportScope))
        ->withCount([
            'customers as customers_count',
            'notes as notes_count',
            'reports as reports_count',
        ])
        ->get();

$year = (int) $request->input('year', Jalalian::now()->getYear());
$month = (int) $request->input('month', Jalalian::now()->getMonth());
$marketerId = $request->input('marketer_id');

// 🧮 به‌دست‌آوردن تعداد روزهای ماه انتخابی
$daysInMonth = (new Jalalian($year, $month, 1))->getMonthDays();

// ✅ ساخت بازه ماه شمسی و تبدیل به میلادی (بدون fromFormat)
$startOfMonth = (new Jalalian($year, $month, 1))->toCarbon()->startOfDay();
$endOfMonth   = (new Jalalian($year, $month, $daysInMonth))->toCarbon()->endOfDay();


// ✅ تبدیل به میلادی برای فیلتر دیتابیس
$startOfMonthGregorian = $startOfMonth->toDateTimeString(); // خروجی Carbon میلادی
$endOfMonthGregorian = $endOfMonth->toDateTimeString();

$query = Report::query()
    ->when($reportScope !== null, fn ($query) => $query->whereIn('user_id', $reportScope))
    ->whereBetween('submitted_at', [$startOfMonthGregorian, $endOfMonthGregorian]);

if ($marketerId) {
    $query->where('user_id', $marketerId);
}

$reportsByDay = $query
    ->selectRaw('DATE(submitted_at) as day_date, SUM(successful_calls) as successful, SUM(unsuccessful_calls) as unsuccessful')
    ->groupBy('day_date')
    ->orderBy('day_date')
    ->get()
    ->map(function ($item) {
        $v = Verta::instance($item->day_date);
        return [
            'date' => $v->format('j F'), // مثل "۱۳ مهر"
            'successful' => (int) $item->successful,
            'unsuccessful' => (int) $item->unsuccessful,
        ];
    });
    $marketers = User::role('Marketer')->when($reportScope !== null, fn ($query) => $query->whereIn('id', $reportScope))->get();

    return view('admin.reports.dashboard', compact(
        'referenceData', 'cityData', 'marketerStats', 'marketers', 'reportsByDay', 'month', 'year', 'marketerId'
    ));}
    // ویرایش محصول
    public function updateProduct(Request $request, Product $product)
    {
        $request->merge([
            'price' => str_replace(',', '', $request->price),
        ]);

        $request->validate([
            'name'      => 'required|string|max:255',
            'price'     => 'required|numeric|min:0',
            'condition' => 'required|integer|min:1',
            'percent'   => 'required|numeric|min:0',
        ]);

        $product->update([
            'name'      => $request->name,
            'price'     => $request->price,
            'condition' => $request->condition,
            'percent'   => $request->percent / 100,
        ]);

        // 📌 لاگ ویرایش محصول
        activity()
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->withProperties(['data' => $request->all()])
            ->log('ویرایش محصول');

        return redirect()->route('admin.commissions')->with('success', 'محصول با موفقیت ویرایش شد ✅');
    }

    // نمایش لیست پورسانت‌ها
    public function index(Request $request)
    {
        $users = User::all();

        $selectedUser = null;
        if ($request->has('user_id')) {
            $selectedUser = User::with('userProducts.product')
                                ->find($request->user_id);
        }

        return view('admin.products.index', compact('users', 'selectedUser'));
    }

    // نمایش همه محصولات
    public function products(Request $request)
    {
        $products = Product::all();
        return view('admin.products.products', compact('products'));
    }

    // آپدیت تعداد فروش
    public function updateSales(Request $request, UserProduct $userProduct)
    {
        $request->validate([
            'sales' => 'required|integer|min:0',
        ]);

        $userProduct->sales = $request->sales;
        $userProduct->save();

        // 📌 لاگ تغییر فروش
        activity()
            ->performedOn($userProduct)
            ->causedBy(auth()->user())
            ->withProperties([
                'product_id' => $userProduct->product_id,
                'sales'      => $userProduct->sales,
            ])
            ->log('بروزرسانی تعداد فروش');

        return back()->with('success', 'فروش با موفقیت بروزرسانی شد ✅');
    }

    // فرم ایجاد محصول
    public function createProduct()
    {
        $users = User::all();
        return view('admin.products.create', compact('users'));
    }

    // ذخیره محصول جدید
    public function storeProduct(Request $request)
    {
        $request->merge([
            'price' => str_replace(',', '', $request->price),
        ]);

        $request->validate([
            'name'      => 'required|string|max:255',
            'price'     => 'required|numeric|min:0',
            'condition' => 'required|integer|min:1',
            'percent'   => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'name'      => $request->name,
            'price'     => $request->price,
            'condition' => $request->condition,
            'percent'   => $request->percent / 100,
        ]);

        $this->syncUserProducts();

        // 📌 لاگ ایجاد محصول
        activity()
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->withProperties(['data' => $request->all()])
            ->log('ایجاد محصول جدید');

        return redirect()
            ->route('admin.commissions')
            ->with('success', 'محصول با موفقیت ایجاد و برای همه کاربران همگام‌سازی شد ✅');
    }

    // همگام‌سازی محصولات برای همه کاربران
    public function syncUserProducts()
    {
        $users = User::all();
        $products = Product::all();

        foreach ($users as $user) {
            foreach ($products as $product) {
                UserProduct::firstOrCreate([
                    'user_id'    => $user->id,
                    'product_id' => $product->id,
                ], [
                    'sales' => 0,
                ]);
            }
        }

        // 📌 لاگ همگام‌سازی
        activity()
            ->causedBy(auth()->user())
            ->log('همگام‌سازی محصولات برای همه کاربران');
    }

    // صفحه ویرایش محصول
    public function editProduct(Product $product)
    {
        return view('admin.edit-product', compact('product'));
    }
}
