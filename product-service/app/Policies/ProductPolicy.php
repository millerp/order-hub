<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return strtolower((string) ($user->role ?? 'customer')) === 'admin';
    }

    public function update(User $user): bool
    {
        return strtolower((string) ($user->role ?? 'customer')) === 'admin';
    }
}
