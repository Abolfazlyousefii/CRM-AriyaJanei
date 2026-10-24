<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveWorkflowStep extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id')->withTrashed();
    }
}
