<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class NotificationMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

   
    public $notificationType;
    public $notification;
    /**
     * Create a new event instance.
     */
    public function __construct($notificationType, $notification)
    {
       
        $this->notificationType = $notificationType;
        $this->notification = $notification;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('notifications');
    }
}
