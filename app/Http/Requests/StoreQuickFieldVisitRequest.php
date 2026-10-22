<?php

namespace App\Http\Requests;

use App\Models\ShopContact;
use Illuminate\Validation\Rule;

class StoreQuickFieldVisitRequest extends StoreFieldVisitRequest
{
    protected function prepareForValidation(): void
    {
        $contact = $this->route('contact');
        $defaults = ['relation_type' => $contact?->relation_type ?? 'مرتبط',
            'cooperation_interest' => $contact?->cooperation_interest ?? 'نیاز به مذاکره دارد'];
        foreach ($defaults as $field => $value) {
            if (! $this->has($field)) {
                $this->merge([$field => $value]);
            }
        }
        if (is_string($this->input('owner_phone'))) {
            $phone = strtr($this->input('owner_phone'), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
            $this->merge(['owner_phone' => preg_replace('/[\s()\-]/u', '', $phone)]);
        }
    }

    public function rules(): array
    {
        return [
            'city' => 'nullable|string|max:60', 'address' => 'nullable|string|max:200',
            'shop_name' => 'required|string|max:120', 'owner_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]{7,15}$/'],
            'owner_name' => 'nullable|string|max:120',
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180',
            'relation_type' => ['required', Rule::in(['مرتبط', 'غیر مرتبط'])],
            'cooperation_interest' => ['required', Rule::in(['دارد', 'ندارد', 'نیاز به مذاکره دارد'])],
            'shop_grade' => ['nullable', Rule::in(['A - خرید عمده/فروشگاه بزرگ/فروش پرچمدار', 'B - ویترین متوسط / فروش میان رده', 'C - حجم خرید کم/ تعمیرکار/ فروش پایین رده'])],
            'description' => 'nullable|string|max:2000',
            'follow_up_status' => ['sometimes', 'required', Rule::in(array_keys(ShopContact::STATUSES))],
            'next_follow_up_at' => 'nullable|date_format:Y-m-d', 'follow_up_note' => 'nullable|string|max:2000',
            'buyer_name' => 'nullable|string|max:120',
            'customer_type' => ['required', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['customer_type']))],
            'visit_result' => ['required', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['visit_result']))],
            'requested_products' => 'nullable|array|max:7',
            'requested_products.*' => ['required', 'distinct', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['requested_products']))],
            'requested_products_note' => 'nullable|string|max:255',
            'purchase_volume' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['purchase_volume']))],
            'purchase_cycle' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['purchase_cycle']))],
            'preferred_payment' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['preferred_payment']))],
            'purchase_priorities' => 'nullable|array|max:6',
            'purchase_priorities.*' => ['required', 'distinct', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['purchase_priorities']))],
            'next_action' => ['nullable', Rule::in(array_keys(ShopContact::WHOLESALE_OPTIONS['next_action']))],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), ['owner_phone.regex' => 'شماره تلفن معتبر وارد کنید؛ مانند ۰۹۱۲۱۲۳۴۵۶۷ یا ۰۲۱۱۲۳۴۵۶۷۸.']);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), ['relation_type' => 'نوع فروشگاه', 'cooperation_interest' => 'تمایل به همکاری',
            'shop_grade' => 'گرید فروشگاه', 'description' => 'توضیحات', 'follow_up_note' => 'یادداشت پیگیری',
            'buyer_name' => 'نام مسئول خرید', 'customer_type' => 'نوع مشتری', 'visit_result' => 'نتیجه بازدید',
            'requested_products' => 'کالاهای موردنیاز', 'requested_products.*' => 'کالای موردنیاز', 'requested_products_note' => 'توضیح کالاها',
            'purchase_volume' => 'حجم خرید ماهانه', 'purchase_cycle' => 'دوره خرید', 'preferred_payment' => 'روش پرداخت',
            'purchase_priorities' => 'اولویت خرید', 'purchase_priorities.*' => 'اولویت خرید', 'next_action' => 'اقدام بعدی']);
    }

    public function contactData(): array
    {
        $data = $this->validated();
        $contact = $this->route('contact');
        foreach (['city', 'address'] as $field) {
            if (! $contact instanceof ShopContact || array_key_exists($field, $data)) {
                $data[$field] = $data[$field] ?? '';
            }
        }
        foreach (['requested_products', 'purchase_priorities'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] ?? [];
            }
        }
        $data['cooperation_interest'] = match ($data['visit_result']) {
            'interested','price_list','sample','ready_to_order' => 'دارد',
            'not_suitable' => 'ندارد', default => 'نیاز به مذاکره دارد',
        };
        $data['relation_type'] = in_array($data['customer_type'], ['other', 'unknown']) ? 'غیر مرتبط' : 'مرتبط';
        if (! $contact instanceof ShopContact || array_key_exists('owner_name', $data)) {
            $data['owner_name'] = $data['owner_name'] ?? '';
        }
        // A quick edit leaves the detailed questionnaire intact unless the activity type changes.
        if ($contact instanceof ShopContact && $contact->relation_type !== $data['relation_type']) {
            foreach (['activity_field', 'shop_size', 'shop_location', 'arya_customer', 'payment_terms', 'nr_activity', 'nr_activity_other', 'nr_goods_other'] as $field) {
                $data[$field] = null;
            }
            $data['main_goods'] = $data['nr_goods'] = [];
        }
        if ($data['relation_type'] !== 'مرتبط') {
            $data['shop_grade'] = null;
        }

        return $data;
    }
}
