<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'full_name',
        'phone',
        'note',
        'role',
        'status',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function contents(): HasMany
    {
        return $this->hasMany(ContentItem::class, 'created_by');
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class, 'created_by');
    }

    public function exportLogs(): HasMany
    {
        return $this->hasMany(ExportLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->name ?: $this->username;
    }
}
