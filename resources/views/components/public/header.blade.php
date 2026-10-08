@props(['event' => null])

<header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ $event ? route('public.events.show', $event->slug) : url('/') }}" class="flex min-w-0 items-center gap-3">
            <x-brand.logo class="h-8 w-auto shrink-0" />
            @if ($event)
                <span class="hidden truncate border-l border-slate-200 pl-3 text-sm font-semibold text-slate-600 sm:block">{{ $event->name }}</span>
            @endif
        </a>
        <livewire:public.cart-badge />
    </div>
</header>
