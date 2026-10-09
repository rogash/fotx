@props(['type', 'status'])

@php
    // Os valores vêm dos enums das migrations; o mesmo código tem sentidos
    // diferentes por entidade (ex.: "pending" em pedido e em lote).
    $badges = [
        'event' => [
            'draft' => ['Rascunho', 'neutral'],
            'published' => ['Publicado', 'success'],
            'archived' => ['Arquivado', 'muted'],
        ],
        'order' => [
            'pending' => ['Aguardando pagamento', 'warning'],
            'paid' => ['Pago', 'success'],
            'cancelled' => ['Cancelado', 'danger'],
        ],
        'batch' => [
            'pending' => ['Na fila', 'neutral'],
            'uploading' => ['Enviando', 'info'],
            'processing' => ['Processando', 'info'],
            'done' => ['Concluído', 'success'],
            'failed' => ['Com falhas', 'danger'],
        ],
        'photo' => [
            'uploaded' => ['Enviada', 'neutral'],
            'processing' => ['Processando', 'info'],
            'ready' => ['Pronta', 'success'],
            'failed' => ['Falhou', 'danger'],
        ],
        'member' => [
            'owner' => ['Responsável', 'neutral'],
            'photographer' => ['Fotógrafo', 'neutral'],
            'assistant' => ['Assistente', 'neutral'],
            'viewer' => ['Visualizador', 'neutral'],
        ],
    ];

    [$label, $tone] = $badges[$type][$status] ?? [$status, 'neutral'];

    $tone_classes = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'muted' => 'bg-slate-100 text-slate-500',
        'info' => 'bg-sky-50 text-sky-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-red-50 text-red-700',
    ][$tone];
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold', $tone_classes]) }}>{{ $label }}</span>
