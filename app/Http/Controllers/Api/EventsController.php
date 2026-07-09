<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class EventsController extends Controller
{
    public function stream(Request $request): Response
    {
        $channel = $request->query('channel', 'global');
        $userId = $request->query('user_id');

        $origin = $request->headers->get('Origin', 'http://localhost:3000');

        $response = new Response(null, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Access-Control-Allow-Origin' => $origin,  // ← NUEVO
            'Access-Control-Allow-Credentials' => 'true',   // ← NUEVO
        ]);

        $response->send();

        echo "event: conectado\n";
        echo 'data: '.json_encode([
            'channel' => $channel,
            'user_id' => $userId,
            'timestamp' => now()->toIso8601String(),
        ])."\n\n";
        flush();
        if (ob_get_level()) {
            ob_flush();
        }

        // Liberar sesión para evitar que el SSE bloquee otras peticiones del mismo usuario
        session()->save();

        $maxDuration = 60;
        $startTime = time();
        $lastHeartbeat = time();

        while ((time() - $startTime) < $maxDuration) {
            echo ":\n\n";
            flush();

            if (connection_aborted()) {
                break;
            }

            if (time() - $lastHeartbeat >= 30) {
                echo ": heartbeat\n\n";
                $lastHeartbeat = time();
                flush();
            }
            usleep(200000);
        }

        return $response;
    }
}
