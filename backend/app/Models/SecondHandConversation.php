<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondHandConversation extends Model
{
    use HasFactory;

    protected $table = 'second_hand_conversations';

    protected $fillable = [
        'listing_id',
        'seller_id',
        'buyer_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(SecondHandListing::class, 'listing_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function messages()
    {
        return $this->hasMany(SecondHandMessage::class, 'conversation_id')->orderBy('id');
    }

    public function lastMessage()
    {
        // latestOfMany eager-load join'inde `conversation_id` iki tabloda da geçtiği için,
        // seçilen kolonları tablo bazında açıkça belirtmek gerekir (MySQL "ambiguous" hatası).
        return $this->hasOne(SecondHandMessage::class, 'conversation_id')
            ->selectRaw(
                'second_hand_messages.id,
                 second_hand_messages.conversation_id,
                 second_hand_messages.sender_id,
                 second_hand_messages.body,
                 second_hand_messages.read_at,
                 second_hand_messages.created_at,
                 second_hand_messages.updated_at'
            )
            ->latestOfMany();
    }
}

