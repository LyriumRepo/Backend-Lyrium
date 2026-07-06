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

    try {
        app(\App\Services\AuditService::class)->record(
            event: \App\Catalogs\SystemEvents::SCHEDULER_EXECUTED,
            module: 'system',
            description: 'Tarea programada ejecutada: cleanup-expired-otp',
            severity: 'info',
            source: \App\Services\AuditService::SOURCE_SCHEDULER,
            metadata: ['command' => 'cleanup-expired-otp'],
        );
    } catch (\Throwable) {
        // Silent fail — never interrupt the scheduled task
    }
})->hourly()->name('cleanup-expired-otp');

// Verificar tiendas pendientes con SLA > 72 horas y notificar a admins
Schedule::command('stores:check-sla')->everySixHours()->name('check-pending-stores-sla');

// Enviar notificaciones de cumpleaños (email + push) a las 8:00 AM cada día
Schedule::command('birthday:send')->dailyAt('08:00')->name('send-birthday-notifications');

// Recordatorio anticipado de cumpleaños 14 días antes (solo correo)
Schedule::command('birthday:advance')->dailyAt('08:00')->name('birthday-advance-reminders');

// Recordatorios del panel de cliente: días 7, 30 y 90 tras la primera compra
Schedule::command('customers:panel-reminders')->dailyAt('09:00')->name('customer-panel-reminders');

// Renovación automática de planes vencidos hoy con auto_renew activado
// (corre antes del recordatorio de las 09:00 para no avisar dos veces si ya se renovó solo)
Schedule::command('app:process-plan-auto-renewals')->dailyAt('07:00')->name('process-plan-auto-renewals')->withoutOverlapping();

// Recordatorios de plan por vencer (7, 3 y 1 día antes)
Schedule::command('app:send-plan-expiring-reminders')->dailyAt('09:00')->name('plan-expiring-reminders');

// Recordatorios de cupones por vencer (3 y 1 día antes)
Schedule::command('app:send-coupon-expiring-reminders')->dailyAt('09:00')->name('coupon-expiring-reminders');

// Detectar cambios en Top 100 y actualizar medallas
Schedule::command('top-medals:detect')->dailyAt('03:00')->name('top-medals-detection')->withoutOverlapping();

// Archivar registros de auditoría > 12 meses el día 1 de cada mes
Schedule::command('audit:archive')->monthlyOn(1, '03:00')->name('audit-archive')->withoutOverlapping();

// Purgar registros de auditoría > 36 meses cada 3 meses
Schedule::command('audit:purge --months=36')->cron('0 4 1 1,4,7,10 *')->name('audit-purge')->withoutOverlapping();

// Actualizar resúmenes diarios de auditoría cada hora
Schedule::command('audit:summarize')->hourly()->name('audit-summarize')->withoutOverlapping();
