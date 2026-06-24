<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración 3/4 — Triple Google Calendar en Reservas
 *
 * El sistema actual guarda solo UN google_event_id (el del especialista).
 * Para cumplir el requisito de sincronizar 3 calendarios, necesitamos
 * guardar los IDs de los 3 eventos de forma independiente:
 *
 *  - google_event_id          (ya existe) → evento en el calendario del ESPECIALISTA
 *  - google_event_id_client   (NUEVO)     → evento en el calendario del CLIENTE
 *  - google_event_id_seller   (NUEVO)     → evento en el calendario del VENDEDOR/STORE
 *
 * Tener los 3 IDs separados permite:
 *  ✓ Cancelar/actualizar cada evento de forma independiente.
 *  ✓ Si el cliente no tiene Google Calendar conectado, google_event_id_client
 *    queda en NULL sin afectar los otros dos.
 *  ✓ Trazabilidad en logs por tipo de calendario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {

            // Evento en el calendario del CLIENTE.
            // NULL si el cliente no tiene Google Calendar conectado (no bloqueante).
            $table->string('google_event_id_client')
                ->nullable()
                ->after('google_event_id')
                ->comment('ID del evento en el Google Calendar del cliente');

            // Evento en el calendario del VENDEDOR / STORE.
            // NULL si la tienda no ha conectado su Google Calendar.
            $table->string('google_event_id_seller')
                ->nullable()
                ->after('google_event_id_client')
                ->comment('ID del evento en el Google Calendar del vendedor/tienda');
        });
    }

    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'google_event_id_client',
                'google_event_id_seller',
            ]);
        });
    }
};
