<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopContact extends Model
{
    public const STATUSES = ['new' => 'ثبت اولیه', 'negotiating' => 'در حال مذاکره', 'won' => 'همکاری آغاز شد', 'closed' => 'عدم همکاری'];

    public const WHOLESALE_OPTIONS = [
        'customer_type' => ['mobile' => 'فروشگاه موبایل', 'accessories' => 'لوازم جانبی', 'repair' => 'تعمیرکار', 'wholesaler' => 'عمده‌فروش', 'other' => 'سایر مشاغل', 'unknown' => 'نامشخص'],
        'requested_products' => ['cable' => 'کابل', 'charger' => 'شارژر', 'earphones' => 'هندزفری', 'powerbank' => 'پاوربانک', 'cases' => 'قاب', 'glass' => 'گلس', 'other' => 'سایر کالاها'],
        'purchase_volume' => ['under_10' => 'کمتر از ۱۰ میلیون تومان', '10_50' => '۱۰ تا ۵۰ میلیون تومان', '50_100' => '۵۰ تا ۱۰۰ میلیون تومان', '100_300' => '۱۰۰ تا ۳۰۰ میلیون تومان', 'over_300' => 'بیش از ۳۰۰ میلیون تومان'],
        'purchase_cycle' => ['weekly' => 'هفتگی', 'fortnightly' => 'دوهفته‌ای', 'monthly' => 'ماهانه', 'irregular' => 'موردی'],
        'preferred_payment' => ['cash' => 'نقدی', 'cheque' => 'چکی', 'mixed' => 'ترکیبی'],
        'purchase_priorities' => ['price' => 'قیمت', 'quality' => 'کیفیت', 'variety' => 'تنوع', 'availability' => 'موجودی', 'delivery' => 'ارسال سریع', 'payment' => 'شرایط پرداخت'],
        'visit_result' => ['unknown' => 'هنوز مشخص نیست', 'interested' => 'علاقه‌مند', 'price_list' => 'درخواست لیست قیمت', 'sample' => 'درخواست نمونه', 'ready_to_order' => 'آماده سفارش', 'not_now' => 'فعلاً زمان مناسبی نیست', 'not_suitable' => 'نامناسب برای همکاری'],
        'next_action' => ['send_prices' => 'ارسال لیست قیمت', 'send_sample' => 'ارسال نمونه', 'call' => 'تماس مجدد', 'take_order' => 'ثبت سفارش', 'visit_again' => 'بازدید مجدد', 'none' => 'بدون اقدام بعدی'],
    ];

    protected $fillable = [
        'visitor_name', 'city', 'relation_type',
        'address', 'lat', 'lng',
        'shop_name', 'owner_name', 'owner_phone',
        'cooperation_interest',

        'activity_field', 'shop_size', 'shop_location', 'shop_grade',
        'main_goods', 'arya_customer', 'payment_terms',

        'nr_activity', 'nr_activity_other', 'nr_goods', 'nr_goods_other',
        'description', 'user_id', 'follow_up_status', 'next_follow_up_at', 'follow_up_note',
        'buyer_name', 'customer_type', 'requested_products', 'requested_products_note', 'purchase_volume',
        'purchase_cycle', 'preferred_payment', 'purchase_priorities', 'visit_result', 'next_action',
    ];

    protected $casts = ['main_goods' => 'array', 'nr_goods' => 'array', 'next_follow_up_at' => 'date', 'lat' => 'float', 'lng' => 'float', 'requested_products' => 'array', 'purchase_priorities' => 'array'];

    public function wholesaleLabel(string $field): string
    {
        $options = self::WHOLESALE_OPTIONS[$field] ?? [];
        $value = $this->getAttribute($field);

        return is_array($value) ? implode('، ', array_map(fn ($v) => $options[$v] ?? $v, $value)) : ($options[$value ?? ''] ?? 'نامشخص');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user)
    {
        if ($user->hasAnyRole(['Admin', 'Owner', 'Sales'])) {
            return $query;
        }
        $ids = [$user->id];
        if ($user->hasAnyRole(['Manager', 'internalManager', 'InternalManager'])) {
            $ids = array_merge($ids, $user->allSubordinateIds());
        }

        return $query->whereIn('user_id', $ids);
    }
}
