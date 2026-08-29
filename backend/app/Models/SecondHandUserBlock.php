<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SecondHandUserBlock extends Model
{
    protected $table = 'second_hand_user_blocks';

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
    ];

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blocked()
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}

