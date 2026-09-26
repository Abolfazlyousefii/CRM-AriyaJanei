<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'manager_id', // 👈 این خیلی مهمه
        'department_id',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
        'deactivated_by',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'blocked_until' => 'datetime',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function userProducts()
    {
        return $this->hasMany(\App\Models\UserProduct::class);
    }

    public function isBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active && ! $this->trashed();
    }

    public function isActiveForErp(): bool
    {
        return $this->isActive() && ! $this->isBlocked();
    }

    public function canAccessErp(): bool
    {
        $roles = config('services.erp.access_roles', []);

        return (bool) config('services.erp.enabled', false)
            && $this->exists
            && $this->isActiveForErp()
            && $roles !== []
            && $this->hasAnyRole($roles);
    }

    public function isSellerForErp(): bool
    {
        $roles = config('services.erp.seller_roles', []);

        return $roles !== [] && $this->hasAnyRole($roles);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEligibleManagers(Builder $query): Builder
    {
        return $query->active()->whereHas('roles', function (Builder $roles): void {
            $roles->where('name', 'Owner')->orWhere('name', 'like', '%Manager');
        });
    }

    public static function isManagerialRole(string $role): bool
    {
        return $role === 'Owner' || str_ends_with($role, 'Manager');
    }

    public function blockRemaining(): ?string
    {
        return $this->isBlocked() ? $this->blocked_until->diffForHumans(null, true) : null;
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id')->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * IDs of every user below this one in the management chain, at any depth.
     *
     * @return list<int>
     */
    public function allSubordinateIds(): array
    {
        $ids = [];
        $frontier = [$this->id];
        $visited = [$this->id => true];

        while (! empty($frontier)) {
            $children = static::query()
                ->whereIn('manager_id', $frontier)
                ->pluck('id')
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                if (isset($visited[$childId])) {
                    continue; // guards against cycles in corrupted data
                }
                $visited[$childId] = true;
                $ids[] = $childId;
                $frontier[] = $childId;
            }
        }

        return $ids;
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by')->withTrashed();
    }

    public function isRole($role)
    {
        return $this->role === $role;
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class, 'user_id');
    }

    public function messageGroups()
    {
        return $this->belongsToMany(MessageGroup::class, 'message_group_user')->withTimestamps();
    }
}
