<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class SupplierTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_admin_can_list_suppliers(): void
    {
        $admin = $this->createAdmin();
        Supplier::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/suppliers');

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    }

    public function test_list_suppliers_with_search_filter(): void
    {
        $admin = $this->createAdmin();
        Supplier::factory()->create(['name' => 'Juan Pérez']);
        Supplier::factory()->create(['name' => 'Otro Proveedor']);

        $response = $this->actingAs($admin)
            ->getJson('/api/suppliers?search=Juan');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_list_suppliers_with_status_filter(): void
    {
        $admin = $this->createAdmin();
        Supplier::factory()->count(2)->create(['status' => 'Activo']);
        Supplier::factory()->suspendido()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/suppliers?status=Activo');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_list_suppliers_with_type_filter(): void
    {
        $admin = $this->createAdmin();
        Supplier::factory()->economista()->create();
        Supplier::factory()->contador()->create();
        Supplier::factory()->ingeniero()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/suppliers?type=Contador');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_seller_cannot_list_suppliers(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)
            ->getJson('/api/suppliers');

        $response->assertForbidden();
    }

    // ─── SHOW ───────────────────────────────────────────────────────

    public function test_admin_can_view_supplier(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonPath('nombre', $supplier->name);
    }

    public function test_show_supplier_returns_correct_structure(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::factory()->ingeniero()->create([
            'especialidad' => 'Sistemas',
            'proyectos' => ['Proyecto A', 'Proyecto B'],
            'certificaciones' => ['AWS', 'Azure'],
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'nombre', 'slug', 'ruc', 'tipo', 'especialidad',
                'estado', 'fechaRenovacion', 'proyectos', 'certificaciones',
                'totalRecibos', 'totalGastado', 'createdAt',
            ])
            ->assertJsonPath('tipo', 'Ingeniero')
            ->assertJsonPath('especialidad', 'Sistemas')
            ->assertJsonPath('proyectos', ['Proyecto A', 'Proyecto B'])
            ->assertJsonPath('certificaciones', ['AWS', 'Azure']);
    }

    // ─── STORE ──────────────────────────────────────────────────────

    public function test_admin_can_create_supplier(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/suppliers', [
                'name' => 'Juan Pérez',
                'ruc' => '10456789123',
                'tipo' => 'Economista',
                'especialidad' => 'Análisis Financiero',
                'fechaRenovacion' => '2026-06-15',
            ]);

        $response->assertCreated()
            ->assertJsonPath('nombre', 'Juan Pérez')
            ->assertJsonPath('tipo', 'Economista')
            ->assertJsonPath('estado', 'Activo');

        $this->assertDatabaseHas('suppliers', ['ruc' => '10456789123']);
    }

    public function test_create_supplier_with_dynamic_fields(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/suppliers', [
                'name' => 'Consultora Intech SAC',
                'ruc' => '20556789125',
                'tipo' => 'Ingeniero',
                'especialidad' => 'Desarrollo de Software',
                'proyectos' => ['Sistema ERP', 'App Mobile'],
                'certificaciones' => ['AWS Solutions Architect', 'Scrum Master'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('proyectos', ['Sistema ERP', 'App Mobile'])
            ->assertJsonPath('certificaciones', ['AWS Solutions Architect', 'Scrum Master']);
    }

    public function test_create_supplier_validates_ruc_uniqueness(): void
    {
        $admin = $this->createAdmin();
        Supplier::factory()->create(['ruc' => '20567891234']);

        $response = $this->actingAs($admin)
            ->postJson('/api/suppliers', [
                'name' => 'Duplicate RUC',
                'ruc' => '20567891234',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ruc']);
    }

    public function test_create_supplier_validates_ruc_length(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/suppliers', [
                'name' => 'Bad RUC',
                'ruc' => '123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ruc']);
    }

    public function test_create_supplier_validates_tipo(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/suppliers', [
                'name' => 'Test',
                'tipo' => 'Abogado',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo']);
    }

    public function test_create_supplier_requires_name(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/suppliers', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function test_admin_can_update_supplier(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/suppliers/{$supplier->id}", [
                'name' => 'Nombre Actualizado',
                'especialidad' => 'Nueva Especialidad',
            ]);

        $response->assertOk()
            ->assertJsonPath('nombre', 'Nombre Actualizado')
            ->assertJsonPath('especialidad', 'Nueva Especialidad');
    }

    public function test_admin_can_change_supplier_status(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::factory()->create(['status' => 'Activo']);

        $response = $this->actingAs($admin)
            ->putJson("/api/suppliers/{$supplier->id}", [
                'estado' => 'Suspendido',
            ]);

        $response->assertOk()
            ->assertJsonPath('estado', 'Suspendido');
    }

    public function test_update_validates_estado_values(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/suppliers/{$supplier->id}", [
                'estado' => 'InvalidStatus',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['estado']);
    }

    // ─── DESTROY ────────────────────────────────────────────────────

    public function test_admin_can_delete_supplier(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
}
