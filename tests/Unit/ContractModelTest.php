<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContractModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_urgency_normal_when_no_end_date(): void
    {
        $contract = Contract::factory()->create(['end_date' => null]);

        $this->assertEquals('normal', $contract->expiry_urgency);
    }

    public function test_expiry_urgency_normal_when_far_future(): void
    {
        $contract = Contract::factory()->create(['end_date' => now()->addMonths(6)]);

        $this->assertEquals('normal', $contract->fresh()->expiry_urgency);
    }

    public function test_expiry_urgency_warning_within_15_days(): void
    {
        $contract = Contract::factory()->create(['end_date' => now()->addDays(10)]);

        $this->assertEquals('warning', $contract->expiry_urgency);
    }

    public function test_expiry_urgency_critical_when_past(): void
    {
        $contract = Contract::factory()->create(['end_date' => now()->subDay()]);

        $this->assertEquals('critical', $contract->expiry_urgency);
    }

    public function test_add_audit_entry_creates_trail(): void
    {
        $contract = Contract::factory()->create();

        $contract->addAuditEntry('Test Action', 'Test User');

        $this->assertDatabaseHas('contract_audit_trails', [
            'contract_id' => $contract->id,
            'action' => 'Test Action',
            'user' => 'Test User',
        ]);
    }

    public function test_contract_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $contract = Contract::factory()->create(['store_id' => $store->id]);

        $this->assertTrue($contract->store->is($store));
    }

    public function test_contract_has_many_audit_trails(): void
    {
        $contract = Contract::factory()->create();
        $contract->addAuditEntry('Action 1', 'User 1');
        $contract->addAuditEntry('Action 2', 'User 2');

        $this->assertCount(2, $contract->fresh()->auditTrails);
    }

    public function test_contract_soft_deletes(): void
    {
        $contract = Contract::factory()->create();
        $contract->delete();

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
        $this->assertNotNull(Contract::withTrashed()->find($contract->id));
    }
}
