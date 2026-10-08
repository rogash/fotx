<div>
    @if (session('status'))
        <p class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">{{ session('status') }}</p>
    @endif

    <div class="fotx-card divide-y divide-slate-100">
        @forelse ($pending_users as $pending_user)
            <div wire:key="pedido-{{ $pending_user->id }}" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-950">{{ $pending_user->name }}</p>
                    <p class="text-sm text-slate-500">{{ $pending_user->email }}</p>
                    @if ($pending_user->photographer_portfolio)
                        <p class="mt-1 truncate text-sm text-slate-600">Portfólio: {{ $pending_user->photographer_portfolio }}</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-400">Pedido em {{ $pending_user->photographer_requested_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" wire:click="reject({{ $pending_user->id }})" wire:confirm="Recusar o pedido de {{ $pending_user->name }}?" class="fotx-button-secondary">Recusar</button>
                    <button type="button" wire:click="approve({{ $pending_user->id }})" wire:confirm="Liberar acesso de fotógrafo para {{ $pending_user->name }}?" class="fotx-button-primary">Aprovar</button>
                </div>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-slate-500">Nenhum pedido de fotógrafo aguardando aprovação.</p>
        @endforelse
    </div>
</div>
