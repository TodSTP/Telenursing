<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\Request;
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
use App\Jobs\SendMessage;
use App\Models\ChatRoom;

class ConversationController extends Controller
{
    use AuthorizesRequests, ValidatesRequests,DispatchesJobs;
    
 public function sendMessageAll(Request $request)
{
    $message = $request->input('message');
    $user_id = $request->input('user_id');

    $user = User::find($user_id);
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $admins = Admin::all();

    foreach ($admins as $admin) {
        // Check if chat room already exists
        $chatRoom = ChatRoom::firstOrCreate([
            'user_id' => $user_id,
            'admin_id' => $admin->id
        ]);

     
        // Create Conversation
        $conversation = Conversation::create([
            'message' => $message,
            'user_id' => $user_id,
            'admin_id' => $admin->id,
            'chat_room_id' => $chatRoom->id,
            'is_reply' => true, // เพิ่มฟิลด์ is_reply เพื่อระบุว่าข้อความเป็นการตอบกลับ
            'reply_type' => 'alladmin', // ระบุว่าการตอบกลับเป็นของ 

        ]);
        

        if (!$conversation) {
            return response()->json(['error' => 'Failed to create conversation'], 500);
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

        $channelName = "messageUser";
        $pusher->trigger($channelName, 'message', [
            'message' => $message,
            'admin_name' => $admin->name,
            'user_name' => $user->fname,
            'admin_id' => $admin->id,
            'user_id' => $user_id,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    $adminIds = $admins->pluck('id');

    return response()->json([
        'message' => $message,
        'user_id' => $user_id,
        'admin_ids' => $adminIds->toArray(),
    ], 200);
}  



/* --------------------------------------------------------------------------------------------------------- */
/*   public function getAdminMessages($admin_id)
{
    // Get all chat rooms (not just the ones specific to the admin_id)
    $chatRooms = ChatRoom::all();

    $messages = [];

    foreach ($chatRooms as $chatRoom) {
        $conversations = $chatRoom->conversations()->with(['user', 'admin'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($conversations as $conversation) {
            // Add each message to the messages array without checking for duplicates
            $messages[] = [
                'message' => $conversation->message,
                'admin_id' => $conversation->admin_id,
                'user_id' => $conversation->user_id,
                'admin_name' => $conversation->admin->name,
                'user_name' => $conversation->user->fname,
                'reply_type' => $conversation->reply_type,
                'is_answered' => $conversation->is_answered,
                'timestamp' => $conversation->created_at->toDateTimeString(),
            ];
        }
    }

    return response()->json($messages, 200);
}
 */

public function getAdminMessages($admin_id)
{
    // Get all chat rooms (not just the ones specific to the admin_id)
    $chatRooms = ChatRoom::all();

    $messages = [];
    $uniqueAllAdminMessages = [];

    foreach ($chatRooms as $chatRoom) {
        $conversations = $chatRoom->conversations()->with(['user', 'admin'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($conversations as $conversation) {
            if ($conversation->reply_type === 'alladmin') {
                // Check if the message is already included in uniqueAllAdminMessages
                $messageKey = $conversation->message . '_' . $conversation->user_id;
                if (!isset($uniqueAllAdminMessages[$messageKey])) {
                    $uniqueAllAdminMessages[$messageKey] = true;
                    
                    $messages[] = [
                        'message' => $conversation->message,
                        'admin_id' => $conversation->admin_id,
                        'user_id' => $conversation->user_id,
                        'admin_name' => $conversation->admin->name,
                        'user_name' => $conversation->user->fname,
                        'reply_type' => $conversation->reply_type,
                        'is_answered' => $conversation->is_answered,
                        'timestamp' => $conversation->created_at->toDateTimeString(),
                    ];
                }
            } else {
                // For other message types, just add to messages array
                $messages[] = [
                    'message' => $conversation->message,
                    'admin_id' => $conversation->admin_id,
                    'user_id' => $conversation->user_id,
                    'admin_name' => $conversation->admin->name,
                    'user_name' => $conversation->user->fname,
                    'reply_type' => $conversation->reply_type,
                    'is_answered' => $conversation->is_answered,
                    'timestamp' => $conversation->created_at->toDateTimeString(),
                ];
            }
        }
    }

    return response()->json($messages, 200);
}



/* --------------------------------------------------------------------------------------------------------- */
/* get ค่า ฝั่ง User  */
public function getUserMessages($user_id)
{
    $chatRooms = ChatRoom::where('user_id', $user_id)->get();

    $messages = [];

    foreach ($chatRooms as $chatRoom) {
        $conversations = $chatRoom->conversations()->with('admin')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($conversations as $conversation) {
            // Check if the message already exists in the array
            $existingMessage = collect($messages)->where('message', $conversation->message)->first();

            // If the message doesn't exist, add it to the messages array
            if (!$existingMessage) {
                $replyType = $conversation->user_id == $user_id ? 'user' : 'admin'; // Check if it's a reply from the user or admin
                $messages[] = [
                    'message' => $conversation->message,
                    'admin_id' => $conversation->admin_id,
                    'user_id' => $conversation->user_id,
                    'admin_name' => $conversation->admin->name,
                    'user_name' => $conversation->user->fname,
                    'reply_type' => $conversation->reply_type,
                    'timestamp' => $conversation->created_at->toDateTimeString(),
                ];
            }
        }
    }

    return response()->json($messages, 200);
} 

 /**
     * ลบห้องแชทและข้อความทั้งหมดที่เกี่ยวข้องโดยใช้ user_id.
     *
     * @param int $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteChatRoomsByUserId($user_id)
    {
        // ค้นหาห้องแชททั้งหมดโดยใช้ user_id
        $chatRooms = ChatRoom::where('user_id', $user_id)->get();

        // ถ้าหากไม่พบห้องแชท ให้ส่ง response error
        if ($chatRooms->isEmpty()) {
            return response()->json(['error' => 'No chat rooms found for this user'], 404);
        }

        // ลบข้อความทั้งหมดที่เกี่ยวข้องกับห้องแชทแต่ละห้อง และลบห้องแชทนั้นเอง
        foreach ($chatRooms as $chatRoom) {
            // ลบข้อความทั้งหมดที่เกี่ยวข้องกับห้องแชทนั้น
            $chatRoom->conversations()->delete();

            // ลบห้องแชทนั้นเอง
            $chatRoom->delete();
        }

        // ส่ง response ว่าลบห้องแชทและข้อความทั้งหมดเรียบร้อยแล้ว
        return response()->json(['message' => 'ลบห้องที่ user id สร้างขึ้น'], 200);
    }




} 