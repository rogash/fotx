<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\PhotoFace;
use App\Services\FaceRecognition\FaceRecognitionManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FaceRecognitionService
{
    // Mantém a imagem enviada ao provedor bem abaixo do limite de 5 MB do Rekognition.
    private const MAX_IMAGE_DIMENSION = 1920;

    public function __construct(private FaceRecognitionManager $face_recognition_manager) {}

    /**
     * @return list<array{event_photo_id: int, score: float}>
     */
    public function search_by_selfie(Event $event, string $selfie_path): array
    {
        $image_bytes = $this->prepare_image($this->disk()->get($selfie_path));
        $matches = collect($this->face_recognition_manager->driver()->search_faces($event, $image_bytes));

        if ($matches->isEmpty()) {
            return [];
        }

        $photo_ids_by_face = PhotoFace::query()
            ->where('event_id', $event->id)
            ->whereIn('provider_face_id', $matches->pluck('provider_face_id'))
            ->pluck('event_photo_id', 'provider_face_id');

        $ready_photo_ids = $event->ready_photos()
            ->whereIn('event_photos.id', $photo_ids_by_face->unique()->values())
            ->pluck('event_photos.id');

        return $matches
            ->filter(fn (array $match): bool => $ready_photo_ids->contains($photo_ids_by_face->get($match['provider_face_id'])))
            ->groupBy(fn (array $match): int => $photo_ids_by_face->get($match['provider_face_id']))
            ->map(fn ($photo_matches, int $event_photo_id): array => [
                'event_photo_id' => $event_photo_id,
                'score' => round((float) $photo_matches->max('similarity'), 4),
            ])
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    /**
     * Reindexa a foto: substitui os rostos anteriores pelos detectados agora.
     */
    public function index_photo(EventPhoto $event_photo): array
    {
        $event_photo->loadMissing('event');
        $driver = $this->face_recognition_manager->driver();
        $previous_face_ids = $event_photo->faces()->whereNotNull('provider_face_id')->pluck('provider_face_id')->all();

        $faces = $driver->index_faces(
            $event_photo->event,
            $this->prepare_image($this->disk()->get($event_photo->original_path)),
            (string) $event_photo->public_id,
        );

        DB::transaction(function () use ($event_photo, $faces): void {
            $event_photo->faces()->delete();

            foreach ($faces as $face) {
                $event_photo->faces()->create([
                    'event_id' => $event_photo->event_id,
                    'provider_face_id' => $face['provider_face_id'],
                    'face_box' => $face['face_box'],
                    'confidence' => $face['confidence'],
                ]);
            }
        });

        if ($previous_face_ids !== []) {
            $driver->delete_faces($event_photo->event, $previous_face_ids);
        }

        return $faces;
    }

    public function forget_photo(EventPhoto $event_photo): void
    {
        $face_ids = $event_photo->faces()->whereNotNull('provider_face_id')->pluck('provider_face_id')->all();

        if ($face_ids !== []) {
            $this->face_recognition_manager->driver()->delete_faces($event_photo->event, $face_ids);
        }
    }

    public function forget_event(Event $event): void
    {
        $this->face_recognition_manager->driver()->delete_event($event);
    }

    /**
     * Normaliza para JPEG (o Rekognition não aceita WebP), aplica a rotação
     * EXIF de fotos de celular e limita as dimensões.
     */
    private function prepare_image(string $image_bytes): string
    {
        $image = imagecreatefromstring($image_bytes);

        if (! $image) {
            throw new RuntimeException('Não foi possível ler a imagem para reconhecimento facial.');
        }

        $image = $this->apply_exif_orientation($image, $image_bytes);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_IMAGE_DIMENSION / max($width, $height));

        if ($scale < 1) {
            $image = imagescale($image, (int) max(1, round($width * $scale)), (int) max(1, round($height * $scale)));
        }

        ob_start();
        imagejpeg($image, null, 90);

        return (string) ob_get_clean();
    }

    private function apply_exif_orientation(mixed $image, string $image_bytes): mixed
    {
        if (! str_starts_with($image_bytes, "\xFF\xD8")) {
            return $image;
        }

        // EXIF malformado é comum em fotos de celular; sem EXIF legível, mantém a orientação.
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($image_bytes));

        return match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }
}
