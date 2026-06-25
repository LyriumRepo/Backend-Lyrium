<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\NewOrderReceived;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderStatusChanged;
use App\Listeners\AccrueLiriosForOrder;
use App\Listeners\BroadcastNotificationCreated;
use App\Listeners\GenerateInvoicesForOrder;
use App\Listeners\SendNewOrderToSellerListener;
use App\Listeners\SendOrderConfirmationMailListener;
use App\Listeners\SendOrderTrackingEmailListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSent;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NewOrderReceived::class => [
            SendNewOrderToSellerListener::class,
        ],
        OrderPaymentConfirmed::class => [
            GenerateInvoicesForOrder::class,
            SendOrderConfirmationMailListener::class,
            AccrueLiriosForOrder::class,
        ],
        OrderStatusChanged::class => [
            SendOrderTrackingEmailListener::class,
        ],
        NotificationSent::class => [
            BroadcastNotificationCreated::class,
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
