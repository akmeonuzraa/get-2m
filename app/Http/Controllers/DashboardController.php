<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\News;
use App\Models\Notification;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Documents récents
        $recentDocuments = Document::with('uploader:id,name', 'folder:id,name')
            ->where('status', 'active')
            ->latest()
            ->take(5)
            ->get();

        // Dernières actualités
        $latestNews = News::with('creator:id,name')
            ->where('status', 'published')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        // Notifications non lues
        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->take(5)
            ->get();

        // Stats personnelles
        $myDocuments = Document::where('uploaded_by', $user->id)
            ->where('status', 'active')
            ->count();

        $mySpaces = Space::whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        // Stats globales (admin seulement)
        $globalStats = null;
        if ($user->isAdmin()) {
            $globalStats = [
                'total_users'     => User::count(),
                'total_documents' => Document::where('status', 'active')->count(),
                'total_spaces'    => Space::count(),
                'total_news'      => News::where('status', 'published')->count(),
            ];
        }

        return response()->json([
            'user'                 => [
                'id'      => $user->id,
                'name'    => $user->name,
                'role'    => $user->role,
                'service' => $user->service,
            ],
            'recent_documents'     => $recentDocuments,
            'latest_news'          => $latestNews,
            'unread_notifications' => $unreadNotifications,
            'my_stats'             => [
                'documents_uploaded' => $myDocuments,
                'spaces_joined'      => $mySpaces,
                'unread_notifications' => Notification::where('user_id', $user->id)
                                                      ->where('is_read', false)
                                                      ->count(),
            ],
            'global_stats'         => $globalStats,
        ]);
    }
}