<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The offline app shell; client-side views use hash routes so this is the only page to precache
Route::view('/', 'app')->name('app');
