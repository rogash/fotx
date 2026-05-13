<?php

namespace App\Models;

use Database\Factories\EventPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'original_path', 'watermarked_path', 'thumbnail_path', 'filename', 'mime_type', 'size_bytes', 'width', 'height', 'status'])]
class EventPhoto extends Model
{
    /** @use HasFactory<EventPhotoFactory> */
    use HasFactory;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function faces(): HasMany
    {
        return $this->hasMany(PhotoFace::class);
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
