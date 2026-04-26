<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransitionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'gif_path',
        'gif_original_name',
        'audio_path',
        'audio_original_name',
        'duration_seconds',
        'is_active',
    ];

    protected $appends = [
        'gif_url',
        'audio_url',
    ];

    public function getGifUrlAttribute(): ?string
    {
        return $this->gif_path ? route('transition-templates.media.gif', $this, false) : null;
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? route('transition-templates.media.audio', $this, false) : null;
    }
}
