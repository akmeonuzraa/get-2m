<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddSpaceMemberRequest;
use App\Http\Requests\StoreSpaceRequest;
use App\Http\Requests\UpdateSpaceMemberRoleRequest;
use App\Http\Requests\UpdateSpaceRequest;
use App\Models\Space;
use App\Models\SpaceMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaceController extends BaseCrudController
{
    protected string $modelClass = Space::class;

    protected function baseQuery(Request $request)
    {
        $user = $request->user();
        $query = Space::query()->with(['creator:id,name,email', 'members:id,name,email']);

        if ($user->isAdmin() || $user->isResponsable()) {
            return $query;
        }

        return $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $space = $this->baseQuery($request)->findOrFail($id);

        return response()->json($space);
    }

    public function store(Request $request): JsonResponse
    {
        $formRequest = StoreSpaceRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->setRouteResolver($request->getRouteResolver());
        abort_unless($formRequest->authorize(), 403, 'Accès refusé.');

        $data = $request->validate($formRequest->rules());
        $space = Space::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'public',
            'cover_image' => $data['cover_image'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $space->members()->attach($request->user()->id, [
            'role' => SpaceMember::ROLE_ADMIN,
            'joined_at' => now(),
        ]);

        return response()->json($space->fresh(), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $space = Space::findOrFail($id);
        $this->ensureSpaceManageAccess($request->user(), $space);

        $formRequest = UpdateSpaceRequest::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->setRouteResolver($request->getRouteResolver());
        abort_unless($formRequest->authorize(), 403, 'Accès refusé.');

        $validated = $request->validate($formRequest->rules());

        $space->update($validated);

        return response()->json($space->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $space = Space::findOrFail($id);
        $this->ensureSpaceManageAccess($request->user(), $space);

        $space->delete();

        return response()->json(['message' => 'Espace supprimé avec succès.']);
    }

    public function addMember(AddSpaceMemberRequest $request, Space $space): JsonResponse
    {
        $this->ensureSpaceMemberManageAccess($request->user(), $space);
        $data = $request->validated();

        $space->members()->syncWithoutDetaching([
            $data['user_id'] => [
                'role' => $data['role'],
                'joined_at' => now(),
            ],
        ]);

        return response()->json([
            'message' => 'Membre ajouté avec succès.',
            'member' => $space->members()->where('users.id', $data['user_id'])->first(),
        ], 201);
    }

    public function updateMemberRole(UpdateSpaceMemberRoleRequest $request, Space $space, User $user): JsonResponse
    {
        $this->ensureSpaceMemberManageAccess($request->user(), $space);
        abort_unless($space->members()->where('users.id', $user->id)->exists(), 404, 'Membre introuvable.');

        $space->members()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        return response()->json([
            'message' => 'Rôle du membre mis à jour.',
            'member' => $space->members()->where('users.id', $user->id)->first(),
        ]);
    }

    public function removeMember(Request $request, Space $space, User $user): JsonResponse
    {
        $this->ensureSpaceMemberManageAccess($request->user(), $space);
        $space->members()->detach($user->id);

        return response()->json(['message' => 'Membre retiré avec succès.']);
    }

    protected function ensureSpaceManageAccess(User $user, Space $space): void
    {
        if ($user->isAdmin() || $user->isResponsable() || $space->created_by === $user->id) {
            return;
        }

        abort(403, 'Accès refusé.');
    }

    protected function ensureSpaceMemberManageAccess(User $user, Space $space): void
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return;
        }

        $isSpaceAdmin = $space->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', SpaceMember::ROLE_ADMIN)
            ->exists();

        if (! $isSpaceAdmin) {
            abort(403, 'Accès refusé.');
        }
    }
}
