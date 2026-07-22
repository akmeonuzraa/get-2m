<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //  Lister les notifications du user connecté
    public function index(Request $request)
    {
        $notifications = Notification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    //  Marquer une notification comme lue
    public function markAsRead(Request $request, Notification $notification)
    {
        $this->denyUnlessOwner($notification, $request->user());

        $notification->markRead();

        return ApiResponse::message('Notification marquée comme lue.');
    }

    //  Marquer toutes les notifications comme lues
    public function markAllAsRead(Request $request)
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return ApiResponse::message('Toutes les notifications marquées comme lues.');
    }

    //  Compter les notifications non lues
    public function unreadCount(Request $request)
    {
        $count = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    //  Supprimer une notification
    public function destroy(Request $request, Notification $notification)
    {
        $this->denyUnlessOwner($notification, $request->user());

        $notification->delete();

        return ApiResponse::message('Notification supprimée.');
    }

    //  Créer une notification (usage interne)
    public static function notify(int $userId, string $type, string $title, string $message, $notifiable = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable ? $notifiable->id : null,
        ]);
    }
}
