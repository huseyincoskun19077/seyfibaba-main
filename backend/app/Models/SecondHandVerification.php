<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondHandVerification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'second_hand_verifications';

    protected $fillable = [
        'user_id',
        'business_name',
        'tax_number',
        'barber_registry_number',
        'tax_document_path',
        'tax_document_original_name',
        'tax_document_size',
        'barber_document_path',
        'barber_document_original_name',
        'barber_document_size',
        'status',
        'submitted_at',
        'terms_accepted_at',
        'privacy_accepted_at',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'privacy_accepted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'tax_document_size' => 'integer',
        'barber_document_size' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}

