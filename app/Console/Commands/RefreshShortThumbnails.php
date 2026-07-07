<?php

namespace App\Console\Commands;

use App\Models\BlogShort;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RefreshShortThumbnails extends Command
{
    protected $signature = 'shorts:refresh-thumbnails';

    protected $description = 'Re-descarga thumbnails de TikTok que hayan expirado y los guarda localmente';

    public function handle(): int
    {
        $shorts = BlogShort::where('thumbnail', 'like', '%tiktokcdn.com%')->get();

        if ($shorts->isEmpty()) {
            $this->info('No se encontraron shorts con thumbnails de TikTok.');

            return self::SUCCESS;
        }

        $this->info("Procesando {$shorts->count()} shorts...");

        foreach ($shorts as $short) {
            $this->line("  [{$short->id}] {$short->title}");

            try {
                // 1. Get a fresh thumbnail URL from TikTok oEmbed
                $freshUrl = null;
                if ($short->url) {
                    $oembedUrl = 'https://www.tiktok.com/oembed?url='.urlencode($short->url);
                    $oembedResponse = Http::timeout(10)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Referer' => 'https://www.tiktok.com/',
                        ])
                        ->get($oembedUrl);

                    if ($oembedResponse->successful()) {
                        $freshUrl = $oembedResponse->json('thumbnail_url');
                    }
                }

                $downloadUrl = $freshUrl ?: $short->thumbnail;
                if (! $downloadUrl) {
                    $this->warn('    No hay URL de thumbnail para descargar');

                    continue;
                }

                // 2. Download the fresh thumbnail
                $response = Http::timeout(15)
                    ->withHeaders(['Referer' => 'https://www.tiktok.com/'])
                    ->get($downloadUrl);

                if ($response->failed()) {
                    $this->warn("    Falló la descarga (HTTP {$response->status()})");

                    continue;
                }

                // 3. Store locally
                $filename = 'shorts/'.$short->id.'_'.time().'.jpg';
                Storage::disk('public')->put($filename, $response->body());

                $short->updateQuietly(['thumbnail' => Storage::disk('public')->url($filename)]);

                $this->info('    ✓ Thumbnail actualizado');
            } catch (\Throwable $e) {
                $this->error("    Error: {$e->getMessage()}");
            }
        }

        $this->info('Proceso completado.');

        return self::SUCCESS;
    }
}
