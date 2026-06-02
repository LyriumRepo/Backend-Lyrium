<?php

namespace App\Providers;

use App\Contracts\InvoiceProviderInterface;
use App\Listeners\BroadcastNotificationCreated;
use App\Services\IzipayService;
use App\Services\NubefactProvider;
use App\Services\NubefactService;
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
        $this->app->bind(InvoiceProviderInterface::class, fn () => NubefactProvider::fromConfig());
        $this->app->bind(NubefactService::class, fn () => NubefactService::fromConfig());
        $this->app->bind(IzipayService::class, fn () => IzipayService::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(NotificationSent::class, BroadcastNotificationCreated::class);
    }
}
