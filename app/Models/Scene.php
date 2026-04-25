<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Scene extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_item_id',
        'name',
        'gif_path',
        'gif_original_name',
        'audio_path',
        'audio_original_name',
        'duration_seconds',
        'position',
    ];

    protected $appends = [
        'gif_url',
        'audio_url',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function getGifUrlAttribute(): ?string
    {
        return $this->gif_path ? route('scenes.media.gif', $this, false) : null;
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? route('scenes.media.audio', $this, false) : null;
    }
}
