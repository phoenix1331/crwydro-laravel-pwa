@extends('web.layout')

@section('title', 'Log in')

@section('content')
    <main class="mx-auto max-w-sm px-4 py-12">
        <h1 class="text-2xl font-bold">Review your walks</h1>
        <p class="mt-1 mb-6 text-muted">Log in to see every synced entry on a map.</p>

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

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-accent">
                Keep me logged in
            </label>

            @error('email')
                <p class="rounded-xl bg-danger-soft px-4 py-3 text-sm font-bold text-danger" role="alert">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn-primary w-full">Log in</button>
        </form>
    </main>
@endsection
