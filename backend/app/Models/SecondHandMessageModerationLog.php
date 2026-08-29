<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecondHandMessageModerationLog extends Model
{
    public $timestamps = false;

    protected $table = 'second_hand_message_moderation_logs';

    protected $fillable = [
        'conversation_id',
        'listing_id',
        'sender_id',
        'receiver_id',
        'body',
        'reason',
        'matched',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

