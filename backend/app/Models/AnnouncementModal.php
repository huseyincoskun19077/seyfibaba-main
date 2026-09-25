<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementModal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'status',
        'expired_date',
        'link',
        'mobile_link',
        'cta_text',
        'show_on_web',
        'show_on_mobile',
    ];

    protected $casts = [
        'status' => 'integer',
        'expired_date' => 'integer',
        'show_on_web' => 'integer',
        'show_on_mobile' => 'integer',
    ];
}
