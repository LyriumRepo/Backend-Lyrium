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
        Schema::table('reviews', function (Blueprint $table) {
            // La unique original (user_id, product_id) fue pensada solo para reseñas
            // de productos, pero bloqueaba calificar más de una reserva de servicio
            // del mismo servicio (product_id guarda el service id en ese caso).
            // La validación de "un review por producto" ya la hace ReviewController@store
            // a nivel de aplicación, así que es seguro quitarla aquí.
            // MySQL usa este índice compuesto para respaldar la FK de user_id,
            // así que primero creamos un índice plano que lo reemplace en ese rol.
            $table->index('user_id', 'reviews_user_id_index');
            $table->dropUnique(['user_id', 'product_id']);

            // En su lugar, una reserva de servicio solo puede calificarse una vez.
            // NULL no cuenta como duplicado en MySQL, así que esto no afecta
            // reseñas de productos (service_booking_id siempre null en esos casos).
            $table->unique('service_booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['service_booking_id']);
            $table->unique(['user_id', 'product_id']);
            $table->dropIndex('reviews_user_id_index');
        });
    }
};
