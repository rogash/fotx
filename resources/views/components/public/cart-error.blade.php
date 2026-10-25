@error('cart')
    <div {{ $attributes->merge(['class' => 'mt-4 flex flex-col gap-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between']) }} role="alert">
        <p class="font-semibold">{{ $message }}</p>
        <a href="{{ route('checkout.show') }}" class="shrink-0 font-bold text-amber-950 underline underline-offset-2">Ver carrinho</a>
    </div>
@enderror
