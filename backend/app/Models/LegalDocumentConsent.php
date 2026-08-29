<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocumentConsent extends Model
{
    protected $fillable = [
        'user_id',
        'guest_identifier',
        'legal_document_id',
        'document_slug',
        'document_title',
        'document_version',
        'ip_address',
        'user_agent',
        'platform',
        'consent_status',
        'context',
        'order_id',
        'consented_at',
    ];

    protected $casts = [
        'consent_status' => 'boolean',
        'consented_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }
}
