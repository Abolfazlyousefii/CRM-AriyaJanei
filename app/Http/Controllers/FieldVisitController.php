<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFieldVisitRequest;
use App\Http\Requests\StoreQuickFieldVisitRequest;
use App\Models\ShopContact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FieldVisitController extends Controller
{
    public function location(Request $request, \App\Services\FieldVisitLocation $location)
    {
        abort_unless($request->user()->can('create', ShopContact::class) || $request->user()->can('viewAny', ShopContact::class), 403);
        $point = $request->validate(['lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180']);

        return response()->json($location->reverse((float) $point['lat'], (float) $point['lng']));
    }

    private function filtered(Request $request)
    {
        $this->authorize('viewAny', ShopContact::class);
        $filters = $request->validate([
            'q' => 'nullable|string|max:120', 'city' => 'nullable|string|max:60',
            'user_id' => 'nullable|integer|min:1', 'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'relation_type' => ['nullable', Rule::in(['مرتبط', 'غیر مرتبط'])],
            'interest' => ['nullable', Rule::in(['دارد', 'ندارد', 'نیاز به مذاکره دارد'])],
            'status' => ['nullable', Rule::in(array_keys(ShopContact::STATUSES))],
            'grade' => ['nullable', Rule::in(['A', 'B', 'C'])], 'overdue' => 'nullable|in:1',
            'customer_type' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['customer_type']))],
            'purchase_volume' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['purchase_volume']))],
            'purchase_cycle' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['purchase_cycle']))],
            'preferred_payment' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['preferred_payment']))],
            'visit_result' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['visit_result']))],
            'next_action' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['next_action']))],
            'requested_product' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['requested_products']))],
        ]);
        $query = ShopContact::visibleTo($request->user());
        foreach (['customer_type', 'purchase_volume', 'purchase_cycle', 'preferred_payment', 'visit_result', 'next_action'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['requested_product'])) {
            $query->whereJsonContains('requested_products', $filters['requested_product']);
        }
        foreach (['city' => 'city', 'user_id' => 'user_id', 'relation_type' => 'relation_type', 'interest' => 'cooperation_interest', 'status' => 'follow_up_status'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($q) => $q->where('shop_name', 'like', $term)->orWhere('owner_name', 'like', $term)->orWhere('owner_phone', 'like', $term)->orWhere('address', 'like', $term));
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['grade'])) {
            $query->where('shop_grade', 'like', $filters['grade'].'%');
        }
        if (! empty($filters['overdue'])) {
            $this->overdue($query);
        }

        return $query;
    }

    private function overdue($query)
    {
        return $query->whereNotIn('follow_up_status', ['won', 'closed'])->whereDate('next_follow_up_at', '<', now()->toDateString());
    }

    public function index(Request $request)
    {
        $query = $this->filtered($request);
        $stats = [
            'total' => (clone $query)->count(),
            'interested' => (clone $query)->where('cooperation_interest', 'دارد')->count(),
            'won' => (clone $query)->where('follow_up_status', 'won')->count(),
            'overdue' => $this->overdue(clone $query)->count(),
            'unmapped' => (clone $query)->where(fn ($q) => $q->whereNull('lat')->orWhereNull('lng'))->count(),
            'legacy' => (clone $query)->whereNull('user_id')->count(),
            'ready' => (clone $query)->where('visit_result', 'ready_to_order')->count(),
            'prices' => (clone $query)->where('visit_result', 'price_list')->count(),
        ];
        $contacts = (clone $query)->with('user:id,name,deleted_at')->latest('id')->paginate(25)->withQueryString();
        $performance = (clone $query)->selectRaw("user_id, visitor_name, COUNT(*) as total, SUM(CASE WHEN follow_up_status = 'won' THEN 1 ELSE 0 END) as won")
            ->groupBy('user_id', 'visitor_name')->orderByDesc('total')->limit(15)->with('user:id,name,deleted_at')->get();
        $citiesReport = (clone $query)->selectRaw('city, COUNT(*) as total')->groupBy('city')->orderByDesc('total')->limit(10)->get();
        $daily = (clone $query)->whereDate('created_at', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->orderBy('day')->get();
        $visible = ShopContact::visibleTo($request->user());
        $cities = (clone $visible)->distinct()->orderBy('city')->pluck('city');
        $users = User::withTrashed()->whereIn('id', (clone $visible)->whereNotNull('user_id')->select('user_id'))->orderBy('name')->get(['id', 'name']);
        $mapPoints = (clone $query)->whereNotNull('lat')->whereNotNull('lng')->latest('id')->limit(1000)
            ->get(['id', 'lat', 'lng', 'shop_name', 'city', 'follow_up_status'])->map(fn ($c) => [
                'lat' => $c->lat, 'lng' => $c->lng, 'name' => $c->shop_name, 'city' => $c->city,
                'status' => ShopContact::STATUSES[$c->follow_up_status] ?? 'ثبت اولیه', 'url' => route('field-visits.show', $c),
            ])->values();

        $productDemand = [];
        foreach (ShopContact::WHOLESALE_OPTIONS['requested_products'] as $code => $label) {
            $productDemand[$code] = (clone $query)->whereJsonContains('requested_products', $code)->count();
        }
        arsort($productDemand);
        $volumeReport = (clone $query)->selectRaw('purchase_volume, COUNT(*) as total')->groupBy('purchase_volume')->pluck('total', 'purchase_volume');

        return view('field-visits.index', compact('stats', 'contacts', 'performance', 'citiesReport', 'daily', 'cities', 'users', 'mapPoints', 'productDemand', 'volumeReport'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', ShopContact::class);

        return view($request->boolean('detailed') ? 'field-visits.form' : 'field-visits.quick-form', ['contact' => null]);
    }

    public function quickStore(StoreQuickFieldVisitRequest $request)
    {
        ShopContact::create(array_merge($request->contactData(), ['user_id' => $request->user()->id, 'visitor_name' => $request->user()->name]));

        return redirect()->route('field-visits.index')->with('success', 'فروشگاه و موقعیت با موفقیت ثبت شد.');
    }

    public function quickUpdate(StoreQuickFieldVisitRequest $request, ShopContact $contact)
    {
        $contact->update($request->contactData());

        return redirect()->route('field-visits.show', $contact)->with('success', 'اطلاعات فروشگاه به‌روز شد.');
    }

    public function store(StoreFieldVisitRequest $request)
    {
        ShopContact::create(array_merge($request->contactData(), ['user_id' => $request->user()->id, 'visitor_name' => $request->user()->name]));

        return redirect()->route('field-visits.index')->with('success', 'فروشگاه و موقعیت با موفقیت ثبت شد.');
    }

    public function show(ShopContact $contact)
    {
        $this->authorize('view', $contact);

        return view('field-visits.show', compact('contact'));
    }

    public function edit(Request $request, ShopContact $contact)
    {
        $this->authorize('update', $contact);

        return view($request->boolean('detailed') ? 'field-visits.form' : 'field-visits.quick-form', compact('contact'));
    }

    public function update(StoreFieldVisitRequest $request, ShopContact $contact)
    {
        $contact->update($request->contactData());

        return redirect()->route('field-visits.show', $contact)->with('success', 'اطلاعات فروشگاه به‌روز شد.');
    }

    public function followUp(Request $request, ShopContact $contact)
    {
        $this->authorize('update', $contact);
        $contact->update($request->validate([
            'follow_up_status' => ['required', Rule::in(array_keys(ShopContact::STATUSES))],
            'next_follow_up_at' => 'nullable|date_format:Y-m-d', 'follow_up_note' => 'nullable|string|max:2000',
        ]));

        return back()->with('success', 'پیگیری ذخیره شد.');
    }

    public function destroy(ShopContact $contact)
    {
        $this->authorize('delete', $contact);
        $contact->delete();

        return redirect()->route('field-visits.index')->with('success', 'ثبت حذف شد.');
    }

    public function export(Request $request)
    {
        $query = $this->filtered($request)->with('user:id,name,deleted_at')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['شناسه', 'بازاریاب', 'شهر', 'فروشگاه', 'مالک', 'تلفن', 'آدرس', 'نوع ارتباط', 'تمایل همکاری', 'گرید', 'وضعیت پیگیری', 'تاریخ پیگیری (میلادی)', 'یادداشت پیگیری', 'تاریخ ثبت (میلادی)', 'عرض جغرافیایی', 'طول جغرافیایی', 'توضیحات', 'زمینه فعالیت', 'اندازه فروشگاه', 'موقعیت فروشگاه', 'اجناس اصلی', 'مشتری آریا', 'پرداخت', 'فعالیت غیرمرتبط', 'شغل دیگر', 'اجناس غیرمرتبط', 'اجناس دیگر', 'مسئول خرید', 'نوع مشتری', 'کالاهای موردنیاز', 'توضیح کالاها', 'حجم خرید ماهانه', 'دوره خرید', 'روش پرداخت ترجیحی', 'اولویت خرید', 'نتیجه بازدید', 'اقدام بعدی']);
            foreach ($query->lazy(500) as $c) {
                $row = [$c->id, $c->user?->name ?? $c->visitor_name.' (قدیمی)', $c->city, $c->shop_name, $c->owner_name,
                    $c->owner_phone, $c->address, $c->relation_type, $c->cooperation_interest, $c->shop_grade,
                    ShopContact::STATUSES[$c->follow_up_status] ?? '', $c->next_follow_up_at?->format('Y-m-d'), $c->follow_up_note,
                    $c->created_at?->format('Y-m-d H:i'), $c->lat, $c->lng, $c->description, $c->activity_field,
                    $c->shop_size, $c->shop_location, implode('، ', $c->main_goods ?? []), $c->arya_customer, $c->payment_terms,
                    $c->nr_activity, $c->nr_activity_other, implode('، ', $c->nr_goods ?? []), $c->nr_goods_other,
                    $c->buyer_name, $c->wholesaleLabel('customer_type'), $c->wholesaleLabel('requested_products'), $c->requested_products_note,
                    $c->wholesaleLabel('purchase_volume'), $c->wholesaleLabel('purchase_cycle'), $c->wholesaleLabel('preferred_payment'),
                    $c->wholesaleLabel('purchase_priorities'), $c->wholesaleLabel('visit_result'), $c->wholesaleLabel('next_action')];
                $row = array_map(fn ($v) => is_string($v) && preg_match('/^[\s]*[=+@\-]/u', $v) ? "'".$v : $v, $row);
                fputcsv($file, $row);
            }
            fclose($file);
        }, 'field-visits-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
