<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Settings;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['settings' => Settings::config()]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = Settings::put($request->validated());

        ActivityLog::record($request->user(), 'settings_updated', $request->validated());

        return response()->json(['settings' => $settings->data]);
    }
}
