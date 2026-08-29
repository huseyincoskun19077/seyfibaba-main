<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonCrmService extends Model
{
    protected $table = 'salon_crm_services';

    protected $fillable = [
        'salon_id',
        'name',
        'duration_minutes',
        'price',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }

    public function staffServices(): HasMany
    {
        return $this->hasMany(SalonCrmStaffService::class, 'service_id');
    }
}
