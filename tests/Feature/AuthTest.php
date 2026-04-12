<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class AuthTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ─── LOGIN ────────────────────────────────────────────────────────

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@lyrium.com',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('administrator');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@lyrium.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'token', 'user'])
            ->assertJsonPath('success', true);
    }

    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@lyrium.com',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@lyrium.com',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_login_with_unverified_email_returns_403(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@lyrium.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'unverified@lyrium.com',
            'password' => 'secret123',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('requires_verification', true);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not_an_email',
            'password' => 'secret123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ─── REGISTER (SELLER) ───────────────────────────────────────────

    public function test_register_seller_creates_user_and_store(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'storeName' => 'BioTienda Test',
            'email' => 'seller@test.com',
            'phone' => '999888777',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'ruc' => '20123456789',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('requires_verification', true);

        $user = User::where('email', 'seller@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('seller'));
        $this->assertDatabaseHas('stores', ['ruc' => '20123456789', 'status' => 'pending']);
    }

    public function test_register_seller_validates_ruc_length(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'storeName' => 'Test Store',
            'email' => 'seller@test.com',
            'phone' => '999888777',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'ruc' => '123', // invalid
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ruc']);
    }

    public function test_register_seller_prevents_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);

        $response = $this->postJson('/api/auth/register', [
            'storeName' => 'Test Store',
            'email' => 'taken@test.com',
            'phone' => '999888777',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'ruc' => '20123456789',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // ─── REGISTER CUSTOMER ──────────────────────────────────────────

    public function test_register_customer_creates_user_with_customer_role(): void
    {
        $response = $this->postJson('/api/auth/register-customer', [
            'name' => 'Juan Cliente',
            'email' => 'customer@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('requires_verification', true);

        $user = User::where('email', 'customer@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('customer'));
    }

    // ─── LOGOUT ─────────────────────────────────────────────────────

    public function test_logout_deletes_current_token(): void
    {
        $user = $this->createAdmin();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertUnauthorized();
    }

    // ─── VALIDATE TOKEN ─────────────────────────────────────────────

    public function test_validate_token_returns_user_data(): void
    {
        $user = $this->createAdmin();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/auth/validate');

        $response->assertOk()
            ->assertJsonStructure(['id', 'username', 'email', 'role']);
    }

    public function test_validate_token_rejects_unauthenticated(): void
    {
        $response = $this->getJson('/api/auth/validate');

        $response->assertUnauthorized();
    }

    // ─── REFRESH TOKEN ──────────────────────────────────────────────

    public function test_refresh_token_returns_new_token(): void
    {
        $user = $this->createAdmin();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['token']);
    }

    // ─── VERIFY OTP ─────────────────────────────────────────────────

    public function test_verify_otp_with_already_verified_user(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@test.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'verified@test.com',
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_verify_otp_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'nobody@test.com',
            'code' => '123456',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ─── RESEND OTP ─────────────────────────────────────────────────

    public function test_resend_otp_for_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/resend-otp', [
            'email' => 'nobody@test.com',
        ]);

        $response->assertNotFound();
    }

    public function test_resend_otp_for_already_verified_user(): void
    {
        User::factory()->create([
            'email' => 'verified@test.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/resend-otp', [
            'email' => 'verified@test.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
