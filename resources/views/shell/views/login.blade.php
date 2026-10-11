<section x-data="loginForm" x-show="$store.router.is('login')" x-cloak>
    <h1 class="text-2xl font-bold">Log in to sync</h1>
    <p class="mt-1 mb-5 text-peat-600">You can keep logging entries without an account. Logging in backs them up to the server.</p>

    <p x-show="$store.auth.authRequired"
       class="mb-5 rounded-xl bg-bracken-400/20 px-4 py-3 text-sm font-semibold text-bracken-600">
        Your session ended. Log in again to carry on syncing. Nothing on this device has been lost.
    </p>

    <form class="space-y-5" @submit.prevent="submit">
        <div>
            <label for="login-email" class="label">Email</label>
            <input id="login-email" type="email" class="field" x-model="email" autocomplete="username" required inputmode="email">
        </div>

        <div>
            <label for="login-password" class="label">Password</label>
            <input id="login-password" type="password" class="field" x-model="password" autocomplete="current-password" required>
        </div>

        <p x-show="error" x-text="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"></p>

        <button type="submit" class="btn-primary w-full" :disabled="busy">
            <span x-text="busy ? 'Logging in…' : 'Log in'"></span>
        </button>
    </form>
</section>
