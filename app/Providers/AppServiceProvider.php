<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\BroadcastNotificationCreated;
<<<<<<< HEAD
<<<<<<< HEAD
use App\Models\Expense;
use App\Observers\ExpenseObserver;
=======
use App\Services\RapifacService;
>>>>>>> 49185e4 (cambios recientes en backent para PreMain)
=======
use App\Services\RapifacService;
>>>>>>> rama-calderon
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Models\Review;
use App\Observers\ReviewObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RapifacService::class, fn () => RapifacService::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Notificaciones broadcast (existente)
        Event::listen(NotificationSent::class, BroadcastNotificationCreated::class);

        // Sincroniza suppliers.total_gastado y total_recibos automáticamente
        Expense::observe(ExpenseObserver::class);

        Review::observe(ReviewObserver::class);
    }
}
