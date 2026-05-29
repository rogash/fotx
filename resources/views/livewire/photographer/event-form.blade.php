<form wire:submit="save" class="fotx-card space-y-6 p-6">
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="text-sm font-medium text-slate-700">Nome</label>
            <input wire:model.live="name" class="fotx-input mt-2 w-full" />
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Slug</label>
            <input wire:model="slug" class="fotx-input mt-2 w-full" />
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Data</label>
            <input type="date" wire:model="event_date" class="fotx-input mt-2 w-full" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Local</label>
            <input wire:model="location" class="fotx-input mt-2 w-full" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Preço por foto</label>
            <input type="number" step="0.01" wire:model="price_per_photo" class="fotx-input mt-2 w-full" />
            @error('price_per_photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Status</label>
            <select wire:model="status" class="fotx-input mt-2 w-full">
                <option value="draft">Rascunho</option>
                <option value="published">Publicado</option>
                <option value="archived">Arquivado</option>
            </select>
        </div>
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Descrição</label>
        <textarea wire:model="description" rows="5" class="fotx-input mt-2 w-full"></textarea>
    </div>
    <div class="flex justify-end gap-3">
        <a href="{{ route('events.index') }}" class="fotx-button-secondary">Cancelar</a>
        <button class="fotx-button-primary">Salvar evento</button>
    </div>
</form>
