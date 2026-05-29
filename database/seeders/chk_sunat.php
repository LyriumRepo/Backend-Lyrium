<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$invoices = App\Models\Invoice::where('store_id', 1)->get();
foreach ($invoices as $inv) {
    echo "id={$inv->id} sunat_status='{$inv->sunat_status}' total={$inv->total}\n";
}
