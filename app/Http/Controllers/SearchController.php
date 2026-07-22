<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\News;
use App\Models\Space;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'type' => 'nullable|in:document,news,space',
            'service' => 'nullable|string',
            'file_type' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = $request->q;
        $type = $request->type;
        $results = [];

        //  Recherche Documents
        if (! $type || $type === 'document') {
            $docs = Document::active()
                ->with('uploader:id,name', 'folder:id,name')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        ->orWhere('original_filename', 'LIKE', "%{$query}%")
                        ->orWhere('keywords', 'LIKE', "%{$query}%");
                });

            if ($request->service) {
                $docs->where('service', $request->service);
            }

            if ($request->file_type) {
                $docs->where('file_type', $request->file_type);
            }

            if ($request->from) {
                $docs->whereDate('created_at', '>=', $request->from);
            }

            if ($request->to) {
                $docs->whereDate('created_at', '<=', $request->to);
            }

            $results['documents'] = $docs->paginate(10);
        }

        //  Recherche News
        if (! $type || $type === 'news') {
            $results['news'] = News::published()
                ->with('creator:id,name')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->paginate(10);
        }

        //  Recherche Spaces
        if (! $type || $type === 'space') {
            $results['spaces'] = Space::with('creator:id,name')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->paginate(10);
        }

        return response()->json([
            'query' => $query,
            'results' => $results,
        ]);
    }
}
