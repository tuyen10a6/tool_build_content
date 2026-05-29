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
        'created_by',
        'created_by_name',
    ];

    public function scopeVisibleTo($query, User $user)
    {
        return $user->isAdmin()
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

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('sort_order')->orderBy('position');
    }

    public function mainScenes(): HasMany
    {
        return $this->hasMany(Scene::class)->where('scene_type', 'main')->orderBy('position');
    }
}
