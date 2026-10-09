@props(['code', 'title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>{{ $title }} | Fotx</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-[#f5f5f7] font-sans text-slate-900 antialiased">
        <main class="flex min-h-screen flex-col items-center justify-center px-4 py-10 text-center">
            <a href="https://fotx.com.br" class="inline-flex">
                <x-brand.logo class="h-10 w-auto" />
            </a>

            <p class="mt-12 text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Erro {{ $code }}</p>
            <h1 class="mt-3 max-w-xl text-3xl font-semibold text-slate-950 sm:text-4xl">{{ $title }}</h1>
            <p class="mt-4 max-w-md text-base leading-7 text-slate-500">{{ $slot }}</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('dashboard') }}" class="fotx-button-primary">Ir para o painel</a>
                <a href="https://fotx.com.br/para-voce.html" class="fotx-button-secondary">Procurar minhas fotos</a>
            </div>
        </main>
    </body>
</html>
