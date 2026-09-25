<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\ReferenceType;
use Illuminate\Http\Request;

class CustomerAdminController extends Controller
{
  public function index(Request $request)
{
    $search = $request->get('search');
    $sort   = $request->get('sort'); // sort: last_note OR null

    $customers = Customer::with([
        'marketer',
        'notes' => function ($q) {
            $q->latest();
        },
        'invoices',      // چون توی ویو استفاده کردی
        'referenceType', // همین‌طور
    ])
    
    ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    })

    // مرتب‌سازی براساس آخرین یادداشت
    ->when($sort === 'last_note', function ($query) {
        $query->leftJoin('notes', 'notes.customer_id', '=', 'customers.id')
              ->select('customers.*')
              ->groupBy('customers.id')
              ->orderByRaw('MAX(notes.created_at) DESC');
              // اگر MySQL استفاده می‌کنی، NULLS LAST رو نذار
    })

    // مرتب‌سازی عادی فقط وقتی که last_note انتخاب نشده
    ->when($sort !== 'last_note', function ($query) {
        $query->orderBy('id', 'DESC');
    })

    ->paginate(15);

    return view('admin.customers.index', compact('customers', 'search', 'sort'));
}



    public function create()
    {
        $marketers = User::role('Marketer')->get();
        $refrenses = ReferenceType::get();
        return view('admin.customers.create', compact('marketers','refrenses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|unique:customers',
            'reference_type_id' => 'nullable|exists:reference_types,id',
            'address' => 'nullable|string',
            'user_id'=> 'nullable|exists:users,id',
        ]);

        $request->merge([
            'marketer_changed_at' => now(),
        ]);

        $customer = Customer::create($request->all());

        // 📌 لاگ اضافه کردن مشتری
        activity()
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['name' => $customer->name, 'phone' => $customer->phone])
            ->log('ایجاد مشتری جدید');

        return redirect()->route('admin.customersAdmin.index')
                         ->with('success', 'مشتری جدید ثبت شد.');
    }

    public function edit(Customer $customer)
    {
        $marketers = User::role('marketer')->get();
        $refrenses = ReferenceType::get();
        return view('admin.customers.edit', compact('customer', 'marketers','refrenses'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required',
            'user_id' => 'nullable|exists:users,id',
            'address' => 'nullable',
            'reference_type_id' => 'nullable|exists:reference_types,id',
        ]);

        if ($request->user_id != $customer->user_id) {
            $customer->marketer_changed_at = now();
        }

        $customer->update([
            'user_id' => $request->user_id ?? $customer->user_id,
            'name' => $request->name ?? $customer->name,
            'phone' => $request->phone ?? $customer->phone,
            'reference_type_id' => $request->reference_type_id ?? $customer->reference_type_id,
            'address' => $request->address ?? $customer->address,
            'category_id' => $request->category_id ?? $customer->category_id,
            'marketer_changed_at' => $customer->marketer_changed_at,
        ]);

        // 📌 لاگ ویرایش مشتری
        activity()
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['changed' => $request->all()])
            ->log('ویرایش اطلاعات مشتری');

        return redirect()->route('admin.customersAdmin.index')->with('success', 'اطلاعات مشتری ویرایش شد.');
    }

    public function destroy(Customer $customer)
    {
        $customerName = $customer->name;
        $customerPhone = $customer->phone;

        $customer->delete();

        // 📌 لاگ حذف مشتری
        activity()
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['name' => $customerName, 'phone' => $customerPhone])
            ->log('حذف مشتری');

        return redirect()->route('admin.customersAdmin.index')->with('success', 'مشتری حذف شد.');
    }
}
