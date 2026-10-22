<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportReviewWorkflow extends Model
{
    protected $fillable = [
        'report_id', 'status', 'current_reviewer_id', 'current_sequence',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function steps()
    {
        return $this->hasMany(ReportReviewStep::class, 'workflow_id');
    }

    public function currentReviewer()
    {
        return $this->belongsTo(User::class, 'current_reviewer_id');
    }

    public function events()
    {
        return $this->hasMany(ReportReviewEvent::class, 'workflow_id')->orderBy('id');
    }
}
