<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── stores ────────────────────────────────────────────────────────────
        Schema::table('stores', function (Blueprint $table) {
            // ID del calendario de Google de la tienda (ej: "primary" o email)
            $table->string('google_calendar_id', 255)
                ->nullable()
                ->after('website')
                ->comment('Google Calendar ID de la tienda (primary o email del calendario)');

            // Token OAuth2 completo (JSON con access_token, refresh_token, expires_in, etc.)
            $table->text('google_calendar_token')
                ->nullable()
                ->after('google_calendar_id')
                ->comment('Token OAuth2 de Google Calendar (JSON cifrado)');
        });

        // ── services ──────────────────────────────────────────────────────────
        Schema::table('services', function (Blueprint $table) {
            // Slug para URLs amigables (antes se generaba dinámicamente en el Resource)
            $table->string('slug', 255)
                ->nullable()
                ->after('name')
                ->comment('Slug URL-friendly generado desde name');

            // Permite que un servicio use un calendario distinto al de la tienda (opcional)
            $table->string('google_calendar_id', 255)
                ->nullable()
                ->after('settings')
                ->comment('Calendario específico del servicio (hereda de store si es null)');
        });

        // ── service_bookings ──────────────────────────────────────────────────
        Schema::table('service_bookings', function (Blueprint $table) {
            // ID del evento creado en Google Calendar
            $table->string('google_event_id', 255)
                ->nullable()
                ->after('reschedule_token')
                ->comment('ID del evento en Google Calendar');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['google_calendar_id', 'google_calendar_token']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['slug', 'google_calendar_id']);
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn('google_event_id');
        });

    }
};
