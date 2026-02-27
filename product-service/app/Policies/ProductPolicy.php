<?php

namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

class ProductPolicy
{
    public function create(Authenticatable $user): bool
    {
        return strtolower((string) ($user->role ?? 'customer')) === 'admin';
    }

    public function update(Authenticatable $user): bool
    {
        return strtolower((string) ($user->role ?? 'customer')) === 'admin';
    }
}
