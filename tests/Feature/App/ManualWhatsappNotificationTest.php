<?php

namespace Tests\Feature\App;

use App\Models\CustomerNotification;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualWhatsappNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_prepare_manual_whatsapp_message_for_wash_order(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $washOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $washOrder->customer->update([
            'name' => 'Joao Silva',
            'phone' => '(11) 98888-7777',
        ]);

        $this->actingAs($user)
            ->post(route('wash-orders.notifications.whatsapp-manual.store', $washOrder), [
                'template_key' => CustomerNotification::TEMPLATE_STATUS_UPDATE,
                'notes' => 'Estamos caprichando na secagem.',
            ])
            ->assertRedirect(route('wash-orders.show', $washOrder));

        $this->assertDatabaseHas('customer_notifications', [
            'wash_order_id' => $washOrder->id,
            'customer_id' => $washOrder->customer_id,
            'user_id' => $user->id,
            'channel' => CustomerNotification::CHANNEL_WHATSAPP_MANUAL,
            'template_key' => CustomerNotification::TEMPLATE_STATUS_UPDATE,
            'target' => '5511988887777',
            'status' => CustomerNotification::STATUS_PREPARED,
        ]);

        $notification = CustomerNotification::first();

        $this->assertStringContainsString('Joao Silva', $notification->message);
        $this->assertStringContainsString('status: lavando', $notification->message);
        $this->assertStringContainsString($washOrder->trackingUrl(), $notification->message);
        $this->assertStringStartsWith('https://wa.me/5511988887777?text=', $notification->action_url);
    }

    public function test_operator_cannot_mark_manual_notification_as_sent(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create();
        $notification = CustomerNotification::create([
            'wash_order_id' => $washOrder->id,
            'customer_id' => $washOrder->customer_id,
            'user_id' => $user->id,
            'channel' => CustomerNotification::CHANNEL_WHATSAPP_MANUAL,
            'template_key' => CustomerNotification::TEMPLATE_TRACKING_LINK,
            'target' => '5511999999999',
            'message' => 'Mensagem preparada.',
            'action_url' => 'https://wa.me/5511999999999?text=Mensagem',
            'status' => CustomerNotification::STATUS_PREPARED,
            'prepared_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('wash-orders.notifications.mark-as-sent', [$washOrder, $notification]))
            ->assertForbidden();

        $this->assertDatabaseHas('customer_notifications', [
            'id' => $notification->id,
            'status' => CustomerNotification::STATUS_PREPARED,
        ]);

        $this->assertNull($notification->refresh()->manually_sent_at);
    }

    public function test_attendant_can_prepare_wash_started_template(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $washOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $washOrder->customer->update([
            'name' => 'Maria Cliente',
            'phone' => '(11) 97777-6666',
        ]);

        $this->actingAs($user)
            ->post(route('wash-orders.notifications.whatsapp-manual.store', $washOrder), [
                'template_key' => CustomerNotification::TEMPLATE_WASH_STARTED,
            ])
            ->assertRedirect(route('wash-orders.show', $washOrder));

        $notification = CustomerNotification::first();

        $this->assertSame(CustomerNotification::TEMPLATE_WASH_STARTED, $notification->template_key);
        $this->assertStringContainsString('iniciamos a lavagem', $notification->message);
        $this->assertStringContainsString($washOrder->trackingUrl(), $notification->message);
    }

    public function test_attendant_can_prepare_promotion_template_with_notes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $washOrder = WashOrder::factory()->create();
        $washOrder->customer->update([
            'name' => 'Carlos Cliente',
            'phone' => '(11) 96666-5555',
        ]);

        $this->actingAs($user)
            ->post(route('wash-orders.notifications.whatsapp-manual.store', $washOrder), [
                'template_key' => CustomerNotification::TEMPLATE_PROMOTION,
                'notes' => '10% de desconto na proxima lavagem completa.',
            ])
            ->assertRedirect(route('wash-orders.show', $washOrder));

        $notification = CustomerNotification::first();

        $this->assertSame(CustomerNotification::TEMPLATE_PROMOTION, $notification->template_key);
        $this->assertStringContainsString('condicao especial', $notification->message);
        $this->assertStringContainsString('10% de desconto na proxima lavagem completa.', $notification->message);
    }

    public function test_wash_order_detail_shows_manual_notification_area(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create();

        $this->actingAs($user)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Notificacao manual')
            ->assertSee('Prepare a mensagem e envie manualmente pelo WhatsApp')
            ->assertSee('Lavagem iniciada')
            ->assertSee('Lavagem concluida')
            ->assertSee('Promocao')
            ->assertSee('Preparar mensagem');
    }
}
