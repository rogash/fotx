<?php

namespace Tests\Feature\Fotx;

use App\Livewire\Photographer\EventForm;
use App\Livewire\Photographer\EventPhotoUploader;
use App\Livewire\Public\Checkout;
use App\Livewire\Public\SelfieSearch;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\FaceSearch;
use App\Models\Order;
use App\Models\PhotoBatch;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class FotxMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_photographer_creates_event(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);

        Livewire::actingAs($photographer)
            ->test(EventForm::class)
            ->set('name', 'Corrida Fotx')
            ->set('slug', 'corrida-fotx')
            ->set('price_per_photo', '19.90')
            ->set('status', 'published')
            ->call('save');

        $this->assertDatabaseHas('events', [
            'name' => 'Corrida Fotx',
            'slug' => 'corrida-fotx',
            'user_id' => $photographer->id,
        ]);
    }

    public function test_customer_dashboard_does_not_show_photographer_actions(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Minhas fotos')
            ->assertSee('Área do cliente')
            ->assertDontSee('Novo evento')
            ->assertDontSee('Fotos cadastradas');
    }

    public function test_customer_cannot_access_photographer_events_area(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('events.index'))
            ->assertForbidden();
    }

    public function test_photo_upload_creates_record(): void
    {
        Storage::fake('local');

        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);

        Livewire::actingAs($photographer)
            ->test(EventPhotoUploader::class, ['event' => $event])
            ->set('photos', [UploadedFile::fake()->image('foto.jpg', 900, 600)->size(600)])
            ->call('upload_photos');

        $this->assertDatabaseHas('event_photos', [
            'event_id' => $event->id,
            'uploaded_by' => $photographer->id,
            'photographer_id' => $photographer->id,
            'filename' => 'foto.jpg',
        ]);

        $this->assertDatabaseHas('photo_batches', [
            'event_id' => $event->id,
            'uploaded_by' => $photographer->id,
            'total_files' => 1,
        ]);
    }

    public function test_public_event_opens(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get(route('public.events.show', $event->slug))
            ->assertOk()
            ->assertSee('Encontre suas fotos em segundos')
            ->assertSee('Falar no WhatsApp');

        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'type' => 'event_view',
            'source' => 'direct',
        ]);
    }

    public function test_public_event_opened_from_qr_records_qr_metrics(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get(route('public.events.show', [$event->slug, 'via' => 'qr']))
            ->assertOk();

        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'type' => 'event_view',
            'source' => 'qr',
        ]);

        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'type' => 'qr_view',
            'source' => 'qr',
        ]);
    }

    public function test_unpublished_public_event_shows_friendly_unavailable_page(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);

        $this->get(route('public.events.show', $event->slug))
            ->assertOk()
            ->assertSee('Evento indisponível')
            ->assertSee('Esta galeria ainda não está publicada.');

        $this->assertDatabaseMissing('event_analytics', [
            'event_id' => $event->id,
            'type' => 'event_view',
        ]);
    }

    public function test_selfie_search_creates_face_search(): void
    {
        Storage::fake('local');

        $event = Event::factory()->create(['status' => 'published']);
        EventPhoto::factory()->count(3)->create(['event_id' => $event->id, 'status' => 'ready']);

        Livewire::test(SelfieSearch::class, ['event' => $event])
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg', 600, 600)->size(500))
            ->set('consent_accepted', true)
            ->call('search')
            ->assertSet('consent_accepted', true);

        $this->assertDatabaseHas('face_searches', [
            'event_id' => $event->id,
            'status' => 'done',
            'consent_accepted' => true,
        ]);

        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'type' => 'selfie_search',
            'source' => 'public_event',
        ]);

        $this->assertNotNull(FaceSearch::query()->where('event_id', $event->id)->value('expires_at'));
    }

    public function test_face_search_rate_limit_blocks_repeated_searches(): void
    {
        Storage::fake('local');
        config(['fotx.face_search_max_attempts' => 1]);

        $event = Event::factory()->create(['status' => 'published']);
        EventPhoto::factory()->count(3)->create(['event_id' => $event->id, 'status' => 'ready']);

        Livewire::test(SelfieSearch::class, ['event' => $event])
            ->set('selfie', UploadedFile::fake()->image('selfie-1.jpg', 600, 600)->size(500))
            ->set('consent_accepted', true)
            ->call('search')
            ->set('selfie', UploadedFile::fake()->image('selfie-2.jpg', 600, 600)->size(500))
            ->call('search')
            ->assertHasErrors('selfie');

        $this->assertSame(1, FaceSearch::query()->where('event_id', $event->id)->count());
    }

    public function test_expired_selfie_purge_removes_files_and_rows(): void
    {
        Storage::fake('local');

        $event = Event::factory()->create();
        Storage::disk('local')->put('events/1/selfies/expired.jpg', 'selfie');
        $face_search = FaceSearch::query()->create([
            'event_id' => $event->id,
            'selfie_path' => 'events/1/selfies/expired.jpg',
            'status' => 'done',
            'consent_accepted' => true,
            'expires_at' => now()->subMinute(),
        ]);

        Artisan::call('fotx:purge-expired-selfies');

        Storage::disk('local')->assertMissing('events/1/selfies/expired.jpg');
        $this->assertDatabaseMissing('face_searches', ['id' => $face_search->id]);
    }

    public function test_checkout_creates_pending_order(): void
    {
        $event = Event::factory()->create(['price_per_photo' => 25.00]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);

        app(CartService::class)->add_photo($event_photo->load('event'));

        Livewire::test(Checkout::class)
            ->set('buyer_name', 'Cliente Fotx')
            ->set('buyer_email', 'cliente@fotx.test')
            ->call('start_payment');

        $this->assertDatabaseHas('orders', [
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'status' => 'pending',
            'payment_provider' => 'mock',
        ]);

        $order = Order::query()->where('buyer_email', 'cliente@fotx.test')->firstOrFail();

        $this->assertNotNull($order->public_id);
        $this->assertNotNull($order->download_token);
        $this->assertNotNull($order->payment_reference);
        $this->assertNotNull($order->payment_checkout_url);
    }

    public function test_mock_payment_approval_marks_order_as_paid(): void
    {
        $event = Event::factory()->create();
        $order = Order::query()->create([
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'total_amount' => 20,
            'status' => 'pending',
            'payment_provider' => 'mock',
            'payment_reference' => 'MOCK-PREF-123',
        ]);

        $this->post(route('payments.mock.approve', [$order, $order->download_token]))
            ->assertRedirect(route('orders.success', [$order, $order->download_token]));

        $this->assertSame('paid', $order->refresh()->status);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'order_id' => $order->id,
            'type' => 'paid_order',
            'source' => 'mock',
        ]);
    }

    public function test_public_results_can_add_and_remove_photo_from_cart(): void
    {
        $event = Event::factory()->create();
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);

        Livewire::test(SelfieSearch::class, ['event' => $event])
            ->call('add_to_cart', $event_photo->public_id)
            ->call('remove_from_cart', $event_photo->public_id);

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_photographer_can_save_photo_search_metadata(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);

        Livewire::actingAs($photographer)
            ->test(EventPhotoUploader::class, ['event' => $event])
            ->set("metadata.{$event_photo->id}.participant_code", '3087')
            ->set("metadata.{$event_photo->id}.search_keywords", 'Ana Silva Equipe Fotx')
            ->call('save_metadata', $event_photo->id);

        $this->assertDatabaseHas('event_photos', [
            'id' => $event_photo->id,
            'participant_code' => '3087',
            'search_keywords' => 'Ana Silva Equipe Fotx',
        ]);
    }

    public function test_photographer_can_import_photo_search_metadata_csv(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create([
            'event_id' => $event->id,
            'filename' => 'IMG_1001.jpg',
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'metadados.csv',
            "foto;numero;nome;equipe\nIMG_1001.jpg;3087;Ana Silva;Equipe Fotx\nnao-existe.jpg;9999;Linha Ignorada;Equipe X\n"
        );

        Livewire::actingAs($photographer)
            ->test(EventPhotoUploader::class, ['event' => $event])
            ->set('metadata_csv', $csv)
            ->call('import_metadata_csv')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_photos', [
            'id' => $event_photo->id,
            'participant_code' => '3087',
            'search_keywords' => 'Ana Silva Equipe Fotx',
        ]);
    }

    public function test_public_text_search_finds_photo_by_participant_code(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        EventPhoto::factory()->create([
            'event_id' => $event->id,
            'participant_code' => '3087',
            'search_keywords' => 'Ana Silva Equipe Fotx',
        ]);

        Livewire::test(SelfieSearch::class, ['event' => $event])
            ->set('participant_query', '3087')
            ->call('search_by_text')
            ->assertSet('has_searched', true)
            ->assertSet('result_source', 'text')
            ->assertSee('100% compatível');

        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'type' => 'text_search',
            'source' => 'public_event',
        ]);
    }

    public function test_cart_applies_volume_discount(): void
    {
        $event = Event::factory()->create(['price_per_photo' => 25.00]);
        EventPhoto::factory()->count(3)->create(['event_id' => $event->id])
            ->each(fn (EventPhoto $event_photo) => app(CartService::class)->add_photo($event_photo->load('event')));

        $summary = app(CartService::class)->summary();

        $this->assertSame(75.0, $summary['subtotal']);
        $this->assertSame(0.15, $summary['discount_percent']);
        $this->assertSame(11.25, $summary['discount_amount']);
        $this->assertSame(63.75, $summary['total']);
    }

    public function test_checkout_creates_order_with_discounted_total(): void
    {
        $event = Event::factory()->create(['price_per_photo' => 25.00]);
        EventPhoto::factory()->count(3)->create(['event_id' => $event->id])
            ->each(fn (EventPhoto $event_photo) => app(CartService::class)->add_photo($event_photo->load('event')));

        Livewire::test(Checkout::class)
            ->set('buyer_email', 'desconto@fotx.test')
            ->call('start_payment');

        $this->assertDatabaseHas('orders', [
            'buyer_email' => 'desconto@fotx.test',
            'total_amount' => 63.75,
            'status' => 'pending',
        ]);
    }

    public function test_public_photo_detail_opens(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $event_photo = EventPhoto::factory()->create([
            'event_id' => $event->id,
            'participant_code' => '3087',
        ]);

        $this->get(route('public.photos.show', [$event->slug, $event_photo]))
            ->assertOk()
            ->assertSee('Foto do evento')
            ->assertSee('3087')
            ->assertSee('Adicionar ao carrinho')
            ->assertSee('Compartilhar no WhatsApp');
    }

    public function test_whatsapp_tracking_redirects_and_records_click(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);

        $response = $this->get(route('tracking.events.whatsapp', [$event->slug, 'photo' => $event_photo->public_id]))
            ->assertRedirect();

        $this->assertStringStartsWith('https://wa.me/', $response->headers->get('Location'));
        $this->assertDatabaseHas('event_analytics', [
            'event_id' => $event->id,
            'event_photo_id' => $event_photo->id,
            'type' => 'whatsapp_click',
            'source' => 'photo',
        ]);
    }

    public function test_download_blocks_photo_not_purchased(): void
    {
        $event = Event::factory()->create();
        $purchased_photo = EventPhoto::factory()->create(['event_id' => $event->id]);
        $blocked_photo = EventPhoto::factory()->create(['event_id' => $event->id]);
        $order = Order::query()->create([
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'total_amount' => 20,
            'status' => 'paid',
        ]);
        $order->items()->create(['event_photo_id' => $purchased_photo->id, 'price' => 20]);

        $download_url = URL::temporarySignedRoute('orders.download', now()->addMinutes(15), [$order, $blocked_photo]);

        $this->get($download_url)->assertForbidden();
    }

    public function test_download_blocks_unsigned_url(): void
    {
        $event = Event::factory()->create();
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);
        $order = Order::query()->create([
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'total_amount' => 20,
            'status' => 'paid',
        ]);
        $order->items()->create(['event_photo_id' => $event_photo->id, 'price' => 20]);

        $this->get(route('orders.download', [$order, $event_photo]))->assertForbidden();
    }

    public function test_photographer_can_set_cover_photo(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id, 'status' => 'ready']);

        Livewire::actingAs($photographer)
            ->test(EventPhotoUploader::class, ['event' => $event])
            ->call('set_cover', $event_photo->id);

        $this->assertSame($event_photo->id, $event->refresh()->cover_photo_id);
    }

    public function test_photographer_can_view_event_orders(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);
        $order = Order::query()->create([
            'event_id' => $event->id,
            'buyer_name' => 'Cliente Pedido',
            'buyer_email' => 'comprador@fotx.test',
            'total_amount' => 35,
            'status' => 'paid',
        ]);
        $order->items()->create(['event_photo_id' => $event_photo->id, 'price' => 35]);

        $this->actingAs($photographer)
            ->get(route('events.orders', $event))
            ->assertOk()
            ->assertSee('comprador@fotx.test')
            ->assertSee('R$ 35,00');
    }

    public function test_photographer_can_view_order_detail(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id, 'filename' => 'vendida.jpg']);
        $order = Order::query()->create([
            'event_id' => $event->id,
            'buyer_name' => 'Cliente Detalhe',
            'buyer_email' => 'detalhe@fotx.test',
            'total_amount' => 42,
            'status' => 'paid',
            'payment_provider' => 'mock',
            'payment_reference' => 'MOCK-123',
        ]);
        $order->items()->create(['event_photo_id' => $event_photo->id, 'price' => 42]);

        $this->actingAs($photographer)
            ->get(route('events.orders.show', [$event, $order]))
            ->assertOk()
            ->assertSee('Cliente Detalhe')
            ->assertSee('vendida.jpg')
            ->assertSee('MOCK-123')
            ->assertSee(route('orders.downloads', [$order, $order->download_token]));
    }

    public function test_photographer_can_export_event_orders_csv(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);
        $order = Order::query()->create([
            'event_id' => $event->id,
            'buyer_name' => 'Cliente CSV',
            'buyer_email' => 'csv@fotx.test',
            'total_amount' => 58,
            'status' => 'paid',
        ]);
        $order->items()->create(['event_photo_id' => $event_photo->id, 'price' => 58]);

        $response = $this->actingAs($photographer)
            ->get(route('events.orders.export', $event))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('pedido_id', $content);
        $this->assertStringContainsString('csv@fotx.test', $content);
        $this->assertStringContainsString('58.00', $content);
    }

    public function test_event_show_has_copyable_public_link(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);

        $this->actingAs($photographer)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Copiar link público')
            ->assertSee('Baixar QR Code')
            ->assertSee('Abrir cartaz')
            ->assertSee('Metricas do evento')
            ->assertSee($event->public_url());
    }

    public function test_photographer_can_archive_event(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id, 'status' => 'published']);

        $this->actingAs($photographer)
            ->post(route('events.archive', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertSame('archived', $event->refresh()->status);
    }

    public function test_photographer_can_delete_event_without_orders(): void
    {
        Storage::fake('local');

        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        $event_photo = EventPhoto::factory()->create([
            'event_id' => $event->id,
            'original_path' => 'events/99/originals/photo.jpg',
            'thumbnail_path' => 'events/99/thumbnails/photo.jpg',
            'watermarked_path' => 'events/99/watermarked/photo.jpg',
        ]);
        Storage::disk('local')->put($event_photo->original_path, 'original');
        Storage::disk('local')->put($event_photo->thumbnail_path, 'thumbnail');
        Storage::disk('local')->put($event_photo->watermarked_path, 'watermarked');

        $this->actingAs($photographer)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        Storage::disk('local')->assertMissing($event_photo->original_path);
        Storage::disk('local')->assertMissing($event_photo->thumbnail_path);
        Storage::disk('local')->assertMissing($event_photo->watermarked_path);
    }

    public function test_event_with_orders_cannot_be_deleted(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id]);
        Order::query()->create([
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'total_amount' => 20,
            'status' => 'pending',
        ]);

        $this->actingAs($photographer)
            ->delete(route('events.destroy', $event))
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_event_owner_can_add_photographer_member(): void
    {
        $owner = User::factory()->create(['role' => 'photographer']);
        $member = User::factory()->create(['role' => 'photographer', 'email' => 'segundo@fotx.test']);
        $event = Event::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('events.members.store', $event), [
                'email' => $member->email,
                'role' => 'photographer',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('event_members', [
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => 'photographer',
        ]);

        $this->actingAs($member)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee($event->name);
    }

    public function test_event_photographer_member_can_upload_batch(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['role' => 'photographer']);
        $member = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $owner->id]);
        $event->members()->create(['user_id' => $member->id, 'role' => 'photographer']);

        Livewire::actingAs($member)
            ->test(EventPhotoUploader::class, ['event' => $event])
            ->set('photos', [UploadedFile::fake()->image('colaborador.jpg', 900, 600)->size(600)])
            ->call('upload_photos');

        $batch = PhotoBatch::query()->where('event_id', $event->id)->firstOrFail();

        $this->assertSame($member->id, $batch->uploaded_by);
        $this->assertDatabaseHas('event_photos', [
            'event_id' => $event->id,
            'photo_batch_id' => $batch->id,
            'uploaded_by' => $member->id,
            'photographer_id' => $member->id,
            'filename' => 'colaborador.jpg',
        ]);
    }

    public function test_photographer_can_view_and_download_event_qr_code(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id, 'status' => 'published']);

        $this->actingAs($photographer)
            ->get(route('events.qr-code', $event))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('<svg', false);

        $this->actingAs($photographer)
            ->get(route('events.qr-code', [$event, 'download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="fotx-qr-'.$event->slug.'.svg"');
    }

    public function test_photographer_can_open_event_poster(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id, 'status' => 'published']);

        $this->actingAs($photographer)
            ->get(route('events.poster', $event))
            ->assertOk()
            ->assertSee('Encontre suas fotos deste evento.')
            ->assertSee($event->public_url())
            ->assertSee(route('events.qr-code', $event));
    }
}
