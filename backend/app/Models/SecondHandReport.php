<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondHandReport extends Model
{
    use HasFactory;

    public const SUBJECT_LISTING = 'listing';
    public const SUBJECT_MESSAGE = 'message';
    public const SUBJECT_USER = 'user';

    public const REASON_SPAM = 'spam';
    public const REASON_SCAM = 'scam';
    public const REASON_HARASSMENT = 'harassment';
    public const REASON_ILLEGAL = 'illegal';
    public const REASON_OTHER = 'other';

    public const STATUS_OPEN = 'open';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    protected $table = 'second_hand_reports';

    protected $fillable = [
        'reporter_user_id',
        'subject_type',
        'subject_id',
        'listing_id',
        'conversation_id',
        'message_id',
        'reason',
        'details',
        'status',
        'handled_by',
        'handled_at',
        'admin_note',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function listing()
    {
        return $this->belongsTo(SecondHandListing::class, 'listing_id');
    }

    public function conversation()
    {
        return $this->belongsTo(SecondHandConversation::class, 'conversation_id');
    }

    public function message()
    {
        return $this->belongsTo(SecondHandMessage::class, 'message_id');
    }

    public function handler()
    {
        return $this->belongsTo(Admin::class, 'handled_by');
    }
}
