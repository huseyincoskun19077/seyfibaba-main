<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatKnowledge extends Model
{
    protected $table = 'ai_chat_knowledge';

    protected $fillable = [
        'category', 'question', 'answer', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
