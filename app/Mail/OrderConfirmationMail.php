<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


final class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Compra confirmada — Pedido #'.($this->order->order_number ?? $this->order->id),
        );
    }

    public function content(): Content
    {
        $order = $this->order->loadMissing(['items.product', 'user']);

        $items = $order->items->map(function ($item) {
            return (object) [
                'name' => $item->product_name,
                'description' => $item->product?->description ?? '',
                'quantity' => $item->quantity,
                'unit_price' => number_format((float) $item->unit_price, 2),
                'line_total' => number_format((float) $item->line_total, 2),
            ];
        });

        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $order,
                'customerName' => $order->user?->name ?? 'Cliente',
                'orderNumber' => $order->order_number ?? (string) $order->id,
                'items' => $items,
                'subtotal' => number_format((float) $order->subtotal, 2),
                'shippingCost' => number_format((float) ($order->shipping_cost ?? 0), 2),
                'discount' => number_format((float) ($order->discount_amount ?? 0), 2),
                'total' => number_format((float) $order->total, 2),
                'shippingAddress' => $order->shipping_address,
                'paymentMethod' => $order->payment_method,
            ]
        );
    }
}
