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

        $request->headers->set('Content-Type', 'text/event-stream');
        $request->headers->set('Cache-Control', 'no-cache');
        $request->headers->set('Connection', 'keep-alive');

        $response = new Response(null, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);

        $response->send();

        $sentEvents = [];

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

        $maxDuration = 300;
        $startTime = time();
        $lastHeartbeat = time();

        while ((time() - $startTime) < $maxDuration) {
            if (connection_aborted()) {
                break;
            }

            if (time() - $lastHeartbeat >= 30) {
                echo ": heartbeat\n\n";
                $lastHeartbeat = time();
                flush();
            }

            usleep(100000);
        }

        return $response;
    }
}
