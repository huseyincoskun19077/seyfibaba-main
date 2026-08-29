<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PusherCredentail extends Model
{
    use HasFactory;

    protected $hidden = [
        'app_secret',
        'app_id',
    ];

    /**
     * Tarayıcıya gönderilebilecek yalnızca public Pusher alanları.
     */
    public function toPublicArray(): array
    {
        return [
            'app_key' => $this->app_key,
            'app_cluster' => $this->app_cluster,
        ];
    }
}
