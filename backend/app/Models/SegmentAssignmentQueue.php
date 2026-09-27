<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentAssignmentQueue extends Model
{
    protected $table = 'segment_assignment_queue';

    protected $fillable = [
        'product_id',
        'suggested_segment_ids',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'suggested_segment_ids' => 'array',
        'reviewed_at' => 'datetime',
        'product_id' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
