<?php

namespace App\Models;

use Database\Factories\EventPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'event_id', 'photo_batch_id', 'uploaded_by', 'photographer_id', 'original_path', 'watermarked_path', 'thumbnail_path', 'filename', 'file_hash', 'participant_code', 'search_keywords', 'mime_type', 'size_bytes', 'width', 'height', 'status'])]
class EventPhoto extends Model
{
    /** @use HasFactory<EventPhotoFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (EventPhoto $event_photo): void {
            $event_photo->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function faces(): HasMany
    {
        return $this->hasMany(PhotoFace::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PhotoBatch::class, 'photo_batch_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function public_url(): string
    {
        return route('public.photos.show', [$this->event->slug, $this]);
    }
}
