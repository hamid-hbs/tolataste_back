<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    public bool $afterCommit = true;

    public function __construct(Order $order)
    {
        $this->order = $order->load(['items', 'table']);
    }

    public function broadcastOn(): array
    {
        $orderId = $this->order->getKey();

        return [
            new Channel("orders.{$orderId}"),
            new Channel('kitchen'),
            new Channel('serveur'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }
}
