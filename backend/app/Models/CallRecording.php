<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallRecording extends Model
{
    protected $fillable = [
        'uniqueid',
        'common_id',
        'source',
        'destination',
        'direction',
        'duration_sec',
        'called_at',
        'line',
        'directory',
        'remote_recording_url',
        'local_path',
        'file_size',
        'sync_status',
        'sync_error',
        'transcript_text',
        'transcript_status',
        'raw_payload',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'raw_payload' => 'array',
        'duration_sec' => 'integer',
        'direction' => 'integer',
        'file_size' => 'integer',
    ];

    public function hasLocalAudio(): bool
    {
        return $this->local_path
            && is_file(public_path($this->local_path));
    }

    public function publicAudioUrl(): ?string
    {
        if (! $this->hasLocalAudio()) {
            return null;
        }

        return asset($this->local_path);
    }
}
