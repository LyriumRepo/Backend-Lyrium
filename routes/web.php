<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Página pública de verificación del recibo de suscripción (enlazada desde el QR del PDF)
Route::get('/verificar/recibo/{invoice}', [\App\Http\Controllers\Api\NubefactController::class, 'verifyReceipt'])
    ->name('plan-receipt.verify')
    ->middleware('signed');
