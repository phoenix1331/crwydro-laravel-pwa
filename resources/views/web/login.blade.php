@extends('web.layout')

@section('title', 'Log in')

@section('content')
    <div class="grid min-h-dvh md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <aside class="contour-panel flex flex-col justify-between gap-8 p-6 md:p-10">
            <a href="{{ route('app') }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-[-0.02em]">
                @include('shell.icon', ['name' => 'peak', 'class' => 'size-7'])
                {{ config('app.name') }}
            </a>
            <div class="hidden md:block">
                <p class="max-w-[18ch] text-4xl leading-tight font-extrabold tracking-[-0.02em]">Every entry from the hill, on one map.</p>
                <p class="mt-4 max-w-[45ch] text-panel-muted">Log in to review what you've synced from your phone, and download it as GPX or CSV.</p>
            </div>
        </aside>

        <main class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-sm">
                <h1 class="text-3xl">Log in</h1>
                <p class="hint mt-1 mb-8">Use the same email and password as the app.</p>

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="username" required autofocus>
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <input id="password" name="password" type="password" class="field" autocomplete="current-password" required>
                    </div>

                    <label class="flex min-h-10 items-center gap-2.5 text-sm">
                        <input type="checkbox" name="remember" value="1" class="size-5 accent-accent">
                        Keep me logged in on this computer
                    </label>

                    @error('email')
                        <p class="notice bg-danger-soft text-danger" role="alert">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-primary w-full">Log in</button>
                </form>
            </div>
        </main>
    </div>
@endsection
