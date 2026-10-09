@props(['number', 'title', 'done' => false, 'optional' => false, 'href' => null, 'action' => null, 'new_tab' => false])

<div @class([
    'rounded-2xl p-5 ring-1',
    'bg-emerald-50/60 ring-emerald-100' => $done,
    'bg-white shadow-sm ring-slate-200' => ! $done,
])>
    <div class="flex items-center gap-3">
        @if ($done)
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </span>
        @else
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-950 text-xs font-bold text-white">{{ $number }}</span>
        @endif
        <p class="text-sm font-semibold text-slate-950">
            {{ $title }}
            @if ($done)
                <span class="sr-only">(concluído)</span>
            @elseif ($optional)
                <span class="ml-1 text-xs font-medium text-slate-400">opcional</span>
            @endif
        </p>
    </div>
    <p class="mt-3 text-sm leading-6 text-slate-500">{{ $slot }}</p>
    @if ($href && $action)
        <a href="{{ $href }}" @if ($new_tab) target="_blank" @endif class="mt-4 inline-flex text-sm font-bold text-emerald-700 hover:text-emerald-800">{{ $action }}</a>
    @endif
</div>
