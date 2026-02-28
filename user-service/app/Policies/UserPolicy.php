<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function isAdmin(User $user): bool
    {
        return strtolower((string) $user->role) === 'admin';
    }

    public function viewAny(User $user)
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, User $model)
    {
        return $this->isAdmin($user) || $user->id === $model->id;
    }

    public function create(User $user)
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, User $model)
    {
        return $this->isAdmin($user) || $user->id === $model->id;
    }

    public function delete(User $user, User $model)
    {
        return $this->isAdmin($user) || $user->id === $model->id;
    }
}
