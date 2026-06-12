<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::with('stores')->get();
foreach ($users as $u) {
    echo $u->id . ' | ' . $u->name . ' | ' . $u->email . ' | roles: ' . $u->getRoleNames()->implode(', ') . ' | stores: ' . $u->stores->pluck('id')->implode(',') . PHP_EOL;
}
