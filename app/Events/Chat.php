<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Chat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender_id;
    public $receiver_id;
    public $username;
    public $message;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($sender_id, $receiver_id, $username, $message)
    {
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->username = $username;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->sender_id . '.' . $this->receiver_id);
        // return new Channel('chatApplication');
        // return new PrivateChannel('chat.' . $this->sender_id . '.' . $this->receiver_id);

    }
}
