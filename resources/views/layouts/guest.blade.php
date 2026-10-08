<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' | Fotx' : config('app.name', 'Fotx') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f5f5f7] font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center px-4 py-10 sm:justify-center">
            <a href="https://fotx.com.br" class="inline-flex">
                <x-brand.logo class="h-10 w-auto" />
            </a>

            <div class="fotx-card mt-8 w-full max-w-md p-6 sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-8 text-xs text-slate-500">
                <a href="https://fotx.com.br/termos.html" class="hover:text-slate-800">Termos de uso</a>
                <span class="mx-2">·</span>
                <a href="https://fotx.com.br/privacidade.html" class="hover:text-slate-800">Privacidade</a>
            </p>
        </div>
    </body>
</html>
