<?php
// app/Http/Requests/SendMessageRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Change to false if you have authorization logic
    }

    public function rules()
    {
        return [
            'message' => 'required|string',
            'admin_id' => 'required|integer|exists:admins,id',
            'reply_type' => 'required|string', // Add validation rule for reply_type
        ];
    }
}
