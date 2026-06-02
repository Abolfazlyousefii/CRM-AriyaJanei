<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_key',
        'base_price',
        'discount',
        'final_price',
        'quantity',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'discount' => 'integer',
        'final_price' => 'integer',
        'quantity' => 'integer',
    ];
}
