<?php

namespace Tests\Feature\Fotx;

use App\Jobs\IndexPhotoFacesJob;
use App\Livewire\Photographer\EventPhotoUploader;
use App\Livewire\Public\SelfieSearch;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Models\PhotoFace;
use App\Models\User;
use App\Services\FaceRecognition\FaceRecognitionManager;
use App\Services\FaceRecognitionService;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\Rekognition\RekognitionClient;
use Aws\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class FaceRecognitionTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{0: string, 1: array}> */
    private array $rekognition_calls = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_mock_driver_indexes_photo_and_selfie_search_finds_it(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $event_photo = $this->create_photo_with_image($event);

        app(FaceRecognitionService::class)->index_photo($event_photo);

        $this->assertNotNull($event_photo->faces()->value('provider_face_id'));

        $this->search_selfie($event)->assertCount('results', 1);
    }

    public function test_rekognition_creates_collection_on_first_index_and_stores_faces(): void
    {
        $event = Event::factory()->create();
        $event_photo = $this->create_photo_with_image($event);
        $this->fake_rekognition([
            'ResourceNotFoundException',
            [],
            ['FaceRecords' => [$this->face_record('face-a', 99.1), $this->face_record('face-b', 97.5)]],
        ]);

        app(FaceRecognitionService::class)->index_photo($event_photo);

        $this->assertSame(['IndexFaces', 'CreateCollection', 'IndexFaces'], array_column($this->rekognition_calls, 0));
        $this->assertSame("fotx-testing-event-{$event->id}", $this->rekognition_calls[1][1]['CollectionId']);
        $this->assertSame($event_photo->public_id, $this->rekognition_calls[2][1]['ExternalImageId']);
        $this->assertEqualsCanonicalizing(['face-a', 'face-b'], $event_photo->faces()->pluck('provider_face_id')->all());
    }

    public function test_reindexing_replaces_faces_and_removes_previous_ones_from_provider(): void
    {
        $event = Event::factory()->create();
        $event_photo = $this->create_photo_with_image($event);
        $this->create_face($event_photo, 'face-antiga');
        $this->fake_rekognition([
            ['FaceRecords' => [$this->face_record('face-nova', 98.0)]],
            [],
        ]);

        app(FaceRecognitionService::class)->index_photo($event_photo);

        $this->assertSame(['IndexFaces', 'DeleteFaces'], array_column($this->rekognition_calls, 0));
        $this->assertSame(['face-antiga'], $this->rekognition_calls[1][1]['FaceIds']);
        $this->assertSame(['face-nova'], $event_photo->faces()->pluck('provider_face_id')->all());
    }

    public function test_rekognition_search_returns_best_score_per_ready_photo_only(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $ready_photo = EventPhoto::factory()->create(['event_id' => $event->id, 'status' => 'ready']);
        $processing_photo = EventPhoto::factory()->create(['event_id' => $event->id, 'status' => 'processing']);
        $this->create_face($ready_photo, 'face-1');
        $this->create_face($ready_photo, 'face-2');
        $this->create_face($processing_photo, 'face-3');
        $this->fake_rekognition([
            ['FaceMatches' => [
                $this->face_match('face-3', 99.0),
                $this->face_match('face-1', 95.5),
                $this->face_match('face-2', 91.0),
                $this->face_match('face-de-outro-evento', 97.0),
            ]],
        ]);

        $this->search_selfie($event)->assertCount('results', 1);

        $this->assertSame(90.0, $this->rekognition_calls[0][1]['FaceMatchThreshold']);
        $this->assertSame(
            [['event_photo_id' => $ready_photo->id, 'score' => 0.955]],
            FaceSearch::query()->where('event_id', $event->id)->firstOrFail()->results,
        );
    }

    public function test_selfie_without_face_shows_friendly_error(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $this->fake_rekognition(['InvalidParameterException']);

        $this->search_selfie($event)->assertHasErrors('selfie');

        $this->assertDatabaseHas('face_searches', ['event_id' => $event->id, 'status' => 'failed']);
    }

    public function test_provider_failure_marks_search_as_failed(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $this->fake_rekognition(['ThrottlingException']);

        $this->search_selfie($event)->assertHasErrors('selfie');

        $this->assertDatabaseHas('face_searches', ['event_id' => $event->id, 'status' => 'failed']);
    }

    public function test_mock_driver_is_blocked_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);

        app(FaceRecognitionManager::class)->driver();
    }

    public function test_event_deletion_removes_provider_collection(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $this->fake_rekognition([[]]);

        $this->actingAs($photographer)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'));

        $this->assertSame([['DeleteCollection', ['CollectionId' => "fotx-testing-event-{$event->id}"]]], $this->rekognition_calls);
    }

    public function test_photo_deletion_removes_faces_from_provider(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);
        $this->create_face($event_photo, 'face-removida');
        $this->fake_rekognition([[]]);

        Livewire::actingAs($photographer)
            ->test(EventPhotoUploader::class, ['event' => $event])
            ->call('delete_photo', $event_photo->id);

        $this->assertSame('DeleteFaces', $this->rekognition_calls[0][0]);
        $this->assertSame(['face-removida'], $this->rekognition_calls[0][1]['FaceIds']);
        $this->assertDatabaseMissing('photo_faces', ['provider_face_id' => 'face-removida']);
    }

    public function test_index_faces_command_queues_ready_photos(): void
    {
        Queue::fake();
        $event = Event::factory()->create();
        EventPhoto::factory()->count(2)->create(['event_id' => $event->id, 'status' => 'ready']);
        EventPhoto::factory()->create(['event_id' => $event->id, 'status' => 'uploaded']);

        $this->artisan('fotx:index-faces', ['event' => $event->slug])->assertSuccessful();

        Queue::assertPushed(IndexPhotoFacesJob::class, 2);
    }

    private function search_selfie(Event $event): mixed
    {
        return Livewire::test(SelfieSearch::class, ['event' => $event])
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg', 600, 600))
            ->set('consent_accepted', true)
            ->call('search');
    }

    private function create_photo_with_image(Event $event): EventPhoto
    {
        $event_photo = EventPhoto::factory()->create([
            'event_id' => $event->id,
            'original_path' => "events/{$event->id}/originals/foto.jpg",
        ]);
        Storage::disk('local')->put($event_photo->original_path, UploadedFile::fake()->image('foto.jpg', 800, 600)->getContent());

        return $event_photo;
    }

    private function create_face(EventPhoto $event_photo, string $provider_face_id): PhotoFace
    {
        return PhotoFace::query()->create([
            'event_id' => $event_photo->event_id,
            'event_photo_id' => $event_photo->id,
            'provider_face_id' => $provider_face_id,
        ]);
    }

    private function face_record(string $face_id, float $confidence): array
    {
        return ['Face' => [
            'FaceId' => $face_id,
            'Confidence' => $confidence,
            'BoundingBox' => ['Left' => 0.1, 'Top' => 0.2, 'Width' => 0.3, 'Height' => 0.4],
        ]];
    }

    private function face_match(string $face_id, float $similarity): array
    {
        return ['Similarity' => $similarity, 'Face' => ['FaceId' => $face_id]];
    }

    /**
     * Cada item é o resultado de uma chamada, em ordem: array vira resposta
     * de sucesso e string vira exceção com esse código de erro da AWS.
     */
    private function fake_rekognition(array $responses): void
    {
        config([
            'fotx.face_recognition_driver' => 'rekognition',
            'fotx.rekognition_collection_prefix' => 'fotx-testing',
        ]);

        $mock_handler = new MockHandler;

        foreach ($responses as $response) {
            $mock_handler->append(function (CommandInterface $command) use ($response): Result|RekognitionException {
                $this->rekognition_calls[] = [$command->getName(), collect($command->toArray())->except(['@http', '@context', 'Image'])->all()];

                return is_string($response)
                    ? new RekognitionException('Erro simulado', $command, ['code' => $response])
                    : new Result($response);
            });
        }

        $this->app->instance(RekognitionClient::class, new RekognitionClient([
            'version' => '2016-06-27',
            'region' => 'sa-east-1',
            'credentials' => ['key' => 'chave-teste', 'secret' => 'segredo-teste'],
            'handler' => $mock_handler,
        ]));
    }
}
