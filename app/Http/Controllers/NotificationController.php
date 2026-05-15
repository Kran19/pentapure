<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            // Fallback for session-based auth if Auth::user() is null
            $sessionUser = session('auth_user');
            if (!$sessionUser) return response()->json(['notifications' => []]);
            $user = \App\Models\User::find($sessionUser['id']);
        }

        $notifications = $user->unreadNotifications->map(function($n) {
            return [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'type' => $n->data['type'] ?? 'info',
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        if (!$user) {
            $sessionUser = session('auth_user');
            if ($sessionUser) $user = \App\Models\User::find($sessionUser['id']);
        }

        if ($user) {
            $notification = $user->notifications()->findOrFail($id);
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 401);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        if (!$user) {
            $sessionUser = session('auth_user');
            if ($sessionUser) $user = \App\Models\User::find($sessionUser['id']);
        }

        if ($user) {
            $user->unreadNotifications->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 401);
    }
}
