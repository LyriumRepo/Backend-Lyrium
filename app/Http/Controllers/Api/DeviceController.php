<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string|max:500',
            'platform' => 'nullable|string|in:web,ios,android|max:50',
            'device_name' => 'nullable|string|max:255',
        ]);

        $existing = UserDevice::where('user_id', $request->user()->id)
            ->where('fcm_token', $data['fcm_token'])
            ->first();

        if ($existing) {
            $existing->touch();
            return $this->success(['message' => 'Token already registered']);
        }

        $data['user_id'] = $request->user()->id;

        UserDevice::create($data);

        return $this->created(['message' => 'Device registered']);
    }

    public function unregister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string|max:500',
        ]);

        UserDevice::where('user_id', $request->user()->id)
            ->where('fcm_token', $data['fcm_token'])
            ->delete();

        return $this->success(['message' => 'Device unregistered']);
    }
}
