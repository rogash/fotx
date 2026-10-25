<?php

namespace App\Services\FaceRecognition;

use App\Models\Event;
use Illuminate\Support\Collection;

interface FaceRecognitionDriver
{
    /**
     * @return list<array{provider_face_id: string, face_box: array{x: float, y: float, w: float, h: float}, confidence: float}>
     */
    public function index_faces(Event $event, string $image_bytes, string $external_image_id): array;

    /**
     * @return list<array{provider_face_id: string, similarity: float}>
     *
     * @throws NoFaceDetectedException
     */
    public function search_faces(Event $event, string $image_bytes): array;

    /**
     * Compara a mesma selfie com as coleções de vários eventos.
     *
     * @param  Collection<int, Event>  $events
     * @return array<int, list<array{provider_face_id: string, similarity: float}>> chave: id do evento
     *
     * @throws NoFaceDetectedException
     */
    public function search_faces_in_events(Collection $events, string $image_bytes): array;

    /**
     * @param  list<string>  $provider_face_ids
     */
    public function delete_faces(Event $event, array $provider_face_ids): void;

    public function delete_event(Event $event): void;
}
