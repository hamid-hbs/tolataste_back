<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = AppNotification::orderBy('read')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['notifications' => $notifications]);
    }

    public function markRead(AppNotification $notification): JsonResponse
    {
        $notification->update(['read' => true]);

        return response()->json(['notification' => $notification->fresh()]);
    }
}
