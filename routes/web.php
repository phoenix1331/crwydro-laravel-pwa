<?php

declare(strict_types=1);

use App\Http\Controllers\ExportController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\Web\MapController;
use App\Http\Controllers\Web\SessionController;
use Illuminate\Support\Facades\Route;

// The offline app shell; client-side views use hash routes so this is the only page to precache
Route::view('/', 'app')->name('app');

// Server-rendered views for reviewing trips at home, behind a normal session login
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::get('/map', MapController::class)->name('map');
    Route::get('/photos/{photo}/{variant?}', PhotoController::class)
        ->whereIn('variant', ['full', 'thumb'])
        ->name('photos.show');
    Route::get('/export/entries.gpx', [ExportController::class, 'gpx'])->name('export.gpx');
    Route::get('/export/entries.csv', [ExportController::class, 'csv'])->name('export.csv');
});
