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
use App\Models\ChatRoom;

class ConversationUserController extends Controller
{
    public function sendMessageToAdmin(Request $request, $admin_id)
    {
        $message = $request->input('message');
        $user_id = $request->input('user_id');

    
        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        $admin = Admin::find($admin_id);
        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }
    
        // Check if chat room exists or create a new one
        $chatRoom = ChatRoom::firstOrCreate(['user_id' => $user_id, 'admin_id' => $admin_id]);
    
        // Create conversation
        $conversation = Conversation::create([
            'message' => $message,
            'user_id' => $user_id,
            'admin_id' => $admin_id,
            'chat_room_id' => $chatRoom->id, // Use the chat room ID from the created or existing chat room
            'is_reply' => true, // เพิ่มฟิลด์ is_reply เพื่อระบุว่าข้อความเป็นการตอบกลับ
            'reply_type' => 'user', // ระบุชนิดของการตอบกลับว่ามาจากผู้ใช้
        ]);
        

        if (!$conversation) {
            return response()->json(['error' => 'Failed to create conversation'], 500);
        }
    
        // Trigger Pusher event
        $channelName = 'Toadminid' . $admin_id;
    
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
            'admin_id' => intval($admin_id),
            'user_id' => intval($user_id),
            'admin_name' => $admin->name,
            'user_name' => $user->fname,
            'timestamp' => now()->toDateTimeString(),
            'chat_room_id' => $chatRoom->id, // Use the chat room ID from the created or existing chat room
            'is_reply' => true, // เพิ่มฟิลด์ is_reply ในข้อมูลที่ส่งไปยัง Pusher
            'reply_type' => 'user', // เพิ่มฟิลด์ reply_type ในข้อมูลที่ส่งไปยัง Pusher
        ]);
    
        // Return JSON response with message, user_id, admin_id, and chat_room_id
        return response()->json([
            'message' => $message,
            'user_id' => $user_id,
            'admin_id' => $admin_id,
            'chat_room_id' => $chatRoom->id, // เพิ่ม chat_room_id ใน JSON response
            'is_reply' => true, // เพิ่มฟิลด์ is_reply ใน JSON response
            'reply_type' => 'user', // เพิ่มฟิลด์ reply_type ใน JSON response
        ], 200);
    }
    

 
}
