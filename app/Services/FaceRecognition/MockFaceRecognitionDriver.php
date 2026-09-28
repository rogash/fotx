<?php

namespace App\Services\FaceRecognition;

use App\Models\Event;
use App\Models\PhotoFace;
use Illuminate\Support\Str;

/**
 * Driver de desenvolvimento: indexa um rosto fictício por foto e devolve
 * rostos aleatórios do evento na busca, sem analisar as imagens.
 */
class MockFaceRecognitionDriver implements FaceRecognitionDriver
{
    public function index_faces(Event $event, string $image_bytes, string $external_image_id): array
    {
        return [[
            'provider_face_id' => 'mock-'.Str::uuid(),
            'face_box' => ['x' => 0.22, 'y' => 0.18, 'w' => 0.28, 'h' => 0.38],
            'confidence' => random_int(7200, 9900) / 10000,
        ]];
    }

    public function search_faces(Event $event, string $image_bytes): array
    {
        return PhotoFace::query()
            ->where('event_id', $event->id)
            ->whereNotNull('provider_face_id')
            ->inRandomOrder()
            ->limit(12)
            ->pluck('provider_face_id')
            ->map(fn (string $provider_face_id): array => [
                'provider_face_id' => $provider_face_id,
                'similarity' => random_int(55, 98) / 100,
            ])
            ->all();
    }

    public function delete_faces(Event $event, array $provider_face_ids): void {}

    public function delete_event(Event $event): void {}
}
