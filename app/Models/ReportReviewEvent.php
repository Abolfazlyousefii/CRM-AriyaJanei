<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportReviewEvent extends Model
{
    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
