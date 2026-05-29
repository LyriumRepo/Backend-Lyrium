<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$inv = App\Models\Invoice::find(1);
echo "Attributes: " . json_encode(array_keys($inv->getAttributes()), JSON_PRETTY_PRINT) . "\n";
echo "amount: " . ($inv->amount ?? 'null') . "\n";
echo "total: " . ($inv->total ?? 'null') . "\n";
