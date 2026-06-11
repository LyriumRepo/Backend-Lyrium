<?php

use App\Models\User;
use App\Models\Order;
use App\Http\Resources\OrderResource;

// Generate a Sanctum token for testing
$user = User::find(4); // Cliente
$token = $user->createToken('api-test')->plainTextToken;
echo "=== TOKEN PARA PRUEBAS ===\n";
echo "{$token}\n\n";

// Test OrderResource output
echo "=== ORDER RESOURCE OUTPUT (Order ID:9 - MIXED) ===\n";
$order = Order::with(['items', 'serviceItems', 'user'])->find(9);
$resource = new OrderResource($order);
echo json_encode($resource->toArray(request()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== ORDER RESOURCE OUTPUT (Order ID:3 - SERVICE) ===\n";
$order3 = Order::with(['items', 'serviceItems', 'user'])->find(3);
$resource3 = new OrderResource($order3);
echo json_encode($resource3->toArray(request()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== ORDER RESOURCE OUTPUT (Order ID:1 - PRODUCT) ===\n";
$order1 = Order::with(['items', 'serviceItems', 'user'])->find(1);
$resource1 = new OrderResource($order1);
echo json_encode($resource1->toArray(request()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
