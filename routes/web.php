<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Página pública de verificación del recibo de suscripción (enlazada desde el QR del PDF)
Route::get('/verificar/recibo/{invoice}', [\App\Http\Controllers\Api\NubefactController::class, 'verifyReceipt'])
    ->name('plan-receipt.verify')
    ->middleware('signed');

// Validación de recepción con un clic desde el email (URL firmada temporal, sin login)
Route::get('/validar/pedido/{order}', [\App\Http\Controllers\ReceiptValidationWebController::class, 'validateOrder'])
    ->name('receipt.validate.order')
    ->middleware('signed');

Route::get('/validar/reserva/{booking}', [\App\Http\Controllers\ReceiptValidationWebController::class, 'validateBooking'])
    ->name('receipt.validate.booking')
    ->middleware('signed');
