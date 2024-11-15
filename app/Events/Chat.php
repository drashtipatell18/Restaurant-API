<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Log;

class Chat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender_id;
    public $receiver_id;
    public $username;
    public $message;
    public $group_id;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($sender_id, $receiver_id, $username, $message, $group_id)
    {
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->username = $username;
        $this->message = $message;
        $this->group_id = $group_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('chat.' . $this->sender_id . '.' . $this->receiver_id );
        // return new Channel('chatApplication');

        if ($this->group_id) {
            Log::info('Broadcasting data:', [
                'sender_id' => $this->sender_id,
                'name' => $this->username,
                'message' => $this->message,
            ]);
            // return new PresenceChannel('group.' . $this->group_id);
            return new Channel('group.' . $this->group_id);
        } else {
            return new PrivateChannel('chat.' . $this->sender_id . '.' . $this->receiver_id);
            // return new Channel('chat.'  . $this->receiver_id);
            // return new Channel('chat.' . $this->sender_id);
        }
    }
}
