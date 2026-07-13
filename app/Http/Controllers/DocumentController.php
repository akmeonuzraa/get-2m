<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NotificationController;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploader:id,name', 'folder:id,name')
                         ->where('status', 'active');

        if ($request->space_id) {
            $query->where('space_id', $request->space_id);
        }

        if ($request->folder_id) {
            $query->where('folder_id', $request->folder_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'file'      => 'required|file|max:51200',
            'space_id'  => 'nullable|exists:spaces,id',
            'folder_id' => 'nullable|exists:folders,id',
            'keywords'  => 'nullable|array',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'title'             => $request->title,
            'description'       => $request->description,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_type'         => $file->getClientOriginalExtension(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'keywords'          => $request->keywords,
            'service'           => $request->user()->service,
            'folder_id'         => $request->folder_id,
            'space_id'          => $request->space_id,
            'uploaded_by'       => $request->user()->id,
            'current_version'   => 1,
            'status'            => 'active',
        ]);
          // Notifier les membres de l'espace
if ($document->space_id) {
    $space = \App\Models\Space::with('members')->find($document->space_id);
    foreach ($space->members as $member) {
        if ($member->id !== $request->user()->id) {
            NotificationController::notify(
                $member->id,
                'document_uploaded',
                'Nouveau document',
                $request->user()->name . ' a déposé "' . $document->title . '"',
                $document
            );
        }
    }
}
        DocumentVersion::create([
            'document_id'       => $document->id,
            'version_number'    => 1,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size'         => $file->getSize(),
            'uploaded_by'       => $request->user()->id,
        ]);

        return response()->json([
            'message'  => 'Document uploadé avec succès.',
            'document' => $document
        ], 201);
    }

    public function show(Document $document)
    {
        return response()->json(
            $document->load('uploader:id,name', 'folder:id,name', 'versions')
        );
    }

    public function trash(Document $document)
    {
        $document->update([
            'status'     => 'trashed',
            'trashed_at' => now(),
        ]);

        return response()->json(['message' => 'Document mis à la corbeille.']);
    }

    public function restore(Document $document)
    {
        $document->update([
            'status'     => 'active',
            'trashed_at' => null,
        ]);

        return response()->json(['message' => 'Document restauré avec succès.']);
    }

    public function destroy(Document $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->forceDelete();

        return response()->json(['message' => 'Document supprimé définitivement.']);
    }

    public function trashed()
    {
        $documents = Document::where('status', 'trashed')
                             ->with('uploader:id,name')
                             ->paginate(15);

        return response()->json($documents);
    }
}