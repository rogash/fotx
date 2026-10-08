<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $portfolio = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'portfolio' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // A conta nasce como cliente; o acesso de fotógrafo depende de aprovação.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);
        $user->request_photographer_access(filled($validated['portfolio']) ? $validated['portfolio'] : null);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <p class="text-sm font-semibold text-emerald-700">Para fotógrafos e equipes</p>
    <h1 class="mt-1 text-2xl font-bold text-slate-950">Venda suas fotos com o Fotx</h1>
    <p class="mt-1 text-sm text-slate-500">Crie sua conta. Nossa equipe confere o cadastro e libera o acesso para você criar eventos.</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="mt-2 block w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="mt-2 block w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="portfolio">Portfólio ou Instagram <span class="font-normal text-slate-400">(opcional)</span></x-input-label>
            <x-text-input wire:model="portfolio" id="portfolio" class="mt-2 block w-full" type="text" name="portfolio" placeholder="@seuperfil ou link do site" />
            <x-input-error :messages="$errors->get('portfolio')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full py-3">Criar conta de fotógrafo</x-primary-button>
    </form>

    <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
        {{ __('Already registered?') }} <a href="{{ route('login') }}" class="font-semibold text-slate-950 hover:underline" wire:navigate>Entrar</a>
    </p>
</div>
