@extends('web.layout')

@section('title', __('app.web.login_title'))

@section('content')
    <div class="grid min-h-dvh md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <aside class="contour-panel flex flex-col justify-between gap-8 p-6 md:p-10">
            <a href="{{ route('app') }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-[-0.02em]">
                @include('shell.icon', ['name' => 'peak', 'class' => 'size-7'])
                {{ config('app.name') }}
            </a>
            <div class="hidden md:block">
                <p class="max-w-[18ch] text-4xl leading-tight font-extrabold tracking-[-0.02em]">{{ __('app.web.login_pitch') }}</p>
                <p class="mt-4 max-w-[45ch] text-panel-muted">{{ __('app.web.login_pitch_body') }}</p>
            </div>
        </aside>

        <main class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-sm">
                <h1 class="text-3xl">{{ __('app.web.login_title') }}</h1>
                <p class="hint mt-1 mb-8">{{ __('app.web.login_intro') }}</p>

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="label">{{ __('app.login.email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="username" required autofocus>
                    </div>

                    <div>
                        <label for="password" class="label">{{ __('app.login.password') }}</label>
                        <input id="password" name="password" type="password" class="field" autocomplete="current-password" required>
                    </div>

                    <label class="flex min-h-10 items-center gap-2.5 text-sm">
                        <input type="checkbox" name="remember" value="1" class="size-5 accent-accent">
                        {{ __('app.web.remember') }}
                    </label>

                    @error('email')
                        <p class="notice bg-danger-soft text-danger" role="alert">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-primary w-full">{{ __('app.login.submit') }}</button>
                </form>
            </div>
        </main>
    </div>
@endsection
