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
        'scene_text',
        'gif_path',
        'gif_original_name',
        'image_path',
        'image_original_name',
        'audio_path',
        'audio_original_name',
        'media_status',
        'media_error',
        'media_started_at',
        'media_completed_at',
        'media_attempts',
        'source_video_path',
        'source_video_original_name',
        'duration_seconds',
        'position',
        'sort_order',
        'position_label',
        'from_scene_id',
        'to_scene_id',
        'transition_template_id',
        'next_transition_template_id',
        'created_by',
        'created_by_name',
    ];

    protected $appends = [
        'gif_url',
        'audio_url',
    ];

    protected $casts = [
        'media_started_at' => 'datetime',
        'media_completed_at' => 'datetime',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $user->canReviewContent() || $user->isAdmin()
            ? $query
            : $query->where('created_by', $user->id);
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

    public function isMediaPending(): bool
    {
        return in_array($this->media_status, ['pending', 'processing'], true);
    }

    public function hasMediaFailed(): bool
    {
        return $this->media_status === 'failed';
    }

    public function getGifUrlAttribute(): ?string
    {
        return $this->gif_path
            ? route('scenes.media.gif', $this, false).'?v='.$this->mediaVersion($this->gif_path)
            : null;
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path
            ? route('scenes.media.audio', $this, false).'?v='.$this->mediaVersion($this->audio_path)
            : null;
    }

    private function mediaVersion(string $path): string
    {
        return md5($path.'|'.$this->updated_at?->timestamp.'|'.$this->id);
    }
}
