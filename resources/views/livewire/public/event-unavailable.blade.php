<main class="min-h-screen bg-slate-50">
    <section class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200 sm:p-10">
            <a href="/" class="inline-flex justify-center">
                <x-brand.logo class="h-12 w-auto" />
            </a>

            <p class="mt-8 text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Evento indisponível</p>
            <h1 class="mt-3 text-3xl font-black text-slate-950 sm:text-4xl">Esta galeria ainda não está publicada.</h1>
            <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-600">
                O evento {{ $event->name }} existe no Fotx, mas o fotógrafo ainda não liberou o acesso público ou arquivou temporariamente a galeria.
            </p>

            <div class="mt-8 rounded-2xl bg-slate-50 p-5 text-left">
                <p class="text-sm font-semibold text-slate-900">O que fazer agora?</p>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                    <li>Confira se o link foi digitado corretamente.</li>
                    <li>Aguarde o fotógrafo publicar a galeria.</li>
                    <li>Se recebeu este link por QR Code, tente novamente mais tarde.</li>
                </ul>
            </div>

            @if (filled(config('fotx.whatsapp_number')))
                <a
                    href="{{ route('tracking.events.whatsapp', $event->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="mt-8 inline-flex rounded-2xl bg-slate-950 px-6 py-4 text-sm font-bold text-white"
                >
                    Falar no WhatsApp
                </a>
            @endif
        </div>
    </section>
</main>
