<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SecondHandMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $data;

    public function __construct(array $data, User $user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->data,
            'user' => $this->user->id,
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('second-hand-message.' . $this->user->id);
    }
}

