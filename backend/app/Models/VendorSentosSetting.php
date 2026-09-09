<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSentosSetting extends Model
{
    protected $table = 'vendor_sentos_settings';

    protected $fillable = [
        'vendor_id',
        'api_base_url',
        'api_key',
        'api_secret',
        'is_enabled',
        'channel_id',
        'warehouse_id',
        'push_orders',
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
        'push_orders' => 'boolean',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'last_tested_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_sync_stats' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function hasCredentials(): bool
    {
        return trim((string) $this->api_base_url) !== ''
            && trim((string) $this->api_key) !== ''
            && trim((string) $this->api_secret) !== '';
    }
}
