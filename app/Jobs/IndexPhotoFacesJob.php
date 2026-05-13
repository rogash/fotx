<?php

namespace App\Jobs;

use App\Models\EventPhoto;
use App\Services\FaceRecognitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexPhotoFacesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventPhoto $event_photo) {}

    public function handle(FaceRecognitionService $face_recognition_service): void
    {
        $face_recognition_service->index_photo($this->event_photo);
    }
}
