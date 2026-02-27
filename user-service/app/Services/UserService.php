<?php

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->userRepository->all();
    }

    public function getById(int $id): User
    {
        $user = $this->userRepository->findById($id);
        if (! $user) {
            throw (new ModelNotFoundException)->setModel(User::class, $id);
        }

        return $user;
    }

    public function create(array $data): User
    {
        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->getById($id);

        return $this->userRepository->update($user, $data);
    }

    public function delete(int $id): void
    {
        $user = $this->getById($id);
        $this->userRepository->delete($user);
    }
}
