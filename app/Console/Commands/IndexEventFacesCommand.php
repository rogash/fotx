<?php

namespace App\Console\Commands;

use App\Jobs\IndexPhotoFacesJob;
use App\Models\Event;
use Illuminate\Console\Command;

class IndexEventFacesCommand extends Command
{
    protected $signature = 'fotx:index-faces {event : ID ou slug do evento}';

    protected $description = 'Enfileira a (re)indexação facial das fotos prontas de um evento.';

    public function handle(): int
    {
        $identifier = (string) $this->argument('event');
        $event = Event::query()
            ->where('slug', $identifier)
            ->when(ctype_digit($identifier), fn ($query) => $query->orWhere('id', (int) $identifier))
            ->first();

        if (! $event) {
            $this->error("Evento não encontrado: {$identifier}");

            return self::FAILURE;
        }

        $total = 0;

        $event->ready_photos()->chunkById(200, function ($event_photos) use (&$total): void {
            foreach ($event_photos as $event_photo) {
                IndexPhotoFacesJob::dispatch($event_photo);
                $total++;
            }
        }, 'event_photos.id', 'id');

        $this->info("Fotos enviadas para indexação: {$total}");

        return self::SUCCESS;
    }
}
