<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderReceiptValidated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $source, // manual | email
    ) {}
}
