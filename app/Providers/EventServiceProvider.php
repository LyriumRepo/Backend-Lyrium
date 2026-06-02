<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\NewOrderReceived;
use App\Events\OrderPaymentConfirmed;
use App\Listeners\GenerateInvoicesForOrder;
use App\Listeners\SendNewOrderToSellerListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NewOrderReceived::class => [
            SendNewOrderToSellerListener::class,
        ],
        OrderPaymentConfirmed::class => [
            GenerateInvoicesForOrder::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
