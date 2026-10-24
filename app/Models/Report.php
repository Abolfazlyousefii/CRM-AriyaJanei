<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_READ = 'read';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'submitted_at',
        'feedback',
        'rating',
        'voice_path',
        'successful_calls',
    'unsuccessful_calls',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'feedback' => 'string',
        'rating' => 'integer',
        ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeRead($query)
    {
        return $query->where('status', self::STATUS_READ);
    }

    public function markAsSubmitted()
    {
        $this->status  = self::STATUS_SUBMITTED;
        $this->submitted_at = now();
        $this->save();
    }

    public function markAsRead()
    {
        $this->status = self::STATUS_READ;
        $this->save();
    }
    public function reviewWorkflow()
    {
        return $this->hasOne(ReportReviewWorkflow::class);
    }

    public function attachments()
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function comments(){return $this->hasMany(ReportComment::class)->orderBy('id');}

}
