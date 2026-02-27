<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): bool;
}
