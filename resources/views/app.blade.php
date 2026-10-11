<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2f4c25">
    <meta name="description" content="Offline field journal for walkers in the Welsh hills">

    <title>{{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    <div class="mx-auto flex min-h-dvh max-w-xl flex-col">
        @include('shell.header')

        <main class="flex-1 px-4 pt-4 pb-28">
            @include('shell.views.entries')
            @include('shell.views.placeholder', ['view' => 'new', 'title' => 'New entry'])
            @include('shell.views.placeholder', ['view' => 'entry', 'title' => 'Entry'])
            @include('shell.views.placeholder', ['view' => 'edit', 'title' => 'Edit entry'])
            @include('shell.views.placeholder', ['view' => 'sync', 'title' => 'Sync'])
            @include('shell.views.placeholder', ['view' => 'login', 'title' => 'Log in'])
        </main>

        @include('shell.nav')
    </div>
</body>
</html>
