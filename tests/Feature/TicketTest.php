<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TicketInboxUpdated;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketRepliedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\WithRoles;

final class TicketTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function createTicketForSeller(User $seller, array $attributes = []): Ticket
    {
        $store = $seller->ownedStores()->first();

        return Ticket::factory()->create(array_merge([
            'user_id' => $seller->id,
            'store_id' => $store->id,
        ], $attributes));
    }

    public function test_seller_can_list_own_tickets(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller);
        $this->createTicketForSeller($seller);
        $admin = $this->createAdmin();

        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'is_read' => false,
        ]);

        $otherSeller = $this->createSeller();
        $this->createTicketForSeller($otherSeller);

        $response = $this->actingAs($seller)->getJson('/api/tickets');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.mensajes_sin_leer', 1);
    }

    public function test_seller_can_create_ticket(): void
    {
        Notification::fake();
        $seller = $this->createSeller();
        $admin = $this->createAdmin();

        $response = $this->actingAs($seller)->postJson('/api/tickets', [
            'asunto' => 'Error en carga de RUC',
            'mensaje' => 'No puedo actualizar mi RUC, sale error de red al guardar los datos.',
            'tipo_ticket' => 'tech',
            'criticidad' => 'alta',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'tech')
            ->assertJsonPath('data.critical', true)
            ->assertJsonPath('data.status', 'abierto');

        $this->assertDatabaseHas('tickets', [
            'user_id' => $seller->id,
            'subject' => 'Error en carga de RUC',
            'category' => 'tech',
            'priority' => 'high',
            'is_critical' => true,
        ]);

        $this->assertDatabaseHas('ticket_messages', [
            'user_id' => $seller->id,
            'content' => 'No puedo actualizar mi RUC, sale error de red al guardar los datos.',
        ]);

        Notification::assertSentTo($admin, TicketCreatedNotification::class);
    }

    public function test_create_ticket_validates_fields(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)->postJson('/api/tickets', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['asunto', 'mensaje', 'tipo_ticket', 'criticidad']);
    }

    public function test_customer_without_store_cannot_create_ticket(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer)->postJson('/api/tickets', [
            'asunto' => 'Test ticket',
            'mensaje' => 'Este es un mensaje de prueba suficientemente largo.',
            'tipo_ticket' => 'info',
            'criticidad' => 'baja',
        ]);

        $response->assertForbidden();
    }

    public function test_seller_can_view_own_ticket(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller);

        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $seller->id,
        ]);

        $response = $this->actingAs($seller)->getJson("/api/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('success', true);
    }

    public function test_seller_cannot_view_other_sellers_ticket(): void
    {
        $seller1 = $this->createSeller();
        $seller2 = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller2);

        $response = $this->actingAs($seller1)->getJson("/api/tickets/{$ticket->id}");

        $response->assertNotFound();
    }

    public function test_seller_can_send_message(): void
    {
        Event::fake([TicketInboxUpdated::class]);
        Notification::fake();
        $seller = $this->createSeller();
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();
        $ticket = $this->createTicketForSeller($seller, [
            'assigned_admin_id' => $admin->id,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'Ya adjunté los documentos solicitados.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.texto', 'Ya adjunté los documentos solicitados.');

        Notification::assertSentTo($admin, TicketRepliedNotification::class);
        Event::assertDispatched(TicketInboxUpdated::class, fn (TicketInboxUpdated $event) => $event->notifyUserId === $admin->id
            && $event->unreadCount === 1
            && $event->totalMessages >= 1
            && $event->ticketId === $ticket->id);
        Event::assertDispatched(TicketInboxUpdated::class, fn (TicketInboxUpdated $event) => $event->notifyUserId === $otherAdmin->id
            && $event->ticketId === $ticket->id);
    }

    public function test_seller_message_notifies_all_admins_when_ticket_has_no_assignee(): void
    {
        Event::fake([TicketInboxUpdated::class]);
        $seller = $this->createSeller();
        $adminA = $this->createAdmin();
        $adminB = $this->createAdmin();
        $ticket = $this->createTicketForSeller($seller, [
            'assigned_admin_id' => null,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'Necesito ayuda con este ticket sin asignacion.',
        ]);

        $response->assertCreated();

        Event::assertDispatched(TicketInboxUpdated::class, fn (TicketInboxUpdated $event) => $event->notifyUserId === $adminA->id
            && $event->unreadCount === 1
            && $event->ticketId === $ticket->id);
        Event::assertDispatched(TicketInboxUpdated::class, fn (TicketInboxUpdated $event) => $event->notifyUserId === $adminB->id
            && $event->unreadCount === 1
            && $event->ticketId === $ticket->id);
    }

    public function test_seller_cannot_message_closed_ticket(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, ['status' => 'closed']);

        $response = $this->actingAs($seller)->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'Quiero reabrir este ticket.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_seller_message_reopens_resolved_ticket(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, ['status' => 'resolved']);

        $this->actingAs($seller)->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'El problema persiste, necesito más ayuda.',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'reopened',
        ]);
    }

    public function test_seller_can_close_own_ticket(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, ['status' => 'in_progress']);

        $response = $this->actingAs($seller)->putJson("/api/tickets/{$ticket->id}/close");

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_seller_can_submit_survey(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, [
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($seller)->postJson("/api/tickets/{$ticket->id}/survey", [
            'rating' => 5,
            'comment' => 'Excelente atención, muy rápido.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'satisfaction_rating' => 5,
            'satisfaction_comment' => 'Excelente atención, muy rápido.',
        ]);
    }

    public function test_seller_cannot_submit_survey_twice(): void
    {
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, [
            'status' => 'closed',
            'satisfaction_rating' => 4,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/tickets/{$ticket->id}/survey", [
            'rating' => 5,
            'comment' => 'Otro feedback.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_list_all_tickets(): void
    {
        $admin = $this->createAdmin();
        $seller1 = $this->createSeller();
        $seller2 = $this->createSeller();
        $this->createTicketForSeller($seller1);
        $this->createTicketForSeller($seller2);

        $response = $this->actingAs($admin)->getJson('/api/admin/tickets');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_filter_tickets_by_status(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createTicketForSeller($seller, ['status' => 'open']);
        $this->createTicketForSeller($seller, ['status' => 'closed', 'closed_at' => now()]);

        $response = $this->actingAs($admin)->getJson('/api/admin/tickets?status=open');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_search_tickets(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createTicketForSeller($seller, ['subject' => 'Error en pasarela de pagos']);
        $this->createTicketForSeller($seller, ['subject' => 'Consulta sobre categorías']);

        $response = $this->actingAs($admin)->getJson('/api/admin/tickets?search=pasarela');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_seller_cannot_access_admin_tickets(): void
    {
        $seller = $this->createSeller();

        $response = $this->actingAs($seller)->getJson('/api/admin/tickets');

        $response->assertForbidden();
    }

    public function test_admin_can_reply_to_ticket(): void
    {
        Event::fake([TicketInboxUpdated::class]);
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller);

        $response = $this->actingAs($admin)->postJson("/api/admin/tickets/{$ticket->id}/messages", [
            'content' => 'Ya revisamos tu caso, necesitamos más información.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_admin_id' => $admin->id,
            'status' => 'in_progress',
        ]);

        Notification::assertSentTo($seller, TicketRepliedNotification::class);
        Event::assertDispatched(TicketInboxUpdated::class, fn (TicketInboxUpdated $event) => $event->notifyUserId === $seller->id
            && $event->unreadCount === 1
            && $event->totalMessages >= 1
            && $event->ticketId === $ticket->id);
    }

    public function test_admin_can_change_ticket_status(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, ['status' => 'in_progress']);

        $response = $this->actingAs($admin)->putJson("/api/admin/tickets/{$ticket->id}/status", [
            'status' => 'resolved',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);

        Notification::assertSentTo($seller, TicketStatusChangedNotification::class);
    }

    public function test_admin_can_assign_ticket(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller);

        $response = $this->actingAs($admin)->putJson("/api/admin/tickets/{$ticket->id}/assign", [
            'admin_id' => $otherAdmin->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_admin_id' => $otherAdmin->id,
        ]);
    }

    public function test_admin_can_change_priority(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller, ['priority' => 'low']);

        $response = $this->actingAs($admin)->putJson("/api/admin/tickets/{$ticket->id}/priority", [
            'priority' => 'critical',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'critical',
            'is_critical' => true,
        ]);
    }

    public function test_admin_can_escalate_ticket(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $ticket = $this->createTicketForSeller($seller);

        $response = $this->actingAs($admin)->putJson("/api/admin/tickets/{$ticket->id}/escalate", [
            'escalated_to' => 'Gerencia Técnica',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'is_escalated' => true,
            'escalated_to' => 'Gerencia Técnica',
        ]);
    }

    public function test_unauthenticated_cannot_access_tickets(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
        $this->postJson('/api/tickets')->assertUnauthorized();
    }

    public function test_ticket_number_is_sequential(): void
    {
        $number1 = Ticket::generateTicketNumber();
        $seller = $this->createSeller();
        $store = $seller->ownedStores()->first();

        Ticket::factory()->create([
            'user_id' => $seller->id,
            'store_id' => $store->id,
            'ticket_number' => $number1,
        ]);

        $number2 = Ticket::generateTicketNumber();

        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('TKT-'.now()->format('Y'), $number1);
    }
}
