<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class ContractTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_admin_can_list_contracts(): void
    {
        $admin = $this->createAdmin();
        Contract::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/contracts');

        $response->assertOk()
            ->assertJsonStructure(['data', 'kpis']);
    }

    public function test_list_contracts_returns_kpis(): void
    {
        $admin = $this->createAdmin();
        Contract::factory()->count(2)->create(['status' => 'ACTIVE']);
        Contract::factory()->create(['status' => 'PENDING']);
        Contract::factory()->create(['status' => 'EXPIRED']);

        $response = $this->actingAs($admin)
            ->getJson('/api/contracts');

        $response->assertOk()
            ->assertJsonPath('kpis.total', 4)
            ->assertJsonPath('kpis.active', 2)
            ->assertJsonPath('kpis.pending', 1)
            ->assertJsonPath('kpis.expired', 1);
    }

    public function test_filter_contracts_by_status(): void
    {
        $admin = $this->createAdmin();
        Contract::factory()->active()->count(2)->create();
        Contract::factory()->create(); // pending

        $response = $this->actingAs($admin)
            ->getJson('/api/contracts?status=ACTIVE');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_contracts_by_modality(): void
    {
        $admin = $this->createAdmin();
        Contract::factory()->create(['modality' => 'VIRTUAL']);
        Contract::factory()->create(['modality' => 'PHYSICAL']);

        $response = $this->actingAs($admin)
            ->getJson('/api/contracts?modality=PHYSICAL');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_contracts_by_search(): void
    {
        $admin = $this->createAdmin();
        Contract::factory()->create(['company' => 'BioAgro SAC']);
        Contract::factory()->create(['company' => 'Otra Empresa']);

        $response = $this->actingAs($admin)
            ->getJson('/api/contracts?search=BioAgro');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_contracts_by_store_id(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->create();
        Contract::factory()->create(['store_id' => $store->id]);
        Contract::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/contracts?store_id={$store->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_seller_cannot_list_contracts(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)
            ->getJson('/api/contracts');

        $response->assertForbidden();
    }

    // ─── SHOW ───────────────────────────────────────────────────────

    public function test_admin_can_view_contract(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonPath('company', $contract->company)
            ->assertJsonStructure(['id', 'dbId', 'company', 'ruc', 'type', 'modality', 'status', 'expiryUrgency']);
    }

    public function test_show_contract_includes_audit_trail(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create();
        $contract->addAuditEntry('Contrato Creado', 'Admin');

        $response = $this->actingAs($admin)
            ->getJson("/api/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'auditTrail');
    }

    // ─── STORE ──────────────────────────────────────────────────────

    public function test_admin_can_create_contract(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/contracts', [
                'company' => 'BioAgro SAC',
                'ruc' => '20567891234',
                'rep' => 'Carlos Mendoza',
                'type' => 'Servicio de Distribución',
                'modality' => 'VIRTUAL',
                'start' => '2026-03-20',
                'end' => '2027-03-20',
                'notes' => 'Contrato de prueba',
            ]);

        $response->assertCreated()
            ->assertJsonPath('company', 'BioAgro SAC')
            ->assertJsonPath('status', 'PENDING');

        // Verify auto-generated contract number
        $this->assertStringStartsWith('CTR-2026-', $response->json('id'));
    }

    public function test_create_contract_generates_sequential_number(): void
    {
        $admin = $this->createAdmin();

        // Create first contract
        $this->actingAs($admin)->postJson('/api/contracts', [
            'company' => 'First', 'type' => 'Test', 'modality' => 'VIRTUAL', 'start' => '2026-03-20',
        ]);

        // Create second contract
        $response = $this->actingAs($admin)->postJson('/api/contracts', [
            'company' => 'Second', 'type' => 'Test', 'modality' => 'VIRTUAL', 'start' => '2026-03-20',
        ]);

        $response->assertCreated();
        $this->assertMatchesRegularExpression('/CTR-2026-002/', $response->json('id'));
    }

    public function test_create_contract_adds_audit_entry(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/contracts', [
                'company' => 'Audit Test', 'type' => 'Test', 'modality' => 'VIRTUAL', 'start' => '2026-03-20',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('contract_audit_trails', [
            'action' => 'Contrato Borrador Creado',
        ]);
    }

    public function test_create_contract_with_store(): void
    {
        $admin = $this->createAdmin();
        $store = Store::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/contracts', [
                'company' => 'With Store',
                'type' => 'Test',
                'modality' => 'VIRTUAL',
                'start' => '2026-03-20',
                'storeId' => $store->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('storeId', $store->id);
    }

    public function test_create_contract_requires_company_type_modality_start(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/contracts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['company', 'type', 'modality', 'start']);
    }

    public function test_create_contract_validates_modality(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/contracts', [
                'company' => 'Test', 'type' => 'Test', 'modality' => 'INVALID', 'start' => '2026-03-20',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['modality']);
    }

    public function test_create_contract_validates_end_after_start(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/contracts', [
                'company' => 'Test', 'type' => 'Test', 'modality' => 'VIRTUAL',
                'start' => '2026-06-01',
                'end' => '2026-01-01', // before start
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function test_admin_can_update_contract(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/contracts/{$contract->id}", [
                'company' => 'Updated Company',
                'rep' => 'New Representative',
            ]);

        $response->assertOk()
            ->assertJsonPath('company', 'Updated Company');
    }

    public function test_update_contract_adds_audit_entry(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/contracts/{$contract->id}", [
                'company' => 'Changed',
            ]);

        $this->assertDatabaseHas('contract_audit_trails', [
            'contract_id' => $contract->id,
            'action' => 'Contrato Actualizado',
        ]);
    }

    // ─── UPDATE STATUS ──────────────────────────────────────────────

    public function test_admin_can_activate_contract(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['status' => 'PENDING']);

        $response = $this->actingAs($admin)
            ->putJson("/api/contracts/{$contract->id}/status", [
                'status' => 'ACTIVE',
            ]);

        $response->assertOk();
        $this->assertEquals('ACTIVE', $contract->fresh()->status);
    }

    public function test_admin_can_expire_contract(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->active()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/contracts/{$contract->id}/status", [
                'status' => 'EXPIRED',
            ]);

        $response->assertOk();
        $this->assertEquals('EXPIRED', $contract->fresh()->status);
    }

    public function test_status_change_adds_descriptive_audit_entry(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['status' => 'PENDING']);

        $this->actingAs($admin)
            ->putJson("/api/contracts/{$contract->id}/status", [
                'status' => 'ACTIVE',
            ]);

        $this->assertDatabaseHas('contract_audit_trails', [
            'contract_id' => $contract->id,
            'action' => 'Firma Digital Validada — Contrato Activado',
        ]);
    }

    public function test_update_status_validates_allowed_values(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/contracts/{$contract->id}/status", [
                'status' => 'INVALID',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // ─── UPLOAD ─────────────────────────────────────────────────────

    public function test_admin_can_upload_contract_document(): void
    {
        Storage::fake('private');
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['start_date' => '2026-03-20']);

        $file = UploadedFile::fake()->create('contrato.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($admin)
            ->postJson("/api/contracts/{$contract->id}/upload", [
                'file' => $file,
            ]);

        $response->assertOk();
        $this->assertNotNull($contract->fresh()->file_path);
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('private');
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['start_date' => '2026-03-20']);

        $file = UploadedFile::fake()->create('image.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($admin)
            ->postJson("/api/contracts/{$contract->id}/upload", [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        Storage::fake('private');
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['start_date' => '2026-03-20']);

        $file = UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf'); // > 10MB

        $response = $this->actingAs($admin)
            ->postJson("/api/contracts/{$contract->id}/upload", [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_adds_audit_entry(): void
    {
        Storage::fake('private');
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['start_date' => '2026-03-20']);

        $file = UploadedFile::fake()->create('contrato.pdf', 500, 'application/pdf');

        $this->actingAs($admin)
            ->postJson("/api/contracts/{$contract->id}/upload", [
                'file' => $file,
            ]);

        $this->assertDatabaseHas('contract_audit_trails', [
            'contract_id' => $contract->id,
            'action' => 'Documento Cargado: contrato.pdf',
        ]);
    }

    // ─── DOWNLOAD ───────────────────────────────────────────────────

    public function test_download_returns_404_when_no_file(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create(['file_path' => null]);

        $response = $this->actingAs($admin)
            ->getJson("/api/contracts/{$contract->id}/download");

        $response->assertNotFound();
    }

    // ─── DESTROY ────────────────────────────────────────────────────

    public function test_admin_can_delete_contract(): void
    {
        $admin = $this->createAdmin();
        $contract = Contract::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }
}
