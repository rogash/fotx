<?php

namespace App\Jobs;

use App\Models\EventPhoto;
use App\Services\PhotoProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessEventPhotoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventPhoto $event_photo) {}

    public function handle(PhotoProcessingService $photo_processing_service): void
    {
        $photo_processing_service->process($this->event_photo);
    }
}
