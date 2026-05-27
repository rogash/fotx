<?php

namespace App\Console\Commands;

use App\Models\FaceSearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredSelfiesCommand extends Command
{
    protected $signature = 'fotx:purge-expired-selfies {--dry-run : Apenas mostra quantas buscas expiradas seriam removidas}';

    protected $description = 'Remove selfies temporarias e buscas faciais expiradas.';

    public function handle(): int
    {
        $query = FaceSearch::query()->whereNotNull('expires_at')->where('expires_at', '<=', now());
        $total = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Buscas expiradas encontradas: {$total}");

            return self::SUCCESS;
        }

        $deleted_files = 0;

        $query->orderBy('id')->chunkById(200, function ($face_searches) use (&$deleted_files): void {
            foreach ($face_searches as $face_search) {
                if ($face_search->selfie_path && Storage::disk(config('filesystems.default'))->exists($face_search->selfie_path)) {
                    Storage::disk(config('filesystems.default'))->delete($face_search->selfie_path);
                    $deleted_files++;
                }

                $face_search->delete();
            }
        });

        $this->info("Buscas removidas: {$total}");
        $this->info("Arquivos removidos: {$deleted_files}");

        return self::SUCCESS;
    }
}
