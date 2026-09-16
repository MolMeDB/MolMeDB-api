<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $notifications = UserNotification::query()
            ->whereBelongsTo($user)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $notifications->map(fn (UserNotification $notification): array => [
                'id' => $notification->id,
                'state' => $notification->state,
                'title' => $notification->title,
                'body' => $notification->body,
                'created_at' => $notification->created_at?->toISOString(),
                'read_at' => $notification->read_at?->toISOString(),
            ]),
            'meta' => [
                'unread_count' => UserNotification::query()
                    ->whereBelongsTo($user)
                    ->where('state', UserNotification::STATE_NEW)
                    ->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        UserNotification::query()
            ->whereBelongsTo($user)
            ->where('state', UserNotification::STATE_NEW)
            ->update([
                'state' => UserNotification::STATE_READ,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Notifications marked as read.',
        ]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        UserNotification::query()
            ->whereBelongsTo($user)
            ->delete();

        return response()->json([
            'message' => 'Notifications cleared.',
        ]);
    }
}
