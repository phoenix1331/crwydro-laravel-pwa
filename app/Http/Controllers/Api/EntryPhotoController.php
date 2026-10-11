<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\StoreEntryPhoto;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntryPhotoResource;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntryPhotoController extends Controller
{
    /**
     * Idempotent upload of one JPEG sent as the raw request body: 201 new, 200 if already stored.
     */
    public function update(Request $request, string $entryId, string $photoId, StoreEntryPhoto $store): JsonResponse
    {
        $entry = Entry::withTrashed()->findOrFail($entryId);

        abort_if($entry->user_id !== $request->user()->id, 403);

        $bytes = $request->getContent();

        abort_if($bytes === '', 422, 'The request body must contain the photo.');
        abort_if(strlen($bytes) > config('crwydro.photo_max_bytes'), 413);

        [$photo, $created] = $store->handle($entry, $photoId, $bytes);

        return EntryPhotoResource::make($photo)
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}
