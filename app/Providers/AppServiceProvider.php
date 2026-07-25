<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\InvoiceProviderInterface;
use App\Models\BlockedIp;
use App\Models\Expense;
use App\Models\Review;
use App\Observers\ExpenseObserver;
use App\Observers\ReviewObserver;
use App\Services\DocumentScanner\OcrTextExtractor;
use App\Services\DocumentScanner\SpatieTextExtractor;
use App\Services\IzipayService;
use App\Services\NubefactProvider;
use App\Services\NubefactService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InvoiceProviderInterface::class, fn () => NubefactProvider::fromConfig());
        $this->app->bind(NubefactService::class, fn () => NubefactService::fromConfig());
        $this->app->bind(IzipayService::class, fn () => IzipayService::fromConfig());

        // Rutas explícitas a los binarios externos usados para escanear PDFs.
        // Sin esto, exec()/shell_exec() dependen del PATH del proceso que corre
        // el servidor (Apache/XAMPP, artisan serve, etc.), que puede no incluir
        // las mismas rutas que la terminal del desarrollador.
        //
        // NOTA: se usa bind() directo (no when()->needs()) porque
        // DocumentScannerService construye estas clases con "new X" como valor
        // por defecto del parámetro del constructor; el contenedor de Laravel
        // solo resuelve vía make() (y por tanto aplica bindings contextuales)
        // si la clase está bindeada directamente — si no, usa el default de PHP
        // y nunca pasa por el contenedor.
        $this->app->bind(
            SpatieTextExtractor::class,
            fn () => new SpatieTextExtractor(env('PDFTOTEXT_PATH')),
        );

        $this->app->bind(
            OcrTextExtractor::class,
            fn () => new OcrTextExtractor(env('TESSERACT_PATH', 'tesseract')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sincroniza suppliers.total_gastado y total_recibos automáticamente
        Expense::observe(ExpenseObserver::class);

        Review::observe(ReviewObserver::class);

        RateLimiter::for('login', function (Request $request) {
            $ip = (string) $request->ip();

            if ($this->isWhitelistedIp($ip)) {
                return Limit::none();
            }

            return [
                Limit::perMinute(10)->by("login:{$ip}"),
                Limit::perMinutes(60, 20)->by("login:{$ip}"),
            ];
        });

        RateLimiter::for('api', function (Request $request) {
            $key = (string) ($request->user()?->id ?? $request->ip());

            if ($this->isWhitelistedIp((string) $request->ip())) {
                return Limit::none();
            }

            return Limit::perMinute(120)->by("api:{$key}");
        });

        RateLimiter::for('sensitive', function (Request $request) {
            $key = (string) ($request->user()?->id ?? $request->ip());

            if ($this->isWhitelistedIp((string) $request->ip())) {
                return Limit::none();
            }

            return Limit::perMinute(5)->by("sensitive:{$key}");
        });
    }

    private function isWhitelistedIp(string $ip): bool
    {
        return BlockedIp::byIp($ip)
            ->where('status', BlockedIp::STATUS_WHITELISTED)
            ->exists();
    }
}
