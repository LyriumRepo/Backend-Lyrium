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
