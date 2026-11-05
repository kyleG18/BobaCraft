<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Order
     */
    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'customer_name' => $this->order->customer_name,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at ? $this->order->created_at->toIso8601String() : now()->toIso8601String(),
            'created_at_formatted' => $this->order->created_at ? $this->order->created_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A'),
            'updated_at' => $this->order->updated_at ? $this->order->updated_at->toIso8601String() : now()->toIso8601String(),
            'updated_at_formatted' => $this->order->updated_at ? $this->order->updated_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A'),
            'stats' => [
                'total' => Order::count(),
                'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
                'preparing' => Order::where('status', Order::STATUS_PREPARING)->count(),
                'ready' => Order::where('status', Order::STATUS_READY)->count(),
                'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            ],
        ];
    }
}
