<?php

namespace Tests\Feature\Fotx;

use App\Livewire\Admin\PhotographerRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PhotographerSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_photographer_signup_screen_can_be_rendered(): void
    {
        $this->get(route('register.photographer'))
            ->assertOk()
            ->assertSeeVolt('pages.auth.register-photographer');
    }

    public function test_photographer_signup_creates_pending_customer_without_photographer_access(): void
    {
        Volt::test('pages.auth.register-photographer')
            ->set('name', 'Fotógrafa Nova')
            ->set('email', 'nova@fotx.test')
            ->set('portfolio', '@fotografanova')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'nova@fotx.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role);
        $this->assertTrue($user->has_pending_photographer_request());
        $this->assertSame('@fotografanova', $user->photographer_portfolio);

        $this->get(route('events.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertSee('Cadastro de fotógrafo em análise');
    }

    public function test_customer_can_request_photographer_access(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->post(route('photographer.request'), ['portfolio' => 'meusite.com.br'])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($customer->refresh()->has_pending_photographer_request());
    }

    public function test_admin_approves_photographer_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pending_user = User::factory()->create(['role' => 'customer']);
        $pending_user->request_photographer_access();

        $this->actingAs($admin)->get(route('admin.photographers'))
            ->assertOk()
            ->assertSee($pending_user->email);

        Livewire::actingAs($admin)
            ->test(PhotographerRequests::class)
            ->call('approve', $pending_user->id)
            ->assertDontSee($pending_user->email);

        $pending_user->refresh();
        $this->assertSame('photographer', $pending_user->role);
        $this->assertNull($pending_user->photographer_requested_at);

        $this->actingAs($pending_user)->get(route('events.index'))->assertOk();
    }

    public function test_admin_rejects_photographer_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pending_user = User::factory()->create(['role' => 'customer']);
        $pending_user->request_photographer_access();

        Livewire::actingAs($admin)
            ->test(PhotographerRequests::class)
            ->call('reject', $pending_user->id);

        $pending_user->refresh();
        $this->assertSame('customer', $pending_user->role);
        $this->assertFalse($pending_user->has_pending_photographer_request());
    }

    public function test_only_admin_reviews_photographer_requests(): void
    {
        $photographer = User::factory()->create(['role' => 'photographer']);
        $pending_user = User::factory()->create(['role' => 'customer']);
        $pending_user->request_photographer_access();

        $this->actingAs($photographer)->get(route('admin.photographers'))->assertForbidden();

        Livewire::actingAs($photographer)
            ->test(PhotographerRequests::class)
            ->assertForbidden();

        $this->assertSame('customer', $pending_user->refresh()->role);
    }
}
