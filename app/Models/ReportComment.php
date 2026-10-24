<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportComment extends Model
{
    protected $fillable = ['report_id', 'user_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
