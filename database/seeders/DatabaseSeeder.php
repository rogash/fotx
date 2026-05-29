<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\PhotoBatch;
use App\Models\User;
use App\Services\PhotoProcessingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@fotx.test'],
            ['name' => 'Admin Fotx', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        $photographer = User::query()->updateOrCreate(
            ['email' => 'fotografo@fotx.test'],
            ['name' => 'Fotógrafo Demo', 'password' => Hash::make('password'), 'role' => 'photographer']
        );

        User::query()->updateOrCreate(
            ['email' => 'cliente@fotx.test'],
            ['name' => 'Cliente Demo', 'password' => Hash::make('password'), 'role' => 'customer']
        );

        $event = Event::query()->updateOrCreate(
            ['slug' => 'casamento-demo-fotx'],
            [
                'user_id' => $photographer->id,
                'name' => 'Casamento Demo Fotx',
                'event_date' => now()->addDays(10)->toDateString(),
                'location' => 'São Paulo, SP',
                'description' => 'Uma galeria demonstrativa para testar busca por selfie, carrinho e downloads protegidos.',
                'price_per_photo' => 29.90,
                'status' => 'published',
            ]
        );

        $event->members()->updateOrCreate(
            ['user_id' => $photographer->id],
            ['role' => 'owner'],
        );

        if ($event->photos()->doesntExist()) {
            $this->create_sample_photos($event, $photographer);
        }

        $event->update(['cover_photo_id' => $event->photos()->where('status', 'ready')->value('id')]);
    }

    private function create_sample_photos(Event $event, User $photographer): void
    {
        $disk = Storage::disk(config('filesystems.default'));
        $batch = PhotoBatch::query()->create([
            'event_id' => $event->id,
            'uploaded_by' => $photographer->id,
            'status' => 'uploading',
        ]);

        foreach (range(1, 8) as $index) {
            $filename = "demo-{$index}.jpg";
            $path = "events/{$event->id}/originals/".Str::uuid().'.jpg';
            $absolute_path = $disk->path($path);
            $directory = dirname($absolute_path);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $this->make_sample_image($absolute_path, $index);

            $event_photo = EventPhoto::query()->create([
                'event_id' => $event->id,
                'photo_batch_id' => $batch->id,
                'uploaded_by' => $photographer->id,
                'photographer_id' => $photographer->id,
                'original_path' => $path,
                'filename' => $filename,
                'file_hash' => hash_file('sha256', $absolute_path),
                'mime_type' => 'image/jpeg',
                'size_bytes' => filesize($absolute_path),
                'status' => 'uploaded',
            ]);

            app(PhotoProcessingService::class)->process($event_photo);
        }

        $batch->refresh_progress();
    }

    private function make_sample_image(string $path, int $index): void
    {
        $image = imagecreatetruecolor(1400, 950);
        $palette = [[15, 23, 42], [6, 78, 59], [30, 64, 175], [126, 34, 206]];
        [$r, $g, $b] = $palette[$index % count($palette)];
        $background = imagecolorallocate($image, $r, $g, $b);
        $accent = imagecolorallocate($image, 236, 253, 245);

        imagefilledrectangle($image, 0, 0, 1400, 950, $background);
        imagefilledellipse($image, 700, 475, 620, 620, imagecolorallocatealpha($image, 255, 255, 255, 95));
        imagestring($image, 5, 580, 450, "FOTX DEMO {$index}", $accent);
        imagejpeg($image, $path, 88);
        imagedestroy($image);
    }
}
