<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category_value',
        'category_name',
        'image_path',
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
