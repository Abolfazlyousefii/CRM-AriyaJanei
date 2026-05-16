<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Leave extends Model
{
    use HasFactory;
       use SoftDeletes; 
 const STATUS_PENDING_MANAGER   = 'pending_manager';   // در انتظار تایید مدیر واحد
    const STATUS_PENDING_INTERNAL  = 'pending_internal';  // در انتظار تایید مدیر داخلی یا ادمین
    const STATUS_PENDING_ACCOUNT   = 'pending_account';   // در انتظار تایید حسابداری
    const STATUS_APPROVED          = 'approved';          // تایید نهایی
    const STATUS_REJECTED          = 'rejected';          // رد شده
    protected $fillable = [
        'user_id',
        'substitute_user_id',
        'leave_type',
        'start_date',
          'leave_unit',
        'end_date',
        'start_time',
        'end_time',
        'reason',
        'manager_id',
        'super_manager_id',
        'status',
    ];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function substituteUser() {
        return $this->belongsTo(User::class, 'substitute_user_id');
    }

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function accountant() {
        return $this->belongsTo(User::class, 'accountant_id');
    }

    public function superManager() {
        return $this->belongsTo(User::class, 'super_manager_id');
}




}
