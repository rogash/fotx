<form wire:submit="save" class="space-y-6">
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="text-sm font-medium text-slate-700">Nome</label>
            <input wire:model.live="name" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Slug</label>
            <input wire:model="slug" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Data</label>
            <input type="date" wire:model="event_date" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Local</label>
            <input wire:model="location" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Preço por foto</label>
            <input type="number" step="0.01" wire:model="price_per_photo" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
            @error('price_per_photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Status</label>
            <select wire:model="status" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                <option value="draft">Rascunho</option>
                <option value="published">Publicado</option>
                <option value="archived">Arquivado</option>
            </select>
        </div>
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Descricao</label>
        <textarea wire:model="description" rows="5" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"></textarea>
    </div>
    <div class="flex justify-end gap-3">
        <a href="{{ route('events.index') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Cancelar</a>
        <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Salvar evento</button>
    </div>
</form>
