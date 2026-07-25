<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerSatisfactionFormRequest;
use App\Http\Requests\UpdateCustomerSatisfactionFormRequest;
use App\Models\CustomerSatisfactionForm;
use App\Models\User;
use App\Support\JalaliDate;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerSatisfactionFormController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CustomerSatisfactionForm::class);
        $user = $request->user();
        $query = CustomerSatisfactionForm::with(['assignedToUser:id,name', 'createdByUser:id,name'])->latest();
        if (! $user->hasAnyRole(['Admin', 'internalManager', 'InternalManager'])) {
            $query->where(fn ($q) => $q->where('created_by_user_id', $user->id)->orWhere('assigned_to_user_id', $user->id));
        }
        $query->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->whereRaw("TRIM(CONCAT(COALESCE(customer_name,''), ' ', COALESCE(customer_family,''))) LIKE ?", ['%'.$request->search.'%'])->orWhere('customer_phone', 'like', '%'.$request->search.'%')))
            ->when($request->filled('purchase_status'), fn ($q) => $request->purchase_status === 'legacy' ? $q->whereNull('purchase_status') : $q->where('purchase_status', $request->purchase_status))
            ->when($request->filled('no_purchase_reason'), fn ($q) => $q->where('no_purchase_reason', $request->no_purchase_reason))
            ->when($request->filled('assigned_to_user_id'), fn ($q) => $q->where('assigned_to_user_id', $request->assigned_to_user_id));
        foreach (['date_from' => '>=', 'date_to' => '<='] as $field => $operator) {
            if ($request->filled($field)) try { $query->whereDate('submitted_at', $operator, Verta::parse($request->$field)->datetime()); } catch (\Throwable) { return back()->withErrors([$field => 'تاریخ فیلتر معتبر نیست.'])->withInput(); }
        }
        return view('customer-satisfaction-forms.index', ['forms' => $query->paginate(20)->withQueryString(), 'reviewUsers' => User::role('customer_review')->orderBy('name')->get(['id', 'name'])]);
    }

    public function create()
    {
        $this->authorize('create', CustomerSatisfactionForm::class);
        return view('customer-satisfaction-forms.create', ['reviewUsers' => $this->reviewUsers()]);
    }

    public function store(StoreCustomerSatisfactionFormRequest $request)
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated('customers') as $customer) CustomerSatisfactionForm::create($this->normalize($customer, true));
        });
        return redirect()->route('customer-satisfaction-forms.index')->with('success', 'فرم‌های رضایت مشتری با موفقیت ثبت شدند.');
    }

    public function show(CustomerSatisfactionForm $customerSatisfactionForm)
    {
        $this->authorize('view', $customerSatisfactionForm);
        return view('customer-satisfaction-forms.show', ['form' => $customerSatisfactionForm->load(['assignedToUser', 'createdByUser'])]);
    }

    public function edit(CustomerSatisfactionForm $customerSatisfactionForm)
    {
        $this->authorize('update', $customerSatisfactionForm);
        return view('customer-satisfaction-forms.edit', ['form' => $customerSatisfactionForm, 'reviewUsers' => $this->reviewUsers()]);
    }

    public function update(UpdateCustomerSatisfactionFormRequest $request, CustomerSatisfactionForm $customerSatisfactionForm)
    {
        $customerSatisfactionForm->update($this->normalize($request->validated('customers')[0], false));
        return redirect()->route('customer-satisfaction-forms.show', $customerSatisfactionForm)->with('success', 'فرم با موفقیت ویرایش شد.');
    }

    public function destroy(CustomerSatisfactionForm $customerSatisfactionForm)
    {
        $this->authorize('delete', $customerSatisfactionForm);
        $customerSatisfactionForm->delete();
        return redirect()->route('customer-satisfaction-forms.index')->with('success', 'فرم رضایت مشتری با موفقیت حذف شد.');
    }

    public function markAssignedReferralsSeen(): JsonResponse
    {
        $updated = CustomerSatisfactionForm::where('assigned_to_user_id', Auth::id())->whereNull('referral_seen_at')->update(['referral_seen_at' => now()]);
        return response()->json(['success' => true, 'updated' => $updated]);
    }

    public function submitResult(Request $request, CustomerSatisfactionForm $customerSatisfactionForm)
    {
        $this->authorize('submitResult', $customerSatisfactionForm);
        $validated = $request->validate(['result' => ['required', 'string']]);
        $customerSatisfactionForm->update(['result' => $validated['result'], 'result_filled_at' => now()]);
        return redirect()->route('customer-satisfaction-forms.show', $customerSatisfactionForm)->with('success', 'نتیجه بررسی ثبت شد.');
    }

    private function reviewUsers() { return User::role('customer_review')->orderBy('name')->get(['id', 'name']); }

    private function normalize(array $data, bool $creating): array
    {
        $digits = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
        $fullName = preg_replace('/\s+/u', ' ', trim($data['customer_full_name'] ?? ''));
        $parts = explode(' ', $fullName, 2);
        $result = [
            'customer_name' => $parts[0], 'customer_family' => $parts[1] ?? null,
            'customer_phone' => blank($data['customer_phone'] ?? null) ? null : strtr(trim($data['customer_phone']), $digits),
            'purchase_status' => $data['purchase_status'], 'description' => $data['description'] ?? null,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'submitted_at' => blank($data['submitted_at_fa'] ?? null) ? ($creating ? now()->toDateString() : null) : JalaliDate::toGregorian($data['submitted_at_fa']),
            'shipment_sent_at' => JalaliDate::toGregorian($data['shipment_sent_at_fa'] ?? null),
        ];
        $buyerFields = ['sales_response_score', 'support_positive_features', 'warranty_explained', 'warranty_meets_needs', 'shipping_time_score', 'packaging_quality_score', 'product_value_satisfied', 'would_recommend', 'would_choose_again'];
        $result['no_purchase_reason'] = $data['purchase_status'] === 'not_purchased' ? ($data['no_purchase_reason'] ?? null) : null;
        foreach ($buyerFields as $field) $result[$field] = $data['purchase_status'] === 'purchased' ? ($data[$field] ?? null) : null;
        if ($data['purchase_status'] !== 'purchased') $result['shipment_sent_at'] = null;
        if ($creating) $result['created_by_user_id'] = Auth::id();
        elseif ($result['submitted_at'] === null) unset($result['submitted_at']);
        return $result;
    }
}
