<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\Event;
use App\Models\Conversation;
use App\Models\User; 
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Pusher\Pusher;
use App\Events\AdminMessageSent;
use App\Events\UserMessageSent;
use App\Http\Requests\SendMessageRequest;
use App\Models\ChatRoom;

class ConversationAdminController extends Controller
{
    public function sendMessageToUser(Request $request, $user_id)
    {
        $message = $request->input('message');
        $admin_id = $request->input('admin_id');
    
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        $admin = Admin::find($admin_id);
        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }
    
        $chatRoom = ChatRoom::firstOrCreate(['user_id' => $user_id, 'admin_id' => $admin_id]);

        // Create Conversation
        $conversation = Conversation::create([
            'message' => $message,
            'user_id' => $user_id,
            'admin_id' => $admin->id,
            'chat_room_id' => $chatRoom->id,
            'is_reply' => true, // เพิ่มฟิลด์ is_reply เพื่อระบุว่าข้อความเป็นการตอบกลับ
            'reply_type' => 'admin', // ระบุว่าการตอบกลับเป็นของ admin
        ]);
        // Update conversation status to indicate it's answered
        $conversation->is_answered = true;
        $conversation->save();

        if (!$conversation) {
            return response()->json(['error' => 'Failed to create conversation'], 500);
        }

    
        $channelName = 'Touserid' . $user_id;
    
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
    
        if ($request->input('sender_type') === 'admin') {
            // Event for admin message sent
            event(new AdminMessageSent($conversation, $channelName));
        } else {
            // Event for user message sent
            event(new UserMessageSent($conversation, $channelName));
        }
    
        // Trigger Pusher event
        $pusher->trigger($channelName, 'message', [
            'message' => $message,
            'admin_id' => $admin_id,
            'user_id' => $user_id,
            'admin_name' => $admin->name,
            'user_name' => $user->fname,
            'timestamp' => now()->toDateTimeString(),
        ]);
    
        // Return JSON response with message, user_id, and admin_id
        return response()->json([
            'message' => $message,
            'user_id' => $user_id,
            'admin_id' => $admin_id,
        ], 200);
    }

}
