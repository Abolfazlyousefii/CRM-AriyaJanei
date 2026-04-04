<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class CustomerNotesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function canEditNotes($user): bool
    {
        return $user->hasRole('Admin')
            || $user->hasRole('Marketer')
            || $user->hasRole('internalManager');
    }

    private function canDeleteNotes($user): bool
    {
        return $user->hasRole('Admin')
            || $user->hasRole('internalManager');
    }

    private function ensureCustomerAccess(Customer $customer): void
    {
        $user = Auth::user();

        if (!$this->canEditNotes($user) && !$user->hasRole('SaleManager')) {
            abort(403);
        }

        if ($user->hasRole('Marketer') && (int) $customer->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function ensureNestedMarketerCustomer(User $marketer, Customer $customer): void
    {
        if ((int) $customer->user_id !== (int) $marketer->id) {
            abort(403);
        }
    }

    private function notePayload(CustomerNote $note): array
    {
        $note->loadMissing('user');

        return [
            'id' => $note->id,
            'content' => $note->content,
            'creator' => $note->user?->name ?? '-',
            'creator_role' => $note->user?->getRoleNames()->first(),
            'created_at' => Jalalian::fromDateTime($note->created_at)->format('Y/m/d H:i'),
        ];
    }

    public function index(User $marketer, Customer $customer)
    {
        if (url()->previous() && !str_contains(url()->previous(), 'notes')) {
            session(['customers_previous_url' => url()->previous()]);
        }

        $this->ensureNestedMarketerCustomer($marketer, $customer);
        $this->ensureCustomerAccess($customer);

        $notes = $customer->notes()->latest('created_at')->paginate(15);

        if (Auth::user()->hasRole('Marketer')) {
            return view('marketer.customers.notes.index', compact('customer', 'notes'));
        }

        return view('admin.marketers.customers.notes.index', compact('customer', 'marketer', 'notes'));
    }

    public function create(User $marketer, Customer $customer)
    {
        $this->ensureNestedMarketerCustomer($marketer, $customer);
        $this->ensureCustomerAccess($customer);

        if (Auth::user()->hasRole('Marketer')) {
            return view('marketer.customers.notes.create', compact('customer'));
        }

        return view('admin.marketers.customers.notes.create', compact('customer', 'marketer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $user = Auth::user();

        if (!$this->canEditNotes($user)) {
            abort(403);
        }

        $this->ensureCustomerAccess($customer);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $note = $customer->notes()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($note)
            ->withProperties(['customer_id' => $customer->id])
            ->log('ایجاد یادداشت');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'note' => $this->notePayload($note),
            ]);
        }

        return back()->with('success', 'یادداشت جدید با موفقیت ثبت شد.');
    }

    public function show(User $marketer, Customer $customer, CustomerNote $note)
    {
        $this->ensureNestedMarketerCustomer($marketer, $customer);
        $this->ensureCustomerAccess($customer);

        if ((int) $note->customer_id !== (int) $customer->id) {
            abort(404);
        }

        if (Auth::user()->hasRole('Marketer')) {
            return view('marketer.customers.notes.show', compact('customer', 'note'));
        }

        return view('admin.marketers.customers.notes.show', compact('customer', 'marketer', 'note'));
    }

    public function edit(User $marketer, Customer $customer, CustomerNote $note)
    {
        $this->ensureNestedMarketerCustomer($marketer, $customer);
        $this->ensureCustomerAccess($customer);

        if ((int) $note->customer_id !== (int) $customer->id) {
            abort(404);
        }

        if (Auth::user()->hasRole('Marketer')) {
            return view('marketer.customers.notes.edit', compact('customer', 'note'));
        }

        return view('admin.marketers.customers.notes.edit', compact('customer', 'marketer', 'note'));
    }

    public function update(Request $request, User $marketer, Customer $customer, CustomerNote $note)
    {
        $user = Auth::user();

        if (!$this->canEditNotes($user)) {
            abort(403);
        }

        $this->ensureNestedMarketerCustomer($marketer, $customer);
        $this->ensureCustomerAccess($customer);

        if ((int) $note->customer_id !== (int) $customer->id) {
            abort(404);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $oldData = $note->only(['content']);

        $note->update([
            'content' => $validated['content'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($note)
            ->withProperties([
                'old' => $oldData,
                'new' => ['content' => $validated['content']],
                'customer_id' => $customer->id,
            ])
            ->log('ویرایش یادداشت');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'content' => $note->content,
            ]);
        }

        return back()->with('success', 'یادداشت با موفقیت بروزرسانی شد.');
    }

    public function updateInline(Request $request, CustomerNote $note)
    {
        $user = Auth::user();

        if (!$this->canEditNotes($user)) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ویرایش یادداشت را ندارید.'
            ], 403);
        }

        $customer = $note->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'مشتری مربوط به این یادداشت پیدا نشد.'
            ], 404);
        }

        $this->ensureCustomerAccess($customer);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $oldData = $note->only(['content']);

        $note->update([
            'content' => $validated['content'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($note)
            ->withProperties([
                'old' => $oldData,
                'new' => ['content' => $validated['content']],
                'customer_id' => $customer->id,
            ])
            ->log('ویرایش یادداشت');

        return response()->json([
            'success' => true,
            'content' => $note->content,
        ]);
    }

    public function destroy(User $marketer, Customer $customer, CustomerNote $note)
    {
        $user = Auth::user();

        if (!$this->canDeleteNotes($user)) {
            abort(403);
        }

        $this->ensureNestedMarketerCustomer($marketer, $customer);

        if ((int) $note->customer_id !== (int) $customer->id) {
            abort(404);
        }

        activity()
            ->causedBy($user)
            ->performedOn($note)
            ->withProperties(['customer_id' => $customer->id])
            ->log('حذف یادداشت');

        $note->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'یادداشت حذف شد.');
    }

    public function destroyInline(CustomerNote $note)
    {
        $user = Auth::user();

        if (!$this->canDeleteNotes($user)) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه حذف یادداشت را ندارید.'
            ], 403);
        }

        $customer = $note->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'مشتری مربوط به این یادداشت پیدا نشد.'
            ], 404);
        }

        activity()
            ->causedBy($user)
            ->performedOn($note)
            ->withProperties(['customer_id' => $customer->id])
            ->log('حذف یادداشت');

        $note->delete();

        return response()->json([
            'success' => true
        ]);
    }
}