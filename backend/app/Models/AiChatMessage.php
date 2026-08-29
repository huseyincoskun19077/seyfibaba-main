<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content', 'tokens_used', 'provider',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
