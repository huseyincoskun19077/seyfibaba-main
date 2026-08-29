<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'file_path',
        'original_name',
        'total_rows',
        'processed_rows',
        'success_count',
        'error_count',
        'status',
        'error_log',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'error_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
