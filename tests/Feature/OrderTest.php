<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the orders dashboard loads successfully and displays order data.
     */
    public function test_orders_dashboard_displays_orders_and_statistics(): void
    {
        Order::create([
            'customer_name' => 'Juan Dela Cruz',
            'status' => Order::STATUS_PENDING,
        ]);

        Order::create([
            'customer_name' => 'Maria Santos',
            'status' => Order::STATUS_PREPARING,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('Maria Santos');
        $response->assertSee('BobaCraft Milk Tea Bar');
    }

    /**
     * Test creating an order sets initial Pending status and dispatches OrderCreated event.
     */
    public function test_can_create_new_order_with_initial_pending_status(): void
    {
        Event::fake([OrderCreated::class]);

        $response = $this->postJson('/orders', [
            'customer_name' => 'Angela Reyes',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'order' => [
                'customer_name' => 'Angela Reyes',
                'status' => Order::STATUS_PENDING,
            ],
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Angela Reyes',
            'status' => Order::STATUS_PENDING,
        ]);

        Event::assertDispatched(OrderCreated::class, function ($event) {
            return $event->order->customer_name === 'Angela Reyes'
                && $event->order->status === Order::STATUS_PENDING;
        });
    }

    /**
     * Test that order creation validates customer name.
     */
    public function test_cannot_create_order_with_invalid_data(): void
    {
        $response = $this->postJson('/orders', [
            'customer_name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name']);
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test updating order status updates the database and dispatches OrderStatusUpdated event.
     */
    public function test_can_update_order_status_and_dispatches_event(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $order = Order::create([
            'customer_name' => 'Mark Villanueva',
            'status' => Order::STATUS_PENDING,
        ]);

        $response = $this->patchJson("/orders/{$order->id}", [
            'status' => Order::STATUS_PREPARING,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'status' => Order::STATUS_PREPARING,
            ],
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PREPARING,
        ]);

        Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
            $channels = $event->broadcastOn();
            $channelNames = array_map(fn($c) => $c->name, $channels);

            return $event->order->id === $order->id
                && $event->order->status === Order::STATUS_PREPARING
                && $event->broadcastAs() === 'order.status.updated'
                && in_array('orders', $channelNames);
        });
    }

    /**
     * Test that invalid or arbitrary status values are rejected with 422.
     */
    public function test_rejects_unsupported_or_arbitrary_status(): void
    {
        $order = Order::create([
            'customer_name' => 'Sofia Mendoza',
            'status' => Order::STATUS_PENDING,
        ]);

        $response = $this->patchJson("/orders/{$order->id}", [
            'status' => 'ArchivedUnknown',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PENDING,
        ]);
    }

    /**
     * Test that submitting the identical status does not dispatch redundant events.
     */
    public function test_does_not_dispatch_duplicate_event_when_status_is_unchanged(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $order = Order::create([
            'customer_name' => 'Sofia Mendoza',
            'status' => Order::STATUS_PREPARING,
        ]);

        $response = $this->patchJson("/orders/{$order->id}", [
            'status' => Order::STATUS_PREPARING,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unchanged' => true,
        ]);

        Event::assertNotDispatched(OrderStatusUpdated::class);
    }
}
