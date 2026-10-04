<?php

namespace App\Jobs;

use App\Models\EventPhoto;
use App\Services\FaceRecognitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexPhotoFacesJob implements ShouldQueue
{
    use Queueable;

    // O Rekognition limita chamadas por segundo; uploads grandes precisam de novas tentativas.
    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120];

    public function __construct(public EventPhoto $event_photo) {}

    public function handle(FaceRecognitionService $face_recognition_service): void
    {
        $face_recognition_service->index_photo($this->event_photo);
    }
}
