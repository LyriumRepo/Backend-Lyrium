<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\BroadcastNotificationCreated;
use App\Models\Expense;
use App\Observers\ExpenseObserver;
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
        //
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
