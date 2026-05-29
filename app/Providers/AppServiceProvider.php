<?php

namespace App\Providers;

use App\Listeners\BroadcastNotificationCreated;
use App\Services\RapifacService;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        Event::listen(NotificationSent::class, BroadcastNotificationCreated::class);
    }
}
