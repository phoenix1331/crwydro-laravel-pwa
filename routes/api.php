<?php

declare(strict_types=1);

use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\EntryPhotoController;
use App\Http\Controllers\Api\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class)->middleware('throttle:10,1');

Route::get('/ping', fn () => response()->json(['ok' => true])->header('Cache-Control', 'no-store'));

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::put('/entries/{id}', [EntryController::class, 'update'])->whereUlid('id');
    Route::delete('/entries/{id}', [EntryController::class, 'destroy'])->whereUlid('id');

    Route::put('/entries/{entryId}/photos/{photoId}', [EntryPhotoController::class, 'update'])
        ->whereUlid(['entryId', 'photoId']);
});
