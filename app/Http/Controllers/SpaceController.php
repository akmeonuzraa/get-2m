<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    //  Lister les espaces
    public function index()
    {
        $spaces = Space::with('creator:id,name', 'members:id,name,role')
            ->paginate(15);

        return response()->json($spaces);
    }

    //  Créer un espace
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:public,private',
        ]);

        $space = Space::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'created_by' => $request->user()->id,
        ]);

        // Ajouter le créateur comme admin de l'espace
        $space->members()->attach($request->user()->id, ['role' => 'admin']);

        return ApiResponse::created('Espace créé avec succès.', [
            'space' => $space->load('creator:id,name', 'members:id,name'),
        ]);
    }

    //  Afficher un espace
    public function show(Space $space)
    {
        return response()->json(
            $space->load('creator:id,name', 'members:id,name')
        );
    }

    //  Modifier un espace
    public function update(Request $request, Space $space)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:public,private',
        ]);

        $space->update($request->only('name', 'description', 'type'));

        return ApiResponse::message('Espace modifié avec succès.', data: [
            'space' => $space,
        ]);
    }

    //  Ajouter un membre
    public function addMember(Request $request, Space $space)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,contributor,reader',
        ]);

        $space->members()->syncWithoutDetaching([
            $request->user_id => ['role' => $request->role],
        ]);

        return ApiResponse::message('Membre ajouté avec succès.');
    }

    //  Retirer un membre
    public function removeMember(Space $space, $userId)
    {
        $space->members()->detach($userId);

        return ApiResponse::message('Membre retiré avec succès.');
    }

    //  Supprimer un espace
    public function destroy(Space $space)
    {
        $space->delete();

        return ApiResponse::message('Espace supprimé avec succès.');
    }
}
