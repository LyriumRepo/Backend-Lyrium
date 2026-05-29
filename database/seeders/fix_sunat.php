<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$fixed = Illuminate\Support\Facades\DB::table('invoices')
    ->where('store_id', 1)
    ->where('sunat_status', 'ACEPTADA')
    ->update(['sunat_status' => App\Models\Invoice::SUNAT_STATUS_ACCEPTED]);

echo "Fixed $fixed invoices: ACEPTADA -> ACCEPTED\n";

// Verify
$invoices = App\Models\Invoice::where('store_id', 1)->get();
foreach ($invoices as $inv) {
    echo "id={$inv->id} sunat_status='{$inv->sunat_status}' total={$inv->total}\n";
}
