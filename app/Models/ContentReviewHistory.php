<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReviewHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_item_id',
        'from_status',
        'to_status',
        'comment',
        'acted_by',
        'acted_by_name',
        'acted_role',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
