<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\Subscription;
use App\Services\IzipayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

class AutoRenewSubscriptionTest extends TestCase
{
    use RefreshDatabase;
    use WithRoles;

    private function makePlan(): Plan
    {
        return Plan::create([
            'name' => 'Crece',
            'slug' => 'crece-test',
            'monthly_fee' => 50,
            'commission_rate' => 0.10,
        ]);
    }

    public function test_enabling_auto_renew_without_a_tokenized_card_fails(): void
    {
        $this->seedRoles();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        $plan = $this->makePlan();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($seller)
            ->putJson("/api/subscriptions/{$subscription->id}/auto-renew", ['enabled' => true]);

        $response->assertStatus(422);
        $this->assertFalse($subscription->fresh()->auto_renew);
    }

    public function test_enabling_auto_renew_with_a_tokenized_card_succeeds(): void
    {
        $this->seedRoles();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        $plan = $this->makePlan();

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $paymentMethod = PaymentMethod::create([
            'user_id' => $seller->id,
            'tipo_metodo' => 'tarjeta',
            'titular' => 'Vendedor de prueba',
            'card_token' => 'mock_token_123',
            'card_last4' => '4242',
            'card_brand' => 'Visa',
            'token_status' => 'active',
        ]);

        $response = $this->actingAs($seller)
            ->putJson("/api/subscriptions/{$subscription->id}/auto-renew", [
                'enabled' => true,
                'payment_method_id' => $paymentMethod->id,
            ]);

        $response->assertStatus(200);
        $subscription->refresh();
        $this->assertTrue($subscription->auto_renew);
        $this->assertEquals($paymentMethod->id, $subscription->payment_method_id);
    }

    public function test_auto_renewal_command_charges_and_extends_subscription_in_mock_mode(): void
    {
        $this->seedRoles();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();
        $plan = $this->makePlan();

        $originalPlanRequest = PlanRequest::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'current_plan_id' => $plan->id,
            'payment_method' => PlanRequest::PAYMENT_METHOD_IZIPAY,
            'months' => 3,
            'total_amount' => 150,
            'payment_status' => PlanRequest::PAYMENT_STATUS_PAID,
            'status' => PlanRequest::STATUS_APPROVED,
        ]);

        $paymentMethod = PaymentMethod::create([
            'user_id' => $seller->id,
            'tipo_metodo' => 'tarjeta',
            'titular' => 'Vendedor de prueba',
            'card_token' => 'mock_token_123',
            'card_last4' => '4242',
            'card_brand' => 'Visa',
            'token_status' => 'active',
        ]);

        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subMonths(3),
            'ends_at' => now()->startOfDay(),
            'status' => 'active',
            'auto_renew' => true,
            'payment_method_id' => $paymentMethod->id,
            'plan_request_id' => $originalPlanRequest->id,
        ]);

        // El .env local de este proyecto apunta al sandbox real de Izipay
        // (IZIPAY_MOCK=false), así que forzamos una instancia en modo mock
        // para este test — de lo contrario el comando intentaría cobrar de
        // verdad contra el sandbox con un card_token falso.
        $this->app->instance(IzipayService::class, new IzipayService(mock: true));

        $this->artisan('app:process-plan-auto-renewals')->assertSuccessful();

        $subscription->refresh();
        $this->assertTrue($subscription->ends_at->isFuture());

        $renewalRequest = PlanRequest::where('store_id', $store->id)
            ->where('id', '!=', $originalPlanRequest->id)
            ->first();

        $this->assertNotNull($renewalRequest);
        $this->assertEquals(3, $renewalRequest->months);
        $this->assertEquals(PlanRequest::PAYMENT_STATUS_PAID, $renewalRequest->payment_status);
    }
}
