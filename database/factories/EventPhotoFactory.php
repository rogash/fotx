<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPhoto>
 */
class EventPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'original_path' => 'events/1/originals/sample.jpg',
            'watermarked_path' => 'events/1/watermarked/sample.jpg',
            'thumbnail_path' => 'events/1/thumbnails/sample.jpg',
            'filename' => 'sample.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width' => 1200,
            'height' => 800,
            'status' => 'ready',
        ];
    }
}
