<?php

use App\Models\Service;
use App\Models\Specialist;
use App\Models\ServiceSchedule;
use App\Models\ServiceBooking;
use App\Models\Order;
use App\Models\OrderServiceItem;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== INICIANDO CREACIÓN DE DATOS DE SERVICIOS ===\n\n";

// ─── 1. VERIFICAR PRERREQUISITOS ───────────────────────────────────────────

$store = Store::find(1);
echo "[OK] Store: {$store->trade_name} (ID:{$store->id})\n";

$customer1 = User::find(4);  // Cliente
$customer2 = User::find(7);  // Silva Manuel
echo "[OK] Customers: {$customer1->name} (ID:{$customer1->id}), {$customer2->name} (ID:{$customer2->id})\n";

$seller = User::find(3);  // Luis Torres
echo "[OK] Seller: {$seller->name} (ID:{$seller->id})\n";

$service = Service::find(48);
if (!$service) {
    // Create a demo service if 48 doesn't exist
    $service = Service::create([
        'store_id' => 1,
        'name' => 'Chequeo Preventivo Demo',
        'description' => 'Chequeo médico preventivo completo',
        'price' => 50.00,
        'duration_minutes' => 30,
        'status' => 'active',
        'is_home_service' => false,
        'booking_advance_hours' => 24,
        'max_capacity' => 1,
        'cancellation_policy' => 'flexible',
    ]);
    echo "[NEW] Created service ID:{$service->id} {$service->name}\n";
} else {
    // Update the existing service to active status
    $service->update(['status' => 'active']);
    echo "[OK] Service: {$service->name} (ID:{$service->id}) S/{$service->price}\n";
}

// Create a second service for variety
$service2 = Service::firstOrCreate(
    ['store_id' => 1, 'name' => 'Consulta Nutricional Online'],
    [
        'description' => 'Consulta personalizada con especialista en nutrición',
        'price' => 65.00,
        'duration_minutes' => 45,
        'status' => 'active',
        'is_home_service' => true,
        'booking_advance_hours' => 12,
        'max_capacity' => 1,
        'cancellation_policy' => 'flexible',
    ]
);
echo "[OK] Service2: {$service2->name} (ID:{$service2->id}) S/{$service2->price}\n";

// Create a third service (higher price)
$service3 = Service::firstOrCreate(
    ['store_id' => 1, 'name' => 'Terapia de Relajación'],
    [
        'description' => 'Sesión de terapia de relajación con aromaterapia',
        'price' => 80.00,
        'duration_minutes' => 60,
        'status' => 'active',
        'is_home_service' => true,
        'booking_advance_hours' => 24,
        'max_capacity' => 2,
        'cancellation_policy' => 'strict',
    ]
);
echo "[OK] Service3: {$service3->name} (ID:{$service3->id}) S/{$service3->price}\n";

echo "\n";

// ─── 2. CREAR ESPECIALISTAS ─────────────────────────────────────────────────

$spec1 = Specialist::firstOrCreate(
    ['store_id' => 1, 'document_number' => '12345678'],
    [
        'nombres' => 'Carlos',
        'apellidos' => 'Mendoza López',
        'document_type' => 'DNI',
        'email' => 'carlos.mendoza@example.com',
        'especialidad' => 'Medicina General',
        'availability' => 'Disponible',
    ]
);
echo "[OK] Specialist1: {$spec1->nombres} {$spec1->apellidos} (ID:{$spec1->id}) - {$spec1->especialidad}\n";

$spec2 = Specialist::firstOrCreate(
    ['store_id' => 1, 'document_number' => '87654321'],
    [
        'nombres' => 'María',
        'apellidos' => 'García Torres',
        'document_type' => 'DNI',
        'email' => 'maria.garcia@example.com',
        'especialidad' => 'Nutrición',
        'availability' => 'Disponible',
    ]
);
echo "[OK] Specialist2: {$spec2->nombres} {$spec2->apellidos} (ID:{$spec2->id}) - {$spec2->especialidad}\n";

echo "\n";

// ─── 3. CREAR SCHEDULES ────────────────────────────────────────────────────

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
$timeSlots = [
    ['start' => '09:00:00', 'end' => '13:00:00', 'bloque' => 1],
    ['start' => '14:00:00', 'end' => '18:00:00', 'bloque' => 2],
];

$schedulesCreated = 0;
$services_list = [$service, $service2, $service3];
$specialists_list = [$spec1, $spec2];

foreach ($services_list as $svc) {
    foreach ($days as $day) {
        foreach ($timeSlots as $slot) {
            $spec = ($svc->id === $service2->id || $svc->id === $service3->id) ? $spec2 : $spec1;
            ServiceSchedule::firstOrCreate(
                [
                    'service_id' => $svc->id,
                    'day_of_week' => $day,
                    'orden_bloque' => $slot['bloque'],
                    'specialist_id' => $spec->id,
                ],
                [
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'max_appointments' => 5,
                    'is_active' => true,
                ]
            );
            $schedulesCreated++;
        }
    }
}
echo "[OK] Created {$schedulesCreated} schedules for " . count($services_list) . " services\n";

// Attach specialists to services via pivot
foreach ($services_list as $svc) {
    $spec = ($svc->id === $service2->id || $svc->id === $service3->id) ? $spec2 : $spec1;
    if (!$svc->specialists()->where('specialist_id', $spec->id)->exists()) {
        $svc->specialists()->attach($spec->id);
    }
}
echo "[OK] Specialists attached to services via pivot\n\n";

// ─── 4. CREAR BOOKINGS + ORDERS + ORDER_SERVICE_ITEMS ────────────────────

// Helper function to create a complete booking chain
function createServiceBookingChain(
    Service $service,
    User $customer,
    Specialist $specialist,
    ServiceSchedule $schedule,
    string $appointmentDate,
    string $status,
    string $paymentMethod,
    string $paymentStatus
): array {
    return DB::transaction(function () use ($service, $customer, $specialist, $schedule, $appointmentDate, $status, $paymentMethod, $paymentStatus) {
        // 1. Create the ServiceBooking
        $booking = ServiceBooking::create([
            'service_id' => $service->id,
            'user_id' => $customer->id,
            'specialist_id' => $specialist->id,
            'schedule_id' => $schedule->id,
            'appointment_date' => $appointmentDate,
            'status' => $status,
            'total_price' => $service->price,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'reschedule_token' => Str::random(64),
            'confirmed_at' => in_array($status, ['confirmed', 'completed']) ? now() : null,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ]);

        // 2. Create the Order
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'order_type' => 'service',
            'user_id' => $customer->id,
            'status' => $status === 'completed' ? 'delivered' : ($status === 'cancelled' ? 'cancelled' : ($status === 'confirmed' ? 'confirmed' : 'pending_seller')),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'subtotal' => $service->price,
            'shipping_cost' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => $service->price,
        ]);

        // 3. Create the OrderServiceItem
        $serviceItem = OrderServiceItem::create([
            'order_id' => $order->id,
            'service_booking_id' => $booking->id,
            'service_id' => $service->id,
            'store_id' => $service->store_id,
            'specialist_id' => $specialist->id,
            'service_name' => $service->name,
            'quantity' => 1,
            'unit_price' => $service->price,
            'line_total' => $service->price,
            'status' => $status === 'completed' ? 'delivered' : ($status === 'cancelled' ? 'cancelled' : ($status === 'confirmed' ? 'confirmed' : 'pending_seller')),
            'appointment_date' => $appointmentDate,
            'modality' => $service->is_home_service ? 'domicilio' : 'presencial',
            'duration_minutes' => $service->duration_minutes,
            'service_snapshot' => json_encode([
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'duration_minutes' => $service->duration_minutes,
            ]),
            'store_name_snapshot' => 'BioTienda Demo',
            'specialist_name_snapshot' => "{$specialist->nombres} {$specialist->apellidos}",
        ]);

        return ['booking' => $booking, 'order' => $order, 'serviceItem' => $serviceItem];
    });
}

// Get schedules for each service
$schedSvc1 = ServiceSchedule::where('service_id', $service->id)->where('day_of_week', 'monday')->where('orden_bloque', 1)->first();
$schedSvc2 = ServiceSchedule::where('service_id', $service2->id)->where('day_of_week', 'tuesday')->where('orden_bloque', 1)->first();
$schedSvc3 = ServiceSchedule::where('service_id', $service3->id)->where('day_of_week', 'wednesday')->where('orden_bloque', 1)->first();

// If any schedule is missing, use the first available one for that service
if (!$schedSvc1) $schedSvc1 = ServiceSchedule::where('service_id', $service->id)->first();
if (!$schedSvc2) $schedSvc2 = ServiceSchedule::where('service_id', $service2->id)->first();
if (!$schedSvc3) $schedSvc3 = ServiceSchedule::where('service_id', $service3->id)->first();

echo "=== CREATING SERVICE BOOKINGS ===\n\n";

// Booking 1: pending - Chequeo Preventivo (customer1)
$r1 = createServiceBookingChain(
    $service, $customer1, $spec1, $schedSvc1,
    now()->addDay()->format('Y-m-d') . ' 10:00:00',
    'pending', 'yape', 'pending'
);
echo "[BOOKING 1] {$service->name} | Customer: {$customer1->name} | Status: pending\n";
echo "  Booking ID:{$r1['booking']->id} | Order ID:{$r1['order']->id} ({$r1['order']->order_number}) | OrderSvcItem ID:{$r1['serviceItem']->id}\n";

// Booking 2: confirmed - Consulta Nutricional (customer2)
$r2 = createServiceBookingChain(
    $service2, $customer2, $spec2, $schedSvc2,
    now()->addDays(2)->format('Y-m-d') . ' 15:00:00',
    'confirmed', 'tarjeta', 'paid'
);
echo "[BOOKING 2] {$service2->name} | Customer: {$customer2->name} | Status: confirmed\n";
echo "  Booking ID:{$r2['booking']->id} | Order ID:{$r2['order']->id} ({$r2['order']->order_number}) | OrderSvcItem ID:{$r2['serviceItem']->id}\n";

// Booking 3: completed - Terapia de Relajación (customer1)
$r3 = createServiceBookingChain(
    $service3, $customer1, $spec2, $schedSvc3,
    now()->subDays(2)->format('Y-m-d') . ' 11:00:00',
    'completed', 'transferencia', 'paid'
);
echo "[BOOKING 3] {$service3->name} | Customer: {$customer1->name} | Status: completed\n";
echo "  Booking ID:{$r3['booking']->id} | Order ID:{$r3['order']->id} ({$r3['order']->order_number}) | OrderSvcItem ID:{$r3['serviceItem']->id}\n";

// Booking 4: cancelled - Chequeo Preventivo (customer2)
$r4 = createServiceBookingChain(
    $service, $customer2, $spec1, $schedSvc1,
    now()->addDays(5)->format('Y-m-d') . ' 09:00:00',
    'cancelled', 'yape', 'refunded'
);
echo "[BOOKING 4] {$service->name} | Customer: {$customer2->name} | Status: cancelled\n";
echo "  Booking ID:{$r4['booking']->id} | Order ID:{$r4['order']->id} ({$r4['order']->order_number}) | OrderSvcItem ID:{$r4['serviceItem']->id}\n";

// Booking 5: pending - Consulta Nutricional (customer1) - different payment method
$r5 = createServiceBookingChain(
    $service2, $customer1, $spec2, $schedSvc2,
    now()->addDays(3)->format('Y-m-d') . ' 16:00:00',
    'pending', 'efectivo', 'pending'
);
echo "[BOOKING 5] {$service2->name} | Customer: {$customer1->name} | Status: pending\n";
echo "  Booking ID:{$r5['booking']->id} | Order ID:{$r5['order']->id} ({$r5['order']->order_number}) | OrderSvcItem ID:{$r5['serviceItem']->id}\n";

// Booking 6: confirmed - Terapia de Relajación (customer2) - different schedule
$schedSvc3Alt = ServiceSchedule::where('service_id', $service3->id)->where('day_of_week', 'thursday')->where('orden_bloque', 1)->first();
if (!$schedSvc3Alt) $schedSvc3Alt = ServiceSchedule::where('service_id', $service3->id)->where('day_of_week', '!=', 'wednesday')->first();
if (!$schedSvc3Alt) $schedSvc3Alt = $schedSvc3;

$r6 = createServiceBookingChain(
    $service3, $customer2, $spec2, $schedSvc3Alt,
    now()->addDays(4)->format('Y-m-d') . ' 14:30:00',
    'confirmed', 'tarjeta', 'paid'
);
echo "[BOOKING 6] {$service3->name} | Customer: {$customer2->name} | Status: confirmed\n";
echo "  Booking ID:{$r6['booking']->id} | Order ID:{$r6['order']->id} ({$r6['order']->order_number}) | OrderSvcItem ID:{$r6['serviceItem']->id}\n";

echo "\n";

// ─── 5. CREAR UNA ORDEN MIXTA ─────────────────────────────────────────────

echo "=== CREATING MIXED ORDER (PRODUCTS + SERVICES) ===\n\n";

DB::transaction(function () use ($customer1) {
    // Create a new order with both product items and service items
    $mixedOrder = Order::create([
        'order_number' => Order::generateOrderNumber(),
        'order_type' => 'mixed',
        'user_id' => $customer1->id,
        'status' => 'pending_seller',
        'payment_method' => 'tarjeta',
        'payment_status' => 'pending',
        'subtotal' => 150.00,
        'shipping_cost' => 15.00,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 165.00,
        'shipping_name' => $customer1->name,
        'shipping_address' => 'Av. Principal 123, Lima',
        'shipping_city' => 'Lima',
        'shipping_phone' => '999888777',
    ]);

    // Add product items
    $product1 = Product::where('store_id', 1)->where('status', 'approved')->first();
    $mixedOrder->items()->create([
        'product_id' => $product1->id,
        'store_id' => 1,
        'product_name' => $product1->name,
        'unit_price' => $product1->price,
        'quantity' => 1,
        'line_total' => $product1->price,
        'status' => 'pending_seller',
    ]);

    // Add a service item (without a booking - standalone service in mixed order)
    $mixedService = Service::where('store_id', 1)->where('name', 'Chequeo Preventivo Demo')->first();
    $mixedOrder->serviceItems()->create([
        'service_booking_id' => null,
        'service_id' => $mixedService->id,
        'store_id' => 1,
        'service_name' => $mixedService->name,
        'quantity' => 1,
        'unit_price' => $mixedService->price,
        'line_total' => $mixedService->price,
        'status' => 'pending_seller',
        'modality' => 'presencial',
        'duration_minutes' => $mixedService->duration_minutes,
        'service_snapshot' => json_encode([
            'id' => $mixedService->id,
            'name' => $mixedService->name,
            'price' => $mixedService->price,
        ]),
        'store_name_snapshot' => 'BioTienda Demo',
    ]);

    echo "[MIXED ORDER] ID:{$mixedOrder->id} ({$mixedOrder->order_number})\n";
    echo "  Type: {$mixedOrder->order_type} | Status: {$mixedOrder->status} | Total: S/{$mixedOrder->total}\n";
    echo "  Products: {$mixedOrder->items->count()} | Services: {$mixedOrder->serviceItems->count()}\n";
});

echo "\n=== CREACIÓN COMPLETADA ===\n\n";
echo "Resumen:\n";
echo "  Services created/updated: 3\n";
echo "  Specialists created: 2\n";
echo "  Schedules created: {$schedulesCreated}\n";
echo "  Service Bookings created: 6\n";
echo "  Service Orders created: 6\n";
echo "  Order Service Items created: 6\n";
echo "  Mixed Orders created: 1\n";
