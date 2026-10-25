<?php

namespace Tests\Feature\Fotx;

use App\Livewire\Photographer\EventForm;
use App\Livewire\Public\EventCatalog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_published_events_that_opted_in(): void
    {
        Event::factory()->create(['name' => 'Corrida Listada', 'status' => 'published', 'show_in_catalog' => true]);
        Event::factory()->create(['name' => 'Casamento Privado', 'status' => 'published', 'show_in_catalog' => false]);
        Event::factory()->create(['name' => 'Rascunho Listado', 'status' => 'draft', 'show_in_catalog' => true]);
        Event::factory()->create(['name' => 'Arquivado Listado', 'status' => 'archived', 'show_in_catalog' => true]);

        $this->get(route('public.events.index'))
            ->assertOk()
            ->assertSeeLivewire(EventCatalog::class)
            ->assertSee('Corrida Listada')
            ->assertDontSee('Casamento Privado')
            ->assertDontSee('Rascunho Listado')
            ->assertDontSee('Arquivado Listado');
    }

    public function test_catalog_searches_by_name_and_location_from_query_string(): void
    {
        Event::factory()->create(['name' => 'Corrida da Serra', 'location' => 'Gramado', 'show_in_catalog' => true]);
        Event::factory()->create(['name' => 'Formatura Medicina', 'location' => 'Curitiba', 'show_in_catalog' => true]);

        $this->get(route('public.events.index', ['q' => 'serra']))
            ->assertOk()
            ->assertSee('Corrida da Serra')
            ->assertDontSee('Formatura Medicina');

        Livewire::test(EventCatalog::class)
            ->set('search', 'curitiba')
            ->assertSee('Formatura Medicina')
            ->assertDontSee('Corrida da Serra');
    }

    public function test_catalog_search_treats_like_wildcards_as_text(): void
    {
        Event::factory()->create(['name' => 'Corrida da Serra', 'show_in_catalog' => true]);

        Livewire::test(EventCatalog::class)
            ->set('search', '%')
            ->assertDontSee('Corrida da Serra')
            ->assertSee('Nenhum evento encontrado');
    }

    public function test_photographer_can_list_event_in_catalog(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);

        Livewire::actingAs($photographer)
            ->test(EventForm::class)
            ->set('name', 'Corrida Vitrine')
            ->set('slug', 'corrida-vitrine')
            ->set('price_per_photo', '19.90')
            ->set('show_in_catalog', true)
            ->call('save');

        $this->assertDatabaseHas('events', ['slug' => 'corrida-vitrine', 'show_in_catalog' => true]);
    }

    public function test_new_events_stay_out_of_catalog_by_default(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);

        Livewire::actingAs($photographer)
            ->test(EventForm::class)
            ->set('name', 'Casamento Ana e Bia')
            ->set('slug', 'casamento-ana-e-bia')
            ->set('price_per_photo', '30.00')
            ->set('status', 'published')
            ->call('save');

        $this->assertDatabaseHas('events', ['slug' => 'casamento-ana-e-bia', 'show_in_catalog' => false]);
    }
}
