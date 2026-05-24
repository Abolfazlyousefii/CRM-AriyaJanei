<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSatisfactionForm extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'submitted_at' => 'date',
        'shipment_sent_at' => 'date',
        'result_filled_at' => 'datetime',
        'referral_seen_at' => 'datetime',
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
}