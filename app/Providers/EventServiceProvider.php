<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AuditLogCreated;
use App\Events\CriticalSecurityEvent;
use App\Events\NewOrderReceived;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderStatusChanged;
use App\Events\RepeatedFailedLoginEvent;
use App\Listeners\AccrueLiriosForOrder;
use App\Listeners\AutoBlockIpListener;
use App\Listeners\BroadcastNotificationCreated;
use App\Listeners\GenerateInvoicesForOrder;
use App\Listeners\LogBlockedIpListener;
use App\Listeners\LogFailedJobListener;
use App\Listeners\LogMaintenanceModeListener;
use App\Listeners\LogSecurityAlertListener;
use App\Listeners\SendNewOrderToSellerListener;
use App\Listeners\SendOrderConfirmationMailListener;
use App\Listeners\SendOrderTrackingEmailListener;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;

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
        JobFailed::class => [
            LogFailedJobListener::class,
        ],
        AuditLogCreated::class => [
            LogBlockedIpListener::class,
        ],
        CriticalSecurityEvent::class => [
            LogSecurityAlertListener::class,
        ],
        RepeatedFailedLoginEvent::class => [
            AutoBlockIpListener::class,
        ],
    ];

    public function boot(): void
    {
        Event::listen(
            MaintenanceModeEnabled::class,
            [LogMaintenanceModeListener::class, 'handleEnabled'],
        );

        Event::listen(
            MaintenanceModeDisabled::class,
            [LogMaintenanceModeListener::class, 'handleDisabled'],
        );
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
