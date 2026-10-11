<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // FrankenPHP serves the app and the `queue` container runs the worker, so `artisan dev` only runs logs and Vite
        DevCommands::except('server', 'queue');

        // One list drives both the phone bottom nav and the tablet/laptop sidebar
        View::composer(['shell.nav', 'shell.sidebar'], fn ($view) => $view->with('navItems', [
            ['href' => '#/', 'label' => 'Entries', 'key' => 'nav.entries', 'icon' => 'entries', 'routes' => "'entries', 'entry', 'edit'"],
            ['href' => '#/walks', 'label' => 'Walks', 'key' => 'nav.walks', 'icon' => 'walks', 'routes' => "'walks'"],
            ['href' => '#/map', 'label' => 'Map', 'key' => 'nav.map', 'icon' => 'map', 'routes' => "'map'"],
            ['href' => '#/sync', 'label' => 'Sync', 'key' => 'nav.sync', 'icon' => 'sync', 'routes' => "'sync', 'login'"],
        ]));
    }
}
