<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17231d">
    <title>@yield('title') | {{ __('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="48x48" href="/icons/favicon-48.png">
    @fonts
    @vite(['resources/css/app.css', ...($scripts ?? [])])
</head>
<body class="min-h-dvh">
    @yield('content')
</body>
</html>
