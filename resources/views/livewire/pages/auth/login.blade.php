<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl font-bold text-slate-950">Entrar no Fotx</h1>
    <p class="mt-1 text-sm text-slate-500">Acesse seus eventos ou suas compras.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-2 block w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-slate-500 hover:text-slate-950" href="{{ route('password.request') }}" wire:navigate>{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <x-text-input wire:model="form.password" id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" name="remember">
            {{ __('Remember me') }}
        </label>

        <x-primary-button class="w-full py-3">{{ __('Log in') }}</x-primary-button>
    </form>

    <div class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
        É fotógrafo e ainda não tem conta?
        <a href="{{ route('register.photographer') }}" class="font-semibold text-slate-950 hover:underline" wire:navigate>Cadastre-se</a>
    </div>
</div>
