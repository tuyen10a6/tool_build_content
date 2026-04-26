<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Scene extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_item_id',
        'scene_type',
        'name',
        'gif_path',
        'gif_original_name',
        'audio_path',
        'audio_original_name',
        'duration_seconds',
        'position',
        'sort_order',
        'position_label',
        'from_scene_id',
        'to_scene_id',
        'transition_template_id',
        'next_transition_template_id',
    ];

    protected $appends = [
        'gif_url',
        'audio_url',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function transitionTemplate(): BelongsTo
    {
        return $this->belongsTo(TransitionTemplate::class, 'transition_template_id');
    }

    public function nextTransitionTemplate(): BelongsTo
    {
        return $this->belongsTo(TransitionTemplate::class, 'next_transition_template_id');
    }

    public function fromScene(): BelongsTo
    {
        return $this->belongsTo(Scene::class, 'from_scene_id');
    }

    public function toScene(): BelongsTo
    {
        return $this->belongsTo(Scene::class, 'to_scene_id');
    }

    public function outgoingTransition(): HasOne
    {
        return $this->hasOne(Scene::class, 'from_scene_id')->where('scene_type', 'transition');
    }

    public function isMain(): bool
    {
        return $this->scene_type === 'main';
    }

    public function isTransition(): bool
    {
        return $this->scene_type === 'transition';
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
