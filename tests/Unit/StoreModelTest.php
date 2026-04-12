<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StoreModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_approved(): void
    {
        $store = Store::factory()->approved()->create();

        $this->assertTrue($store->isApproved());
        $this->assertFalse($store->isBanned());
    }

    public function test_is_banned(): void
    {
        $store = Store::factory()->banned()->create();

        $this->assertTrue($store->isBanned());
        $this->assertFalse($store->isApproved());
    }

    public function test_add_strike_increments(): void
    {
        $store = Store::factory()->approved()->create(['strikes' => 0]);

        $store->addStrike();

        $this->assertEquals(1, $store->fresh()->strikes);
    }

    public function test_three_strikes_bans_store(): void
    {
        $store = Store::factory()->approved()->create(['strikes' => 2]);

        $store->addStrike();

        $store->refresh();
        $this->assertEquals('banned', $store->status);
        $this->assertNotNull($store->banned_at);
    }

    public function test_store_belongs_to_owner(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($store->owner->is($user));
    }

    public function test_profile_is_complete_when_required_fields_are_present(): void
    {
        $store = Store::factory()->withCompleteProfile()->create();

        $this->assertTrue($store->isProfileComplete());
        $this->assertSame([], $store->missingProfileFields());
    }

    public function test_missing_profile_fields_returns_missing_labels(): void
    {
        $store = Store::factory()->create([
            'razon_social' => null,
            'rep_legal_nombre' => 'Maria Perez',
            'rep_legal_dni' => null,
            'direccion_fiscal' => null,
        ]);

        $this->assertFalse($store->isProfileComplete());
        $this->assertSame([
            'Razon social',
            'DNI del representante legal',
            'Direccion fiscal',
        ], $store->missingProfileFields());
    }

    public function test_store_soft_deletes(): void
    {
        $store = Store::factory()->create();
        $store->delete();

        $this->assertSoftDeleted('stores', ['id' => $store->id]);
    }
}
