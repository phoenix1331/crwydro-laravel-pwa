<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpsertEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpsertEntryRequest;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EntryController extends Controller
{
    /**
     * Idempotent upsert: 201 when the ULID is new, 200 otherwise.
     */
    public function update(UpsertEntryRequest $request, string $id, UpsertEntry $upsert): JsonResponse
    {
        [$entry, $created] = $upsert->handle($request->user(), $id, $request->validated());

        return EntryResource::make($entry)
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }

    /**
     * Idempotent soft delete: 204 whether or not the entry exists or is already deleted.
     */
    public function destroy(Request $request, string $id): Response
    {
        $entry = Entry::withTrashed()->find($id);

        if (! $entry) {
            return response()->noContent();
        }

        abort_if($entry->user_id !== $request->user()->id, 403);

        if (! $entry->trashed()) {
            $entry->delete();
        }

        return response()->noContent();
    }
}
