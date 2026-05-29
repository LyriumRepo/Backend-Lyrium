<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

try {
    $service = $app->make(App\Services\PaymentSchedulerService::class);
    $total = $service->getTotalPendingAmountByStore(1);
    echo "Total pending amount: $total\n";
    $nextDate = $service->getNextPaymentDate();
    echo "Next payment date: " . $nextDate->toIso8601String() . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
