<?php

namespace App\Policies;

use App\Models\CustomerSatisfactionForm;
use App\Models\User;

class CustomerSatisfactionFormPolicy
{
    private function manager(User $user): bool { return $user->hasAnyRole(['Admin', 'internalManager', 'InternalManager']); }
    public function viewAny(User $user): bool { return $this->manager($user) || $user->hasRole('customer_review'); }
    public function view(User $user, CustomerSatisfactionForm $form): bool { return $this->manager($user) || $form->created_by_user_id === $user->id || $form->assigned_to_user_id === $user->id; }
    public function create(User $user): bool { return $user->hasRole('customer_review'); }
    public function update(User $user, CustomerSatisfactionForm $form): bool { return $form->purchase_status !== null && ($this->manager($user) || $form->created_by_user_id === $user->id); }
    public function delete(User $user, CustomerSatisfactionForm $form): bool { return $form->created_by_user_id === $user->id && blank($form->result); }
    public function submitResult(User $user, CustomerSatisfactionForm $form): bool { return $form->assigned_to_user_id === $user->id; }
}
