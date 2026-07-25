<?php

namespace App\Models;

use App\Policies\CustomerSatisfactionFormPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsePolicy(CustomerSatisfactionFormPolicy::class)]
class CustomerSatisfactionForm extends Model
{
    use HasFactory;

    public const PURCHASE_STATUSES = ['purchased' => 'خریدار', 'not_purchased' => 'خرید نکرده'];
    public const NO_PURCHASE_REASONS = ['price' => 'قیمت', 'quality' => 'کیفیت محصول', 'communication' => 'نحوه ارتباط و پاسخ‌گویی', 'product_range' => 'سبد و تنوع کالا', 'shipping_timing' => 'نحوه ارسال و زمان‌بندی'];
    public const SUPPORT_FEATURES = ['communication' => 'نحوه ارتباط', 'follow_up' => 'پیگیری مناسب', 'knowledge' => 'دانش و تسلط کارشناس'];
    public const YES_NO = ['yes' => 'بله', 'no' => 'خیر'];

    protected $fillable = [
        'submitted_at',
        'customer_name',
        'customer_family',
        'shipment_sent_at',
        'satisfaction_status',
        'assigned_to_user_id',
        'operator_communication_score',
        'shipment_score',
        'product_quality_score',
        'needs_consultation',
        'wants_to_purchase',   // دارد / ندارد
        'purchase_method',     // سایت / حضوری / تلفنی
        'created_by_user_id',
        'description',
        'result',
        'result_filled_at',
        'referral_seen_at',
        'customer_phone', 'purchase_status', 'no_purchase_reason', 'sales_response_score',
        'support_positive_features', 'warranty_explained', 'warranty_meets_needs',
        'shipping_time_score', 'packaging_quality_score', 'product_value_satisfied',
        'would_recommend', 'would_choose_again',
    ];

    protected $casts = [
        'submitted_at' => 'date',
        'shipment_sent_at' => 'date',
        'result_filled_at' => 'datetime',
        'referral_seen_at' => 'datetime',
        'support_positive_features' => 'array',
        'sales_response_score' => 'integer',
        'shipping_time_score' => 'integer',
        'packaging_quality_score' => 'integer',
    ];

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getCustomerFullNameAttribute(): string
    {
        return trim(($this->customer_name ?? '') . ' ' . ($this->customer_family ?? ''));
    }

    public function getWantsToPurchaseLabelAttribute(): string
    {
        return $this->wants_to_purchase === 'yes' ? 'دارد' : 'ندارد';
    }

    public function getPurchaseMethodLabelAttribute(): string
    {
        switch ($this->purchase_method) {
            case 'website': return 'سایت';
            case 'in_person': return 'حضوری';
            case 'phone': return 'تلفنی';
            default: return '-';
        }
    }

    public function getPurchaseStatusLabelAttribute(): string { return self::PURCHASE_STATUSES[$this->purchase_status] ?? 'فرم قدیمی'; }
    public function getNoPurchaseReasonLabelAttribute(): string { return self::NO_PURCHASE_REASONS[$this->no_purchase_reason] ?? '—'; }
    public function getSupportPositiveFeaturesLabelsAttribute(): array { return array_values(array_intersect_key(self::SUPPORT_FEATURES, array_flip($this->support_positive_features ?? []))); }
    public function getWarrantyExplainedLabelAttribute(): string { return self::YES_NO[$this->warranty_explained] ?? '—'; }
    public function getWarrantyMeetsNeedsLabelAttribute(): string { return self::YES_NO[$this->warranty_meets_needs] ?? '—'; }
    public function getProductValueSatisfiedLabelAttribute(): string { return self::YES_NO[$this->product_value_satisfied] ?? '—'; }
    public function getWouldRecommendLabelAttribute(): string { return self::YES_NO[$this->would_recommend] ?? '—'; }
    public function getWouldChooseAgainLabelAttribute(): string { return self::YES_NO[$this->would_choose_again] ?? '—'; }
    public function getAverageScoreAttribute(): ?float
    {
        $scores = array_values(array_filter([$this->sales_response_score, $this->shipping_time_score, $this->packaging_quality_score], fn ($score) => $score !== null));
        return $scores === [] ? null : round(array_sum($scores) / count($scores), 1);
    }
}
