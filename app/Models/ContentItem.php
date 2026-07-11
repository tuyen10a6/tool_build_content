<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'approval_status',
        'review_comment',
        'reviewed_by',
        'reviewed_by_name',
        'reviewed_at',
        'submitted_at',
        'revision_requested_count',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function scopeVisibleTo($query, User $user)
    {
        return $user->canReviewContent() || $user->isAdmin()
            ? $query
            : $query->where('created_by', $user->id);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('sort_order')->orderBy('position');
    }

    public function mainScenes(): HasMany
    {
        return $this->hasMany(Scene::class)->where('scene_type', 'main')->orderBy('position');
    }

    public function reviewHistories(): HasMany
    {
        return $this->hasMany(ContentReviewHistory::class)->latest();
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this->approval_status, ['draft', 'needs_revision'], true);
    }
}
