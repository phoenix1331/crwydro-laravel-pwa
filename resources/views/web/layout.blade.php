<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2f4c25">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="48x48" href="/icons/favicon-48.png">
    @fonts
    @vite(['resources/css/app.css', ...($scripts ?? [])])
</head>
<body class="min-h-dvh">
    <header class="bg-moss-700 text-white">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4">
            <a href="{{ route('app') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight">
                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 20 9 7l4 7 3-4 6 10z" />
                </svg>
                {{ config('app.name') }}
            </a>

            @auth
                <div class="flex items-center gap-4 text-sm">
                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full bg-white/10 px-3 py-1.5 font-semibold hover:bg-white/20">Log out</button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    @yield('content')
</body>
</html>
