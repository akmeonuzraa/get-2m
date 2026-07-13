<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    //  Lister les actualités publiées
    public function index()
    {
        $news = News::with('creator:id,name')
                    ->where('status', 'published')
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('published_at')
                    ->paginate(15);

        return response()->json($news);
    }

    //  Créer une actualité
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'target'       => 'required|in:all,service,space',
            'target_value' => 'nullable|string',
            'is_pinned'    => 'boolean',
        ]);

        $news = News::create([
            'title'        => $request->title,
            'content'      => $request->content,
            'target'       => $request->target,
            'target_value' => $request->target_value,
            'is_pinned'    => $request->is_pinned ?? false,
            'status'       => 'draft',
            'created_by'   => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Actualité créée avec succès.',
            'news'    => $news
        ], 201);
    }

    //  Afficher une actualité
    public function show(News $news)
    {
        return response()->json(
            $news->load('creator:id,name', 'comments.user:id,name')
        );
    }

    //  Modifier une actualité
    public function update(Request $request, News $news)
    {
        $request->validate([
            'title'        => 'sometimes|string|max:255',
            'content'      => 'sometimes|string',
            'target'       => 'sometimes|in:all,service,space',
            'target_value' => 'nullable|string',
            'is_pinned'    => 'boolean',
        ]);

        $news->update($request->only(
            'title', 'content', 'target', 'target_value', 'is_pinned'
        ));

        return response()->json([
            'message' => 'Actualité modifiée avec succès.',
            'news'    => $news
        ]);
    }

    //  Publier une actualité
    public function publish(News $news)
    {
        $news->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        return response()->json(['message' => 'Actualité publiée avec succès.']);
    }

    //  Archiver une actualité
    public function archive(News $news)
    {
        $news->update(['status' => 'archived']);

        return response()->json(['message' => 'Actualité archivée avec succès.']);
    }

    //  Supprimer une actualité
    public function destroy(News $news)
    {
        $news->delete();

        return response()->json(['message' => 'Actualité supprimée avec succès.']);
    }
}