@php $order_url = route('orders.downloads', [$order, $download_token]); @endphp
<div class="mt-6 rounded-2xl bg-amber-50 p-4 text-left ring-1 ring-amber-100">
    <p class="text-sm font-semibold text-amber-900">Guarde este link</p>
    <p class="mt-1 text-sm text-amber-800">É por ele que você acessa os downloads do pedido. Salve nos favoritos ou copie e mande para você mesmo.</p>
    <div class="mt-3 flex gap-2">
        <input type="text" readonly value="{{ $order_url }}" class="fotx-input min-w-0 flex-1 bg-white px-3 py-2 text-xs text-slate-600" onclick="this.select()" aria-label="Link do pedido">
        <button type="button" class="fotx-button-secondary shrink-0 px-4 py-2" onclick="navigator.clipboard.writeText('{{ $order_url }}').then(() => { this.textContent = 'Copiado'; })">Copiar</button>
    </div>
</div>
