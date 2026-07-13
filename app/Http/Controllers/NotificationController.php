<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //  Lister les notifications du user connecté
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
                                     ->orderByDesc('created_at')
                                     ->paginate(20);

        return response()->json($notifications);
    }

    //  Marquer une notification comme lue
    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    //  Marquer toutes les notifications comme lues
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);

        return response()->json(['message' => 'Toutes les notifications marquées comme lues.']);
    }

    //  Compter les notifications non lues
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
                             ->where('is_read', false)
                             ->count();

        return response()->json(['unread_count' => $count]);
    }

    //  Supprimer une notification
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification supprimée.']);
    }

    //  Créer une notification (usage interne)
    public static function notify(int $userId, string $type, string $title, string $message, $notifiable = null)
    {
        return Notification::create([
            'user_id'         => $userId,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id'   => $notifiable ? $notifiable->id : null,
        ]);
    }
}