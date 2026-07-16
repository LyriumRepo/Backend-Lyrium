<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DeviceDetector\DeviceDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DebugCloudflareController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ua = $request->userAgent() ?? '';
        $dd = new DeviceDetector($ua);
        $dd->parse();
        $client = $dd->getClient();
        $os = $dd->getOs();

        return response()->json([
            'ip' => [
                'request_ip' => $request->ip(),
                'server_remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
                'server_remote_host' => $_SERVER['REMOTE_HOST'] ?? 'N/A',
            ],
            'user_agent' => [
                'raw' => $request->userAgent(),
                'header_user_agent' => $request->header('User-Agent'),
            ],
            'cloudflare_headers' => collect($request->headers->all())
                ->filter(fn ($v, $k) => preg_match('/^(cf-|x-forwarded-|x-real-)/i', $k))
                ->map(fn ($v) => is_array($v) ? implode(', ', $v) : $v)
                ->toArray(),
            'all_headers' => collect($request->headers->all())
                ->map(fn ($v) => is_array($v) ? implode(', ', $v) : $v)
                ->toArray(),
            'server' => [
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
                'remote_host' => $_SERVER['REMOTE_HOST'] ?? 'N/A',
                'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
            ],
            'agent_detection' => [
                'device' => $dd->getBrandName()
                    ? ($dd->getModel()
                        ? (str_contains($dd->getModel(), $dd->getBrandName())
                            ? $dd->getModel()
                            : $dd->getBrandName() . ' ' . $dd->getModel())
                        : $dd->getBrandName())
                    : ucfirst($dd->getDeviceName() ?: 'Desconocido'),
                'brand' => $dd->getBrandName(),
                'model' => $dd->getModel(),
                'device_type' => $dd->getDeviceName(),
                'platform' => $os['name'] ?? 'Desconocido',
                'platform_version' => $os['version'] ?? '',
                'browser' => $client['name'] ?? 'Desconocido',
                'browser_version' => $client['version'] ?? '',
                'is_mobile' => $dd->isMobile(),
                'is_desktop' => $dd->isDesktop(),
            ],
            'interpretation' => [
                'ip_actual' => $request->ip(),
                'explicacion' => 'Si ip_actual es 127.0.0.1, TrustProxies no está funcionando. '
                    . 'Si ves una IP pública, TrustProxies está usando CF-Connecting-IP o X-Forwarded-For correctamente.',
                'nota_android_linux' => 'Android aparece como "Linux" en el User-Agent porque '
                    . 'el sistema operativo Android está construido sobre el kernel Linux. '
                    . 'La regex actual busca "Linux" en el UA y lo reporta como dispositivo, '
                    . 'pero con jenssegers/agent se detecta correctamente como Android.',
            ],
        ]);
    }
}
