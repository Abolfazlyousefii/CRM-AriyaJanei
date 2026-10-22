<?php

namespace App\Http\Requests;

use App\Models\ShopContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFieldVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof ShopContact
            ? $this->user()->can('update', $contact)
            : $this->user()->can('create', ShopContact::class);
    }

    public function rules(): array
    {
        $rules = [
            'city' => 'required|string|max:60', 'relation_type' => ['required', Rule::in(['مرتبط', 'غیر مرتبط'])],
            'address' => 'required|string|max:200', 'shop_name' => 'required|string|max:120',
            'owner_name' => 'required|string|max:120', 'owner_phone' => 'required|string|max:30',
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180',
            'cooperation_interest' => ['required', Rule::in(['دارد', 'ندارد', 'نیاز به مذاکره دارد'])],
            'description' => 'nullable|string|max:2000',
            'follow_up_status' => ['sometimes', 'required', Rule::in(array_keys(ShopContact::STATUSES))],
            'next_follow_up_at' => 'nullable|date_format:Y-m-d', 'follow_up_note' => 'nullable|string|max:2000',
        ];

        return array_merge($rules, $this->input('relation_type') === 'مرتبط' ? [
            'activity_field' => ['required', Rule::in(['موبایل + جانبی', 'فقط جانبی', 'تعمیرات + جانبی', 'ترکیبی'])],
            'shop_size' => ['required', Rule::in(['کمتر از 15 متر', 'بین 15 تا 30 متر', 'بیشتر از 30 متر'])],
            'shop_location' => ['required', Rule::in(['مسیر خیابان اصلی', 'همکف پاساژ', 'طبقات پاساژ', 'کوچه فرعی'])],
            'shop_grade' => ['required', Rule::in(['A - خرید عمده/فروشگاه بزرگ/فروش پرچمدار', 'B - ویترین متوسط / فروش میان رده', 'C - حجم خرید کم/ تعمیرکار/ فروش پایین رده'])],
            'main_goods' => 'nullable|array', 'main_goods.*' => 'string|max:50',
            'arya_customer' => ['required', Rule::in(['است', 'نیست'])], 'payment_terms' => ['required', Rule::in(['نقدی', 'چکی'])],
        ] : [
            'nr_activity' => ['required', Rule::in(['لوازم رایانه (کامپیوتر)', 'سوپرمارکت', 'تزیینات خودرو', 'لوازم برقی', 'غیره'])],
            'nr_activity_other' => 'nullable|string|max:120|required_if:nr_activity,غیره',
            'nr_goods' => 'required|array|min:1', 'nr_goods.*' => 'string|max:50',
            'nr_goods_other' => ['nullable', 'string', 'max:200', Rule::requiredIf(in_array('غیره', (array) $this->input('nr_goods', []), true))],
        ]);
    }

    public function messages(): array
    {
        return ['required' => 'تکمیل :attribute الزامی است.', 'in' => 'گزینه :attribute معتبر نیست.',
            'date_format' => 'تاریخ را به میلادی و در قالب سال-ماه-روز وارد کنید.',
            'between' => ':attribute خارج از محدوده مجاز است.'];
    }

    public function attributes(): array
    {
        return ['lat' => 'موقعیت روی نقشه (عرض)', 'lng' => 'موقعیت روی نقشه (طول)', 'city' => 'شهر',
            'address' => 'آدرس', 'shop_name' => 'نام فروشگاه', 'owner_name' => 'نام مالک', 'owner_phone' => 'تلفن مالک',
            'next_follow_up_at' => 'تاریخ پیگیری', 'follow_up_status' => 'وضعیت پیگیری'];
    }

    public function contactData(): array
    {
        $data = $this->validated();
        $inactive = $data['relation_type'] === 'مرتبط'
            ? ['nr_activity', 'nr_activity_other', 'nr_goods_other']
            : ['activity_field', 'shop_size', 'shop_location', 'shop_grade', 'arya_customer', 'payment_terms'];
        foreach ($inactive as $field) {
            $data[$field] = null;
        }
        $data['main_goods'] = $data['main_goods'] ?? [];
        $data['nr_goods'] = $data['nr_goods'] ?? [];

        return $data;
    }
}
