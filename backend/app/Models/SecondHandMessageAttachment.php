<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SecondHandMessageAttachment extends Model
{
    use HasFactory;

    protected $table = 'second_hand_message_attachments';

    protected $appends = ['url'];

    protected $fillable = [
        'message_id',
        'kind',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function getUrlAttribute(): string
    {
        return (string) Storage::disk('public')->url((string) $this->path);
    }

    public function message()
    {
        return $this->belongsTo(SecondHandMessage::class, 'message_id');
    }
}

