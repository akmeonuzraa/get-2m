<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseCrudController
{
    protected string $modelClass = User::class;

    protected function baseQuery(Request $request)
    {
        return User::query()->select([
            'id',
            'name',
            'email',
            'role',
            'service',
            'avatar',
            'is_active',
            'last_login_at',
            'created_at',
            'updated_at',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,responsable,user'],
            'service' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'in:admin,responsable,user'],
            'service' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->fresh());
    }

    public function toggleActive(Request $request, User $user): JsonResponse
    {
        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return response()->json([
            'message' => 'Statut utilisateur mis à jour.',
            'is_active' => $user->is_active,
        ]);
    }
}
