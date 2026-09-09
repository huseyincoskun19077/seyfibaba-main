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
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'last_tested_at' => 'datetime',
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
