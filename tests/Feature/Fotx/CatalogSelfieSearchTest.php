<?php

namespace Tests\Feature\Fotx;

use App\Livewire\Public\CatalogSelfieSearch;
use App\Livewire\Public\PhotoDetail;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Models\PhotoFace;
use App\Services\CartService;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\Rekognition\RekognitionClient;
use Aws\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogSelfieSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $searched_collections = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_page_explains_that_selfie_is_compared_with_catalog_events(): void
    {
        $this->get(route('public.catalog.search'))
            ->assertOk()
            ->assertSeeLivewire(CatalogSelfieSearch::class)
            ->assertSee('todos os eventos da vitrine do Fotx')
            ->assertSee('apagada automaticamente em até '.config('fotx.face_selfie_ttl_hours').' horas');
    }

    public function test_selfie_is_searched_only_in_catalog_events_and_grouped_by_event(): void
    {
        $corrida = $this->catalog_event('Corrida da Serra', '2026-10-20');
        $formatura = $this->catalog_event('Formatura Medicina', '2026-10-10');
        $privado = Event::factory()->create(['name' => 'Casamento Privado', 'status' => 'published', 'show_in_catalog' => false]);
        $rascunho = Event::factory()->create(['name' => 'Rascunho', 'status' => 'draft', 'show_in_catalog' => true]);

        $corrida_photo = $this->photo_with_face($corrida, 'face-corrida');
        $formatura_photo = $this->photo_with_face($formatura, 'face-formatura');
        $this->photo_with_face($privado, 'face-privado');
        $this->photo_with_face($rascunho, 'face-rascunho');

        $this->fake_rekognition([
            "fotx-testing-event-{$corrida->id}" => ['FaceMatches' => [$this->face_match('face-corrida', 93.0)]],
            "fotx-testing-event-{$formatura->id}" => ['FaceMatches' => [$this->face_match('face-formatura', 97.5)]],
        ]);

        $component = $this->search_selfie();

        $this->assertEqualsCanonicalizing(
            ["fotx-testing-event-{$corrida->id}", "fotx-testing-event-{$formatura->id}"],
            $this->searched_collections,
        );

        $groups = $component->get('results_by_event');
        $this->assertSame([$formatura->id, $corrida->id], array_map(fn (array $group): int => $group['event']->id, $groups));
        $this->assertSame($formatura_photo->id, $groups[0]['photos'][0]['photo']->id);
        $this->assertSame($corrida_photo->id, $groups[1]['photos'][0]['photo']->id);
        $component->assertSee('Você aparece em 2 eventos')->assertDontSee('Casamento Privado');

        $face_search = FaceSearch::query()->sole();
        $this->assertSame('catalog', $face_search->scope);
        $this->assertNull($face_search->event_id);
        $this->assertTrue($face_search->consent_accepted);
        $this->assertNotNull($face_search->expires_at);
        $this->assertSame([
            ['event_id' => $formatura->id, 'event_photo_id' => $formatura_photo->id, 'score' => 0.975],
            ['event_id' => $corrida->id, 'event_photo_id' => $corrida_photo->id, 'score' => 0.93],
        ], $face_search->results);
    }

    public function test_search_is_limited_to_most_recent_catalog_events(): void
    {
        config(['fotx.catalog_face_search_max_events' => 1]);
        $recente = $this->catalog_event('Evento Recente', '2026-10-20');
        $antigo = $this->catalog_event('Evento Antigo', '2026-01-10');
        $this->photo_with_face($recente, 'face-recente');
        $this->photo_with_face($antigo, 'face-antigo');
        $this->fake_rekognition(["fotx-testing-event-{$recente->id}" => ['FaceMatches' => []]]);

        $this->search_selfie()->assertSee('Procuramos em 1 evento');

        $this->assertSame(["fotx-testing-event-{$recente->id}"], $this->searched_collections);
    }

    public function test_selfie_without_face_shows_friendly_error(): void
    {
        $evento = $this->catalog_event('Corrida', '2026-10-20');
        $this->photo_with_face($evento, 'face-corrida');
        $this->fake_rekognition(["fotx-testing-event-{$evento->id}" => 'InvalidParameterException']);

        $this->search_selfie()->assertHasErrors('selfie')->assertSet('has_searched', false);

        $this->assertSame('failed', FaceSearch::query()->sole()->status);
    }

    public function test_without_catalog_events_the_selfie_is_not_stored(): void
    {
        $this->fake_rekognition([]);

        $this->search_selfie()
            ->assertHasNoErrors()
            ->assertSee('Ainda não há eventos com fotos na vitrine');

        $this->assertSame(0, FaceSearch::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('catalog/selfies'));
        $this->assertSame([], $this->searched_collections);
    }

    public function test_catalog_search_has_its_own_attempt_limit(): void
    {
        config(['fotx.catalog_face_search_max_attempts' => 1]);

        $this->search_selfie()->assertHasNoErrors();
        $this->search_selfie()->assertHasErrors('selfie');
    }

    public function test_cart_refuses_photo_from_another_event(): void
    {
        $corrida = $this->catalog_event('Corrida da Serra', '2026-10-20');
        $formatura = $this->catalog_event('Formatura Medicina', '2026-10-10');
        $corrida_photo = EventPhoto::factory()->create(['event_id' => $corrida->id]);
        $formatura_photo = EventPhoto::factory()->create(['event_id' => $formatura->id]);

        Livewire::test(CatalogSelfieSearch::class)
            ->call('add_to_cart', $corrida_photo->public_id)
            ->assertHasNoErrors()
            ->call('add_to_cart', $formatura_photo->public_id)
            ->assertHasErrors('cart')
            ->assertSee('Seu carrinho já tem fotos de Corrida da Serra');

        $this->assertSame([$corrida_photo->id], app(CartService::class)->get_items()->pluck('event_photo_id')->all());
    }

    public function test_photo_page_refuses_photo_from_another_event(): void
    {
        $corrida = $this->catalog_event('Corrida da Serra', '2026-10-20');
        $formatura = $this->catalog_event('Formatura Medicina', '2026-10-10');
        app(CartService::class)->add_photo(EventPhoto::factory()->create(['event_id' => $corrida->id])->load('event'));
        $formatura_photo = EventPhoto::factory()->create(['event_id' => $formatura->id]);

        Livewire::test(PhotoDetail::class, ['slug' => $formatura->slug, 'event_photo' => $formatura_photo])
            ->call('add_to_cart')
            ->assertHasErrors('cart');

        $this->assertSame(1, app(CartService::class)->count());
    }

    public function test_photo_from_event_outside_catalog_cannot_be_added_from_catalog_search(): void
    {
        $privado = Event::factory()->create(['status' => 'published', 'show_in_catalog' => false]);
        $photo = EventPhoto::factory()->create(['event_id' => $privado->id]);

        Livewire::test(CatalogSelfieSearch::class)
            ->call('add_to_cart', $photo->public_id)
            ->assertNotFound();

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_expired_catalog_searches_are_purged(): void
    {
        Storage::disk('local')->put('catalog/selfies/antiga.jpg', 'selfie');
        FaceSearch::query()->create([
            'scope' => 'catalog',
            'selfie_path' => 'catalog/selfies/antiga.jpg',
            'status' => 'done',
            'consent_accepted' => true,
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('fotx:purge-expired-selfies')->assertSuccessful();

        $this->assertSame(0, FaceSearch::query()->count());
        Storage::disk('local')->assertMissing('catalog/selfies/antiga.jpg');
    }

    private function search_selfie(): mixed
    {
        return Livewire::test(CatalogSelfieSearch::class)
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg', 600, 600))
            ->set('consent_accepted', true)
            ->call('search');
    }

    private function catalog_event(string $name, string $event_date): Event
    {
        return Event::factory()->create([
            'name' => $name,
            'event_date' => $event_date,
            'status' => 'published',
            'show_in_catalog' => true,
        ]);
    }

    private function photo_with_face(Event $event, string $provider_face_id): EventPhoto
    {
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id, 'status' => 'ready']);
        PhotoFace::query()->create([
            'event_id' => $event->id,
            'event_photo_id' => $event_photo->id,
            'provider_face_id' => $provider_face_id,
        ]);

        return $event_photo;
    }

    private function face_match(string $face_id, float $similarity): array
    {
        return ['Similarity' => $similarity, 'Face' => ['FaceId' => $face_id]];
    }

    /**
     * Responde cada SearchFacesByImage pela coleção consultada: array vira resposta
     * de sucesso e string vira exceção com esse código de erro da AWS.
     *
     * @param  array<string, array|string>  $responses_by_collection
     */
    private function fake_rekognition(array $responses_by_collection): void
    {
        config([
            'fotx.face_recognition_driver' => 'rekognition',
            'fotx.rekognition_collection_prefix' => 'fotx-testing',
        ]);

        $mock_handler = new MockHandler;

        foreach (range(1, 10) as $ignored) {
            $mock_handler->append(function (CommandInterface $command) use ($responses_by_collection): Result|RekognitionException {
                $collection_id = $command['CollectionId'];
                $this->searched_collections[] = $collection_id;
                $response = $responses_by_collection[$collection_id] ?? 'ResourceNotFoundException';

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
