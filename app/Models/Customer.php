<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Customer $customer): void {
            if (! $customer->customer_number) {
                $customer->forceFill([
                    'customer_number' => 100000 + (int) $customer->id,
                ])->saveQuietly();
            }
        });
    }

    protected $fillable = [
        'customer_number',
        'name',
        'DISC',
        'phone',
        'province',
        'city',
        'address',
        'category_id',
        'reference_type_id',
        'user_id',
        'marketer_changed_at',
    ];

    protected $casts = [
        'marketer_changed_at' => 'datetime',
    ];

    public function getDisplayCustomerIdAttribute(): int
    {
        return (int) ($this->customer_number ?? (100000 + (int) $this->id));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function referenceType()
    {
        return $this->belongsTo(ReferenceType::class);
    }

    public function marketer()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class);
    }
}
