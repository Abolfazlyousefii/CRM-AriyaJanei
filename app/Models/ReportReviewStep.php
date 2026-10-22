<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportReviewStep extends Model
{
    protected $fillable = [
        'workflow_id', 'sequence', 'reviewer_id', 'reviewer_name',
        'decision', 'comment', 'returned_description_hash', 'decided_at', 'decided_by',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function workflow()
    {
        return $this->belongsTo(ReportReviewWorkflow::class, 'workflow_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
