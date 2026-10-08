<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f5f5f7">
        <title>{{ isset($title) ? $title.' | Fotx' : config('app.name', 'Fotx') }}</title>
        @isset($description)
            <meta name="description" content="{{ $description }}">
            <meta property="og:description" content="{{ $description }}">
        @endisset
        <meta property="og:title" content="{{ $title ?? config('app.name', 'Fotx') }}">
        <meta property="og:type" content="website">
        @isset($og_image)
            <meta property="og:image" content="{{ $og_image }}">
            <meta name="twitter:card" content="summary_large_image">
        @endisset
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f5f5f7] font-sans text-slate-900 antialiased">
        {{ $slot }}
    </body>
</html>
