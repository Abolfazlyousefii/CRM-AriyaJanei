<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ExternalCustomerSyncController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $query = Customer::query()
            ->with([
                'category:id,name',
                'referenceType:id,name',
                'marketer:id,name,phone',
                'notes.author:id,name,phone',
            ]);

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', $validated['updated_since']);
        }

        $query->orderBy('id');

        if (! empty($validated['per_page'])) {
            $customers = $query
                ->paginate($validated['per_page'])
                ->through(fn (Customer $customer): array => $this->customerPayload($customer));

            return response()->json([
                'message' => 'Customers synced successfully.',
                'customers' => $customers,
            ]);
        }

        $customers = $query
            ->get()
            ->map(fn (Customer $customer): array => $this->customerPayload($customer))
            ->values();

        return response()->json([
            'message' => 'Customers synced successfully.',
            'count' => $customers->count(),
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_number' => ['nullable', 'integer', 'min:1', Rule::unique('customers', 'customer_number')],
            'name' => ['required', 'string', 'max:255'],
            'DISC' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('customers', 'phone')],
            'province' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'reference_type_id' => ['nullable', 'integer', 'exists:reference_types,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'marketer_phone' => ['nullable', 'string', 'max:20', 'exists:users,phone'],
        ]);

        if (! empty($validated['marketer_phone'])) {
            $validated['user_id'] = User::where('phone', $validated['marketer_phone'])->value('id');
        }

        $data = Arr::except($validated, ['marketer_phone']);

        if (! empty($data['user_id'])) {
            $data['marketer_changed_at'] = now();
        }

        $customer = Customer::create($data)->load([
            'category:id,name',
            'referenceType:id,name',
            'marketer:id,name,phone',
            'notes.author:id,name,phone',
        ]);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $this->customerPayload($customer),
        ], 201);
    }

    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'customer_number' => $customer->customer_number,
            'display_customer_id' => $customer->display_customer_id,
            'name' => $customer->name,
            'DISC' => $customer->DISC,
            'phone' => $customer->phone,
            'province' => $customer->province,
            'city' => $customer->city,
            'address' => $customer->address,
            'category_id' => $customer->category_id,
            'category' => $customer->category ? [
                'id' => $customer->category->id,
                'name' => $customer->category->name,
            ] : null,
            'reference_type_id' => $customer->reference_type_id,
            'reference_type' => $customer->referenceType ? [
                'id' => $customer->referenceType->id,
                'name' => $customer->referenceType->name,
            ] : null,
            'user_id' => $customer->user_id,
            'marketer' => $customer->marketer ? [
                'id' => $customer->marketer->id,
                'name' => $customer->marketer->name,
                'phone' => $customer->marketer->phone,
            ] : null,
            'marketer_changed_at' => $customer->marketer_changed_at,
            'notes' => $customer->notes->map(fn ($note): array => [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'author' => $note->author ? [
                    'id' => $note->author->id,
                    'name' => $note->author->name,
                    'phone' => $note->author->phone,
                ] : null,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
            ])->values(),
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at,
        ];
    }
}
