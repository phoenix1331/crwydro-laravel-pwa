<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2f4c25">
    <meta name="description" content="Offline field journal for walkers in the Welsh hills">

    <title>{{ config('app.name') }}</title>

    <link rel="manifest" href="/build/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="48x48" href="/icons/favicon-48.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    <a href="#content" class="sr-only z-50 rounded-control bg-accent px-4 py-3 font-bold text-accent-ink focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
       @click.prevent="document.getElementById('content').focus()" x-data x-text="$t('skip')">Skip to content</a>
    <div class="min-h-dvh md:grid md:grid-cols-[15.5rem_minmax(0,1fr)]">
        @include('shell.sidebar')

        <div class="flex min-h-dvh min-w-0 flex-col">
            @include('shell.header')
            @include('shell.banners')

            <main id="content" tabindex="-1" class="flex-1 px-4 pt-5 pb-28 outline-none md:px-8 md:pt-8 md:pb-10 lg:px-10">
                <div x-data x-show="$store.router.is('entries', 'entry')"
                     class="mx-auto max-w-3xl lg:grid lg:max-w-7xl lg:grid-cols-[minmax(20rem,26rem)_minmax(0,1fr)] lg:items-start lg:gap-8">
                    @include('shell.views.entries')
                    @include('shell.views.entry')
                </div>
                @include('shell.views.new')
                @include('shell.views.edit')
                @include('shell.views.walks')
                @include('shell.views.map')
                @include('shell.views.sync')
                @include('shell.views.login')
            </main>
        </div>
    </div>

    @include('shell.toast')
    @include('shell.nav')
</body>
</html>
