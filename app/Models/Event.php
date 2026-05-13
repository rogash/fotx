<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'slug', 'event_date', 'location', 'description', 'price_per_photo', 'status', 'cover_photo_id'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'price_per_photo' => 'decimal:2',
        ];
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class);
    }

    public function ready_photos(): HasMany
    {
        return $this->photos()->where('status', 'ready');
    }

    public function cover_photo(): BelongsTo
    {
        return $this->belongsTo(EventPhoto::class, 'cover_photo_id');
    }

    public function searches(): HasMany
    {
        return $this->hasMany(FaceSearch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function public_url(): string
    {
        return route('public.events.show', $this->slug);
    }
}
