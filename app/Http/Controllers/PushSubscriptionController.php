<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Store a new push subscription.
     */
    public function subscribe(Request $request)
    {
        \Log::info('Push Subscription Request Received', ['data' => $request->all()]);
        $request->validate([
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required'
        ]);

        $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();

        if (!$user) {
            \Log::warning('Push Subscription failed: User not authenticated');
            return response()->json(['success' => false, 'message' => 'User not found.'], 401);
        }

        $user->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth']
        );

        \Log::info('Push Subscription Saved for User: ' . $user->name);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a push subscription.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required'
        ]);

        $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();

        if ($user) {
            $user->deletePushSubscription($request->endpoint);
        }

        return response()->json(['success' => true]);
    }
}
