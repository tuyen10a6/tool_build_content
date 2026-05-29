<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'export_type',
        'file_name',
        'data_scope',
        'exported_at',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $user->isAdmin()
            ? $query
            : $query->where('user_id', $user->id);
    }
}
