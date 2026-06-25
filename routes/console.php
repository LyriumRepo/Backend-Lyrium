<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Limpiar códigos OTP expirados cada hora
Schedule::call(function () {
    \App\Models\EmailVerificationCode::where('expires_at', '<', now())->delete();
})->hourly()->name('cleanup-expired-otp');

// Verificar tiendas pendientes con SLA > 72 horas y notificar a admins
Schedule::command('stores:check-sla')->everySixHours()->name('check-pending-stores-sla');

// Enviar notificaciones de cumpleaños (email + push) a las 8:00 AM cada día
Schedule::command('birthday:send')->dailyAt('08:00')->name('send-birthday-notifications');

// Recordatorio anticipado de cumpleaños 14 días antes (solo correo)
Schedule::command('birthday:advance')->dailyAt('08:00')->name('birthday-advance-reminders');

// Recordatorios del panel de cliente: días 7, 30 y 90 tras la primera compra
Schedule::command('customers:panel-reminders')->dailyAt('09:00')->name('customer-panel-reminders');

// Recordatorios de plan por vencer (7, 3 y 1 día antes)
Schedule::command('app:send-plan-expiring-reminders')->dailyAt('09:00')->name('plan-expiring-reminders');

// Recordatorios de cupones por vencer (3 y 1 día antes)
Schedule::command('app:send-coupon-expiring-reminders')->dailyAt('09:00')->name('coupon-expiring-reminders');

// Detectar cambios en Top 100 y actualizar medallas
Schedule::command('top-medals:detect')->dailyAt('03:00')->name('top-medals-detection')->withoutOverlapping();
