<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
    'message', 
    'user_id', 
    'admin_id', 
    'is_reply',
    'reply_type',
    'chat_room_id'
];


    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }
    
}
