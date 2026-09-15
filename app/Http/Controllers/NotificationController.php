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
            $sessionUser = session('auth_user');
            if ($sessionUser) {
                $user = \App\Models\User::find($sessionUser['id']);
            }
        }

        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $rawNotifs = \Illuminate\Support\Facades\DB::table('notifications')
            ->where(function($q) use ($user) {
                $q->where('notifiable_id', (string)$user->id)
                  ->orWhere('notifiable_id', (int)$user->id);
            })
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $unreadCount = 0;
        $notifications = $rawNotifs->map(function($n) use (&$unreadCount) {
            $data = json_decode($n->data ?? '{}', true) ?: [];
            $isRead = !is_null($n->read_at);
            if (!$isRead) {
                $unreadCount++;
            }
            return [
                'id'         => (string)$n->id,
                'title'      => $data['title'] ?? 'Notification',
                'message'    => $data['message'] ?? '',
                'type'       => $data['type'] ?? 'info',
                'is_read'    => $isRead,
                'read_at'    => $isRead ? \Carbon\Carbon::parse($n->read_at)->diffForHumans() : null,
                'created_at' => \Carbon\Carbon::parse($n->created_at)->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count'  => $unreadCount,
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
            if ($sessionUser) {
                $user = \App\Models\User::find($sessionUser['id']);
            }
        }

        $nowStr = \Carbon\Carbon::now()->toDateTimeString();
        
        \Illuminate\Support\Facades\DB::table('notifications')
            ->where('id', (string)$id)
            ->update(['read_at' => $nowStr]);

        return response()->json(['success' => true]);
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

        $nowStr = \Carbon\Carbon::now()->toDateTimeString();
        if ($user) {
            \Illuminate\Support\Facades\DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => $nowStr]);
        } else {
            \Illuminate\Support\Facades\DB::table('notifications')
                ->whereNull('read_at')
                ->update(['read_at' => $nowStr]);
        }

        return response()->json(['success' => true]);
    }
}
