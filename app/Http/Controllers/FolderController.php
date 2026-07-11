<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        $folders = Folder::with('children', 'creator:id,name')
                         ->where('space_id', $request->space_id)
                         ->whereNull('parent_id')
                         ->get();

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'space_id'  => 'required|exists:spaces,id',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        $folder = Folder::create([
            'name'       => $request->name,
            'space_id'   => $request->space_id,
            'parent_id'  => $request->parent_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Dossier créé avec succès.',
            'folder'  => $folder
        ], 201);
    }

    public function update(Request $request, Folder $folder)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $folder->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Dossier modifié avec succès.',
            'folder'  => $folder
        ]);
    }

    public function destroy(Folder $folder)
    {
        $folder->delete();

        return response()->json(['message' => 'Dossier supprimé avec succès.']);
    }
}