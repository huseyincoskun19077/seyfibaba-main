<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSofttrSetting extends Model
{
    protected $table = 'vendor_softtr_settings';

    protected $fillable = [
        'vendor_id',
        'api_base_url',
        'api_user',
        'api_password',
        'is_enabled',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'last_sync_at',
        'last_sync_status',
        'last_sync_message',
        'last_sync_stats',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'api_user' => 'encrypted',
        'api_password' => 'encrypted',
        'last_tested_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_sync_stats' => 'array',
    ];

    protected $hidden = [
        'api_user',
        'api_password',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function hasCredentials(): bool
    {
        return trim((string) $this->api_base_url) !== ''
            && trim((string) $this->api_user) !== ''
            && trim((string) $this->api_password) !== '';
    }
}
