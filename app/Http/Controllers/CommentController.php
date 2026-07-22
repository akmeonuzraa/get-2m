<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\News;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    //  Lister les commentaires d'une actualité
    public function index(News $news)
    {
        $comments = $news->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json($comments);
    }

    //  Ajouter un commentaire
    public function store(Request $request, News $news)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'news_id' => $news->id,
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        return ApiResponse::created('Commentaire ajouté avec succès.', [
            'comment' => $comment->load('user:id,name'),
        ]);
    }

    //  Supprimer un commentaire
    public function destroy(News $news, Comment $comment)
    {
        $this->denyUnlessOwner($comment, request()->user(), allowAdmin: true);

        $comment->delete();

        return ApiResponse::message('Commentaire supprimé avec succès.');
    }
}
