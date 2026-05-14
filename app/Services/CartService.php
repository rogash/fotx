<?php

namespace App\Services;

use App\Models\EventPhoto;
use Illuminate\Support\Collection;

class CartService
{
    private const SESSION_KEY = 'fotx_cart';

    public function add_photo(EventPhoto $event_photo): void
    {
        $cart = session(self::SESSION_KEY, []);
        $cart[$event_photo->id] = [
            'event_photo_id' => $event_photo->id,
            'event_id' => $event_photo->event_id,
            'price' => (float) $event_photo->event->price_per_photo,
        ];

        session([self::SESSION_KEY => $cart]);
    }

    public function remove_photo(int $event_photo_id): void
    {
        $cart = session(self::SESSION_KEY, []);
        unset($cart[$event_photo_id]);
        session([self::SESSION_KEY => $cart]);
    }

    public function has_photo(int $event_photo_id): bool
    {
        return array_key_exists($event_photo_id, session(self::SESSION_KEY, []));
    }

    public function count(): int
    {
        return $this->get_items()->count();
    }

    public function get_items(): Collection
    {
        $cart = collect(session(self::SESSION_KEY, []));
        $photo_ids = $cart->pluck('event_photo_id')->all();
        $photos = EventPhoto::query()->with('event')->whereIn('id', $photo_ids)->get()->keyBy('id');

        return $cart
            ->map(fn (array $item): ?array => $photos->has($item['event_photo_id']) ? [
                ...$item,
                'photo' => $photos[$item['event_photo_id']],
            ] : null)
            ->filter()
            ->values();
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function total(): float
    {
        return (float) $this->get_items()->sum('price');
    }
}
