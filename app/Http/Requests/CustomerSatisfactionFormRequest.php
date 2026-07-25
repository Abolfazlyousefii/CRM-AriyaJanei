<?php

namespace App\Http\Requests;

use App\Models\CustomerSatisfactionForm;
use App\Models\User;
use App\Support\JalaliDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class CustomerSatisfactionFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $customers = $this->input('customers');

        if (! is_array($customers)) {
            return;
        }

        foreach ($customers as &$customer) {
            if (! is_array($customer)) {
                continue;
            }

            foreach (['submitted_at_fa', 'shipment_sent_at_fa'] as $field) {
                if (! array_key_exists($field, $customer) || (! is_string($customer[$field]) && $customer[$field] !== null)) {
                    continue;
                }

                $customer[$field] = JalaliDate::normalize($customer[$field]);
            }
        }
        unset($customer);

        $this->merge(['customers' => $customers]);
    }

    public function rules(): array
    {
        return [
            'customers' => ['required', 'array', 'min:1'],
            'customers.*.customer_full_name' => ['required', 'string', 'max:255'],
            'customers.*.customer_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9۰-۹٠-٩+\s-]+$/u'],
            'customers.*.purchase_status' => ['required', Rule::in(array_keys(CustomerSatisfactionForm::PURCHASE_STATUSES))],
            'customers.*.submitted_at_fa' => ['nullable', 'string'],
            'customers.*.shipment_sent_at_fa' => ['nullable', 'string'],
            'customers.*.assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'customers.*.description' => ['nullable', 'string'],
            'customers.*.no_purchase_reason' => ['nullable', Rule::in(array_keys(CustomerSatisfactionForm::NO_PURCHASE_REASONS))],
            'customers.*.sales_response_score' => ['nullable', 'integer', 'between:1,5'],
            'customers.*.support_positive_features' => ['nullable', 'array'],
            'customers.*.support_positive_features.*' => [Rule::in(array_keys(CustomerSatisfactionForm::SUPPORT_FEATURES))],
            'customers.*.warranty_explained' => ['nullable', Rule::in(['yes', 'no'])],
            'customers.*.warranty_meets_needs' => ['nullable', Rule::in(['yes', 'no'])],
            'customers.*.shipping_time_score' => ['nullable', 'integer', 'between:1,5'],
            'customers.*.packaging_quality_score' => ['nullable', 'integer', 'between:1,5'],
            'customers.*.product_value_satisfied' => ['nullable', Rule::in(['yes', 'no'])],
            'customers.*.would_recommend' => ['nullable', Rule::in(['yes', 'no'])],
            'customers.*.would_choose_again' => ['nullable', Rule::in(['yes', 'no'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('customers', []) as $index => $customer) {
                $status = $customer['purchase_status'] ?? null;
                $required = $status === 'purchased'
                    ? ['sales_response_score', 'support_positive_features', 'warranty_explained', 'warranty_meets_needs', 'shipping_time_score', 'packaging_quality_score', 'product_value_satisfied', 'would_recommend', 'would_choose_again']
                    : ($status === 'not_purchased' ? ['no_purchase_reason'] : []);
                foreach ($required as $field) {
                    if (blank($customer[$field] ?? null)) $validator->errors()->add("customers.$index.$field", 'پاسخ این سؤال الزامی است.');
                }
                foreach (['submitted_at_fa', 'shipment_sent_at_fa'] as $field) {
                    if (! JalaliDate::isValid($customer[$field] ?? null)) {
                        $validator->errors()->add("customers.$index.$field", 'تاریخ شمسی انتخاب‌شده معتبر نیست.');
                    }
                }
                $assigned = $customer['assigned_to_user_id'] ?? null;
                if ($assigned && ! User::role('customer_review')->whereKey($assigned)->exists()) $validator->errors()->add("customers.$index.assigned_to_user_id", 'کاربر ارجاع‌شده باید نقش بررسی رضایت مشتری داشته باشد.');
            }
        }];
    }

    public function messages(): array
    {
        return ['customers.required' => 'حداقل یک مشتری وارد کنید.', 'customers.*.customer_full_name.required' => 'نام و نام خانوادگی مشتری الزامی است.', 'customers.*.customer_phone.regex' => 'شماره تماس فقط می‌تواند شامل عدد، +، فاصله و خط تیره باشد.', 'customers.*.purchase_status.required' => 'وضعیت خرید مشتری را انتخاب کنید.', 'customers.*.between' => 'امتیاز باید بین ۱ تا ۵ باشد.', 'customers.*.integer' => 'امتیاز باید عدد صحیح باشد.'];
    }
}
