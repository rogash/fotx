<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\PhotoFace;

class FaceRecognitionService
{
    /**
     * Future integration point:
     * POST http://127.0.0.1:8001/search-face
     */
    public function search_by_selfie(Event $event, string $selfie_path): array
    {
        return $event->ready_photos()
            ->inRandomOrder()
            ->limit(12)
            ->get()
            ->map(fn (EventPhoto $event_photo): array => [
                'event_photo_id' => $event_photo->id,
                'score' => random_int(55, 98) / 100,
            ])
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    public function index_photo(EventPhoto $event_photo): array
    {
        $face = PhotoFace::query()->create([
            'event_id' => $event_photo->event_id,
            'event_photo_id' => $event_photo->id,
            'face_box' => ['x' => 0.22, 'y' => 0.18, 'w' => 0.28, 'h' => 0.38],
            'embedding' => collect(range(1, 12))->map(fn (): float => random_int(-10000, 10000) / 10000)->all(),
            'confidence' => random_int(7200, 9900) / 10000,
        ]);

        return $face->toArray();
    }
}
