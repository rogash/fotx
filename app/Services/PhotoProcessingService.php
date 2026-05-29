<?php

namespace App\Services;

use App\Jobs\IndexPhotoFacesJob;
use App\Models\EventPhoto;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PhotoProcessingService
{
    private const PUBLIC_MAX_DIMENSION = 1600;

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
            $this->make_watermark($source_path, $watermarked_temp_path, $event_photo);

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
            $event_photo->batch?->refresh_progress();

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
    }

    private function make_watermark(string $source_path, string $target_path, EventPhoto $event_photo): void
    {
        $image = $this->open_image($source_path, $event_photo->mime_type);
        if (! $image) {
            copy($source_path, $target_path);

            return;
        }

        $watermarked = $this->resized_public_canvas($image);
        $this->apply_tiled_watermark($watermarked, $event_photo);

        imageinterlace($watermarked, true);
        imagejpeg($watermarked, $target_path, 78);
    }

    private function resized_public_canvas(mixed $image): mixed
    {
        $source_width = imagesx($image);
        $source_height = imagesy($image);
        $scale = min(1, self::PUBLIC_MAX_DIMENSION / max($source_width, $source_height));
        $target_width = (int) max(1, round($source_width * $scale));
        $target_height = (int) max(1, round($source_height * $scale));
        $canvas = imagecreatetruecolor($target_width, $target_height);

        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);

        return $canvas;
    }

    private function apply_tiled_watermark(mixed $image, EventPhoto $event_photo): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $seed = crc32((string) ($event_photo->public_id ?: $event_photo->id));
        $opacity = 15 + ($seed % 8);
        $tile_width = (int) max(280, min(520, round($width * 0.28)));
        $tile_height = (int) round($tile_width * 0.54);
        $tile = $this->make_watermark_tile($tile_width, $tile_height, $event_photo);
        $rotated_tile = imagerotate($tile, -28, imagecolorallocatealpha($tile, 0, 0, 0, 127));

        imagealphablending($rotated_tile, false);
        imagesavealpha($rotated_tile, true);
        $this->apply_image_opacity($rotated_tile, $opacity);

        $rotated_width = imagesx($rotated_tile);
        $rotated_height = imagesy($rotated_tile);
        $offset_x = -$rotated_width + (int) ($seed % max(1, $rotated_width));
        $offset_y = -$rotated_height + (int) (($seed >> 8) % max(1, $rotated_height));

        for ($y = $offset_y; $y < $height + $rotated_height; $y += (int) round($rotated_height * 0.78)) {
            for ($x = $offset_x; $x < $width + $rotated_width; $x += (int) round($rotated_width * 0.82)) {
                imagecopy($image, $rotated_tile, $x, $y, 0, 0, $rotated_width, $rotated_height);
            }
        }

        $this->draw_corner_guard($image, $event_photo);
    }

    private function make_watermark_tile(int $width, int $height, EventPhoto $event_photo): mixed
    {
        $tile = imagecreatetruecolor($width, $height);
        imagealphablending($tile, false);
        imagesavealpha($tile, true);
        imagefill($tile, 0, 0, imagecolorallocatealpha($tile, 0, 0, 0, 127));
        imagealphablending($tile, true);

        $logo = $this->watermark_logo();
        if ($logo) {
            $logo_width = (int) round($width * 0.62);
            $logo_height = (int) round($logo_width * (imagesy($logo) / imagesx($logo)));
            $x = (int) round(($width - $logo_width) / 2);
            $y = (int) round(($height - $logo_height) / 2);

            imagecopyresampled($tile, $logo, $x, $y, 0, 0, $logo_width, $logo_height, imagesx($logo), imagesy($logo));
        }

        $label = 'fotX - '.$event_photo->event->slug;
        $text_color = imagecolorallocatealpha($tile, 255, 255, 255, 38);
        imagestring($tile, 3, 16, max(12, $height - 30), $label, $text_color);

        return $tile;
    }

    private function draw_corner_guard(mixed $image, EventPhoto $event_photo): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $guard = 'fotX'.' / '.$event_photo->public_id;
        $text_color = imagecolorallocatealpha($image, 255, 255, 255, 34);
        $shadow_color = imagecolorallocatealpha($image, 0, 0, 0, 70);
        $x = (int) max(16, round($width * 0.035));
        $y = (int) max(16, round($height * 0.92));

        imagestring($image, 4, $x + 1, $y + 1, $guard, $shadow_color);
        imagestring($image, 4, $x, $y, $guard, $text_color);
    }

    private function apply_image_opacity(mixed $image, int $opacity): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $opacity = max(0, min(100, $opacity));

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                if ($alpha >= 127) {
                    continue;
                }

                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;
                $new_alpha = 127 - (int) round((127 - $alpha) * ($opacity / 100));

                imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $red, $green, $blue, $new_alpha));
            }
        }
    }

    private function watermark_logo(): mixed
    {
        $logo_path = public_path('brand/fotx-logo-white.png');

        return file_exists($logo_path) ? imagecreatefrompng($logo_path) : false;
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

        return $path ?: throw new \RuntimeException('Não foi possível criar arquivo temporário.');
    }
}
