<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\NewOrderReceived;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderStatusChanged;
use App\Listeners\GenerateInvoicesForOrder;
use App\Listeners\SendNewOrderToSellerListener;
use App\Listeners\SendOrderConfirmationMailListener;
use App\Listeners\SendOrderTrackingEmailListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NewOrderReceived::class => [
            SendNewOrderToSellerListener::class,
        ],
        OrderPaymentConfirmed::class => [
            GenerateInvoicesForOrder::class,
            SendOrderConfirmationMailListener::class,
        ],
        OrderStatusChanged::class => [
            SendOrderTrackingEmailListener::class,
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
