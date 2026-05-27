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
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Area do cliente')
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
            ->call('upload');

        $this->assertDatabaseHas('event_photos', [
            'event_id' => $event->id,
            'filename' => 'foto.jpg',
        ]);
    }

    public function test_public_event_opens(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get(route('public.events.show', $event->slug))
            ->assertOk()
            ->assertSee('Encontre suas fotos em segundos');
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
    }

    public function test_public_results_can_add_and_remove_photo_from_cart(): void
    {
        $event = Event::factory()->create();
        $event_photo = EventPhoto::factory()->create(['event_id' => $event->id]);

        Livewire::test(SelfieSearch::class, ['event' => $event])
            ->call('add_to_cart', $event_photo->id)
            ->call('remove_from_cart', $event_photo->id);

        $this->assertSame(0, app(CartService::class)->count());
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

        $this->get(route('orders.download', [$order, $order->download_token, $blocked_photo]))->assertForbidden();
    }

    public function test_download_blocks_invalid_token(): void
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

        $this->get(route('orders.download', [$order, 'token-invalido', $event_photo]))->assertForbidden();
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
            ->assertSee('Copiar link publico')
            ->assertSee($event->public_url());
    }
}
