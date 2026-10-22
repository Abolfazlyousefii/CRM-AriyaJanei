<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'supervisor_id', 'manager_id', 'parent_manager_id'];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id')->withTrashed();
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id')->withTrashed();
    }

    public function parentManager()
    {
        return $this->belongsTo(User::class, 'parent_manager_id')->withTrashed();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
