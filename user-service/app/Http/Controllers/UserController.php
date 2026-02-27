<?php

namespace App\Http\Controllers;

use App\Contracts\UserServiceInterface;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection($this->userService->getAll());
    }

    public function show($id)
    {
        $user = $this->userService->getById((int) $id);
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);
        $validated = $request->validated();
        $validated['password'] = '';
        $user = $this->userService->create($validated);

        return response()->json(new UserResource($user), 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = $this->userService->getById((int) $id);
        Gate::authorize('update', $user);
        $user = $this->userService->update((int) $id, $request->validated());

        return new UserResource($user);
    }

    public function destroy($id)
    {
        $user = $this->userService->getById((int) $id);
        Gate::authorize('delete', $user);
        $this->userService->delete((int) $id);

        return response()->json(null, 204);
    }
}
