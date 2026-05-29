<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'uploaded_by', 'status', 'total_files', 'processed_files', 'failed_files', 'original_total_bytes'])]
class PhotoBatch extends Model
{
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class);
    }

    public function refresh_progress(): void
    {
        $total_files = $this->photos()->count();
        $processed_files = $this->photos()->where('status', 'ready')->count();
        $failed_files = $this->photos()->where('status', 'failed')->count();
        $processing_files = $this->photos()->whereIn('status', ['uploaded', 'processing'])->count();

        $this->update([
            'total_files' => $total_files,
            'processed_files' => $processed_files,
            'failed_files' => $failed_files,
            'original_total_bytes' => (int) $this->photos()->sum('size_bytes'),
            'status' => match (true) {
                $total_files === 0 => 'pending',
                $failed_files > 0 && $processed_files + $failed_files === $total_files => 'failed',
                $processing_files > 0 => 'processing',
                default => 'done',
            },
        ]);
    }
}
