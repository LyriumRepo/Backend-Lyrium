<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Registrar rutas de broadcasting con middleware API + Sanctum (sin CSRF)
Broadcast::routes(['middleware' => ['api', 'auth:sanctum']]);

// Canal por defecto de Laravel (notificaciones del modelo User)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado por usuario (notificaciones, tickets)
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

// Canal privado por ticket (mensajes en tiempo real — customer + seller + admin)
Broadcast::channel('ticket.{ticketId}', function (User $user, int $ticketId) {
    $ticket = \App\Models\Ticket::find($ticketId);
    if (! $ticket) {
        return false;
    }

    // Creador del ticket (customer)
    if ($user->id === $ticket->user_id) {
        return true;
    }

    // Admin
    if ($user->hasRole('administrator')) {
        return true;
    }

    // Seller dueño de la tienda asociada al ticket
    if ($ticket->store_id && $user->stores()->where('stores.id', $ticket->store_id)->exists()) {
        return true;
    }

    return false;
});

// Canal privado por tienda (órdenes, bookings, estado de plan, productos)
Broadcast::channel('store.{storeId}', function (User $user, int $storeId) {
    return $user->stores()->where('stores.id', $storeId)->exists()
        || $user->hasRole('administrator');
});
