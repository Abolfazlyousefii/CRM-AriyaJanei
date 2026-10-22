<?php

namespace App\Policies;

use App\Models\ShopContact;
use App\Models\User;

class ShopContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Marketer', 'Manager', 'Admin', 'Owner', 'Sales', 'internalManager', 'InternalManager']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Marketer', 'Sales', 'Admin', 'Owner']);
    }

    public function view(User $user, ShopContact $contact): bool
    {
        return $this->viewAny($user) && ShopContact::visibleTo($user)->whereKey($contact->id)->exists();
    }

    public function update(User $user, ShopContact $contact): bool
    {
        return $this->view($user, $contact);
    }

    public function delete(User $user, ShopContact $contact): bool
    {
        return $user->hasAnyRole(['Admin', 'Owner']);
    }
}
