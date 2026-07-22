<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    //  Lister les actualités publiées
    public function index()
    {
        $news = News::published()
            ->pinnedFirst()
            ->with('creator:id,name')
            ->paginate(15);

        return response()->json($news);
    }

    //  Créer une actualité
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target' => 'required|in:all,service,space',
            'target_value' => 'nullable|string',
            'is_pinned' => 'boolean',
        ]);

        $news = News::create([
            'title' => $request->title,
            'content' => $request->content,
            'target' => $request->target,
            'target_value' => $request->target_value,
            'is_pinned' => $request->is_pinned ?? false,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return ApiResponse::created('Actualité créée avec succès.', [
            'news' => $news,
        ]);
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
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'target' => 'sometimes|in:all,service,space',
            'target_value' => 'nullable|string',
            'is_pinned' => 'boolean',
        ]);

        $news->update($request->only(
            'title', 'content', 'target', 'target_value', 'is_pinned'
        ));

        return ApiResponse::message('Actualité modifiée avec succès.', data: [
            'news' => $news,
        ]);
    }

    //  Publier une actualité
    public function publish(News $news)
    {
        $news->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return ApiResponse::message('Actualité publiée avec succès.');
    }

    //  Archiver une actualité
    public function archive(News $news)
    {
        $news->update(['status' => 'archived']);

        return ApiResponse::message('Actualité archivée avec succès.');
    }

    //  Supprimer une actualité
    public function destroy(News $news)
    {
        $news->delete();

        return ApiResponse::message('Actualité supprimée avec succès.');
    }
}
