<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserAccountService
{
    public function create(array $attributes, array $roles): User
    {
        return DB::transaction(function () use ($attributes, $roles): User {
            $user = User::create($attributes);
            $user->syncRoles($roles);

            return $user->load(['roles', 'manager']);
        });
    }

    public function update(User $user, array $attributes, array $roles): User
    {
        return DB::transaction(function () use ($user, $attributes, $roles): User {
            $user->update($attributes);
            $user->syncRoles($roles);

            return $user->refresh()->load(['roles', 'manager']);
        });
    }

    public function syncRoles(User $user, array $roles): User
    {
        return DB::transaction(function () use ($user, $roles): User {
            $user->syncRoles(array_values(array_unique(Arr::wrap($roles))));

            return $user->refresh()->load('roles');
        });
    }
}
