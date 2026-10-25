<?php

namespace App\Services\FaceRecognition;

use App\Models\Event;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\Rekognition\RekognitionClient;
use Aws\ResultInterface;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Collection;

/**
 * Cada evento tem sua própria coleção no Rekognition, criada sob demanda
 * na primeira indexação e removida quando o evento é excluído.
 */
class RekognitionFaceRecognitionDriver implements FaceRecognitionDriver
{
    private const MAX_FACES_PER_PHOTO = 100;

    private const DELETE_FACES_BATCH_SIZE = 4096;

    /**
     * Buscas simultâneas na vitrine. A AWS limita as chamadas por segundo de
     * SearchFacesByImage por conta e região, então o lote fica pequeno.
     */
    private const PARALLEL_SEARCHES = 5;

    public function __construct(private RekognitionClient $client) {}

    public function index_faces(Event $event, string $image_bytes, string $external_image_id): array
    {
        $request = [
            'CollectionId' => $this->collection_id($event),
            'Image' => ['Bytes' => $image_bytes],
            'ExternalImageId' => $external_image_id,
            'MaxFaces' => self::MAX_FACES_PER_PHOTO,
            'QualityFilter' => 'AUTO',
        ];

        try {
            $result = $this->client->indexFaces($request);
        } catch (RekognitionException $exception) {
            if ($exception->getAwsErrorCode() !== 'ResourceNotFoundException') {
                throw $exception;
            }

            $this->client->createCollection(['CollectionId' => $this->collection_id($event)]);
            $result = $this->client->indexFaces($request);
        }

        return collect($result['FaceRecords'] ?? [])
            ->map(fn (array $face_record): array => [
                'provider_face_id' => (string) $face_record['Face']['FaceId'],
                'face_box' => [
                    'x' => (float) $face_record['Face']['BoundingBox']['Left'],
                    'y' => (float) $face_record['Face']['BoundingBox']['Top'],
                    'w' => (float) $face_record['Face']['BoundingBox']['Width'],
                    'h' => (float) $face_record['Face']['BoundingBox']['Height'],
                ],
                'confidence' => (float) $face_record['Face']['Confidence'] / 100,
            ])
            ->values()
            ->all();
    }

    public function search_faces(Event $event, string $image_bytes): array
    {
        try {
            return $this->face_matches($this->client->searchFacesByImage($this->search_request($event, $image_bytes)));
        } catch (RekognitionException $exception) {
            return $this->empty_search_or_throw($exception);
        }
    }

    public function search_faces_in_events(Collection $events, string $image_bytes): array
    {
        $matches_by_event = [];

        foreach ($events->chunk(self::PARALLEL_SEARCHES) as $events_batch) {
            $promises = $events_batch->mapWithKeys(fn (Event $event): array => [
                $event->id => $this->client->searchFacesByImageAsync($this->search_request($event, $image_bytes)),
            ])->all();

            foreach (Utils::settle($promises)->wait() as $event_id => $outcome) {
                $matches_by_event[$event_id] = $outcome['state'] === 'fulfilled'
                    ? $this->face_matches($outcome['value'])
                    : $this->empty_search_or_throw($outcome['reason']);
            }
        }

        return $matches_by_event;
    }

    public function delete_faces(Event $event, array $provider_face_ids): void
    {
        foreach (array_chunk($provider_face_ids, self::DELETE_FACES_BATCH_SIZE) as $face_ids_batch) {
            try {
                $this->client->deleteFaces([
                    'CollectionId' => $this->collection_id($event),
                    'FaceIds' => $face_ids_batch,
                ]);
            } catch (RekognitionException $exception) {
                if ($exception->getAwsErrorCode() !== 'ResourceNotFoundException') {
                    throw $exception;
                }
            }
        }
    }

    public function delete_event(Event $event): void
    {
        try {
            $this->client->deleteCollection(['CollectionId' => $this->collection_id($event)]);
        } catch (RekognitionException $exception) {
            if ($exception->getAwsErrorCode() !== 'ResourceNotFoundException') {
                throw $exception;
            }
        }
    }

    private function search_request(Event $event, string $image_bytes): array
    {
        return [
            'CollectionId' => $this->collection_id($event),
            'Image' => ['Bytes' => $image_bytes],
            'FaceMatchThreshold' => (float) config('fotx.face_match_threshold', 90),
            'MaxFaces' => (int) config('fotx.face_search_max_results', 50),
            'QualityFilter' => 'AUTO',
        ];
    }

    /**
     * @return list<array{provider_face_id: string, similarity: float}>
     */
    private function face_matches(ResultInterface $result): array
    {
        return collect($result['FaceMatches'] ?? [])
            ->map(fn (array $face_match): array => [
                'provider_face_id' => (string) $face_match['Face']['FaceId'],
                'similarity' => (float) $face_match['Similarity'] / 100,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{}
     */
    private function empty_search_or_throw(mixed $exception): array
    {
        if (! $exception instanceof RekognitionException) {
            throw $exception;
        }

        return match ($exception->getAwsErrorCode()) {
            // Evento sem nenhuma foto indexada ainda.
            'ResourceNotFoundException' => [],
            // O Rekognition responde assim quando não encontra rosto na selfie.
            'InvalidParameterException' => throw new NoFaceDetectedException('Nenhum rosto encontrado na selfie.', previous: $exception),
            default => throw $exception,
        };
    }

    private function collection_id(Event $event): string
    {
        $prefix = preg_replace('/[^a-zA-Z0-9_.\-]/', '-', (string) config('fotx.rekognition_collection_prefix'));

        return "{$prefix}-event-{$event->id}";
    }
}
