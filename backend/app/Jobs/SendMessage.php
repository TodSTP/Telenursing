<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\User; 
use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pusher\Pusher;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversation;
    protected $channelName;

    public function __construct(Conversation $conversation, $channelName)
    {
        $this->conversation = $conversation;
        $this->channelName = $channelName;
    }

    public function handle()
    {
        // Check if $this->conversation->admin is not null before proceeding
        if (!$this->conversation->admin) {
            // Log an error or handle the situation accordingly
            return;
        }

        $options = [
            'cluster' => 'ap1',
            'useTLS' => true
        ];

        $pusher = new Pusher(
            'c38b6cfa9a4f7e26bf76',
            '9c01e9989d46534a826a',
            '1766073',
            $options
        );

        $pusher->trigger($this->channelName, 'message', [
            'message' => $this->conversation->message,
            'admin_name' => $this->conversation->admin->name,
            'user_name' => $this->conversation->user->fname,
            'admin_id' => $this->conversation->admin_id,
            'user_id' => $this->conversation->user_id,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
