<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
