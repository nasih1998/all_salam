<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var array */
    public $cart;

    /** @var bool */
    public $completed;

    /**
     * Create a new event instance.
     *
     * @param array $cart
     * @param bool $completed
     */
    public function __construct(array $cart, bool $completed = false)
    {
        $this->cart = $cart;
        $this->completed = $completed;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('pos-cart');
    }

    public function broadcastAs()
    {
        return 'CartUpdated';
    }
}


