<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action_type',
        'model_type',
        'model_id',
        'old_value',
        'new_value',
        'ip_address',
        'description',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public static function log($actionType, $modelType, $modelId = null, $oldValue = null, $newValue = null, $description = null)
    {
        $admin = auth()->guard('admin')->user();
        
        return static::create([
            'admin_id' => $admin?->id,
            'action_type' => $actionType,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'ip_address' => request()->ip(),
            'description' => $description,
        ]);
    }
}