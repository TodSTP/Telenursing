<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Models\Conversation;

class AdminMessageSent
{
    use Dispatchable, SerializesModels;

    public $conversation;
    public $channelName;

    public function __construct(Conversation $conversation, $channelName)
    {
        $this->conversation = $conversation;
        $this->channelName = $channelName;
    }

    public function broadcastOn()
    {
        return new Channel($this->channelName);
    }
}
