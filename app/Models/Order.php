<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['event_id', 'buyer_name', 'buyer_email', 'total_amount', 'status', 'payment_provider', 'payment_reference', 'download_token'])]
class Order extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->download_token ??= Str::random(48);
        });
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function has_photo(EventPhoto $event_photo): bool
    {
        return $this->items()->where('event_photo_id', $event_photo->id)->exists();
    }
}
