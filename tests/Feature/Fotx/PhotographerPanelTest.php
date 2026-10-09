<?php

namespace Tests\Feature\Fotx;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotographerPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_photographer_sees_onboarding_steps(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);

        $this->actingAs($photographer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Primeiros passos')
            ->assertSee('0 de 4 concluídos')
            ->assertSee('Criar primeiro evento');
    }

    public function test_onboarding_tracks_progress_and_lists_recent_activity(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id, 'name' => 'Corrida da Serra', 'status' => 'draft']);
        EventPhoto::factory()->create(['event_id' => $event->id]);

        $this->actingAs($photographer)
            ->get(route('dashboard'))
            ->assertSee('2 de 4 concluídos')
            ->assertSee('Corrida da Serra')
            ->assertSee('Rascunho')
            ->assertDontSee('>draft<', false);
    }

    public function test_onboarding_disappears_after_first_sale(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id, 'name' => 'Formatura 2026']);
        EventPhoto::factory()->create(['event_id' => $event->id]);
        Order::query()->create([
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'total_amount' => '29.90',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($photographer)
            ->get(route('dashboard'))
            ->assertDontSee('Primeiros passos')
            ->assertSee('Últimas vendas')
            ->assertSee('R$ 29,90');
    }

    public function test_admin_dashboard_does_not_show_onboarding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Primeiros passos');
    }

    public function test_statuses_and_roles_are_shown_in_portuguese(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $event = Event::factory()->create(['user_id' => $photographer->id, 'status' => 'published']);
        Order::query()->create([
            'event_id' => $event->id,
            'buyer_email' => 'cliente@fotx.test',
            'total_amount' => '19.90',
            'status' => 'pending',
        ]);

        $this->actingAs($photographer)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Publicado')
            ->assertSee('Responsável')
            ->assertSee('Aguardando pagamento')
            ->assertDontSee('>owner<', false)
            ->assertDontSee('>pending<', false);

        $this->actingAs($photographer)
            ->get(route('events.orders', $event))
            ->assertSee('Aguardando pagamento');
    }

    public function test_not_found_page_uses_fotx_layout(): void
    {
        $this->get('/pagina-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Não encontramos esta página')
            ->assertSee('Ir para o painel');
    }

    public function test_forbidden_page_explains_pending_access(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('events.index'))
            ->assertForbidden()
            ->assertSee('Você não tem acesso a esta página');
    }
}
