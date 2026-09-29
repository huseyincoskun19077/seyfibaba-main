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

    /**
     * @return array{method?:string,our_label?:string,other_label?:string,single_label?:string,turns?:list<array{role:string,text:string}>}|null
     */
    public function dialogue(): ?array
    {
        if ($this->transcript_status !== 'done' || ! $this->transcript_text) {
            return null;
        }

        $data = json_decode($this->transcript_text, true);

        return is_array($data) && isset($data['turns']) ? $data : null;
    }

    public function publicAudioUrl(): ?string
    {
        if (! $this->hasLocalAudio()) {
            return null;
        }

        return asset($this->local_path);
    }
}
