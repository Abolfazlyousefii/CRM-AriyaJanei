<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveWorkflowEvent extends Model
{
    protected $guarded = [];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id')->withTrashed();
    }
}
