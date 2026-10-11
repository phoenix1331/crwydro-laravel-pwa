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
    <div class="mx-auto flex min-h-dvh max-w-xl flex-col">
        @include('shell.header')

        <main class="flex-1 px-4 pt-4 pb-28">
            @include('shell.views.entries')
            @include('shell.views.new')
            @include('shell.views.entry')
            @include('shell.views.edit')
            @include('shell.views.walks')
            @include('shell.views.sync')
            @include('shell.views.login')
        </main>

        @include('shell.toast')
        @include('shell.nav')
    </div>
</body>
</html>
