<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class CardClick implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $card_id;
    public $selected;

    /**
     * Create a new event instance.
     */
    public function __construct($card_id, $selected)
    {
        $this->card_id = $card_id;
        $this->selected = $selected;

        Cache::put('box-' . $card_id, ['card_id' => $card_id, 'selected' => $selected], 36000);
    }

    public function broadcastOn()
    {
        return new Channel('box-channel');
    }

    public function broadcastAs()
    {
        return 'CardClick';
    }
}
