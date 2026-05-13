<?php

namespace App\Services;

use App\Jobs\IndexPhotoFacesJob;
use App\Models\EventPhoto;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PhotoProcessingService
{
    public function process(EventPhoto $event_photo): EventPhoto
    {
        $event_photo->update(['status' => 'processing']);

        try {
            $disk = Storage::disk($this->disk_name());
            $source_path = $this->temporary_file('fotx_source_');
            file_put_contents($source_path, $disk->get($event_photo->original_path));

            [$width, $height] = getimagesize($source_path) ?: [null, null];

            $thumbnail_path = $this->variant_path($event_photo, 'thumbnails');
            $watermarked_path = $this->variant_path($event_photo, 'watermarked');
            $thumbnail_temp_path = $this->temporary_file('fotx_thumb_');
            $watermarked_temp_path = $this->temporary_file('fotx_watermarked_');

            $this->make_thumbnail($source_path, $thumbnail_temp_path, $event_photo->mime_type);
            $this->make_watermark($source_path, $watermarked_temp_path, $event_photo->mime_type);

            $disk->put($thumbnail_path, fopen($thumbnail_temp_path, 'r'));
            $disk->put($watermarked_path, fopen($watermarked_temp_path, 'r'));

            $event_photo->update([
                'thumbnail_path' => $thumbnail_path,
                'watermarked_path' => $watermarked_path,
                'width' => $width,
                'height' => $height,
                'status' => 'ready',
            ]);

            IndexPhotoFacesJob::dispatch($event_photo);
        } catch (Throwable) {
            $event_photo->update(['status' => 'failed']);
        } finally {
            foreach ([$source_path ?? null, $thumbnail_temp_path ?? null, $watermarked_temp_path ?? null] as $temporary_path) {
                if ($temporary_path && file_exists($temporary_path)) {
                    unlink($temporary_path);
                }
            }
        }

        return $event_photo->fresh();
    }

    public function disk_name(): string
    {
        return config('filesystems.default', 'local');
    }

    private function variant_path(EventPhoto $event_photo, string $folder): string
    {
        $basename = pathinfo($event_photo->filename, PATHINFO_FILENAME);

        return "events/{$event_photo->event_id}/{$folder}/{$basename}.jpg";
    }

    private function make_thumbnail(string $source_path, string $target_path, string $mime_type): void
    {
        $image = $this->open_image($source_path, $mime_type);
        if (! $image) {
            copy($source_path, $target_path);

            return;
        }

        $source_width = imagesx($image);
        $source_height = imagesy($image);
        $thumb_width = 420;
        $thumb_height = (int) max(1, round($source_height * ($thumb_width / $source_width)));
        $thumbnail = imagecreatetruecolor($thumb_width, $thumb_height);

        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, $source_width, $source_height);
        imagejpeg($thumbnail, $target_path, 84);
        imagedestroy($image);
        imagedestroy($thumbnail);
    }

    private function make_watermark(string $source_path, string $target_path, string $mime_type): void
    {
        $image = $this->open_image($source_path, $mime_type);
        if (! $image) {
            copy($source_path, $target_path);

            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $font_size = max(5, (int) round(min($width, $height) / 14));
        $text = 'FOTX';
        $color = imagecolorallocatealpha($image, 255, 255, 255, 45);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 75);
        $x = (int) round($width * 0.08);
        $y = (int) round($height * 0.55);

        imagestring($image, $font_size, $x + 2, $y + 2, $text, $shadow);
        imagestring($image, $font_size, $x, $y, $text, $color);
        imagejpeg($image, $target_path, 86);
        imagedestroy($image);
    }

    private function open_image(string $path, string $mime_type): mixed
    {
        return match ($mime_type) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    private function temporary_file(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        return $path ?: throw new \RuntimeException('Nao foi possivel criar arquivo temporario.');
    }
}
