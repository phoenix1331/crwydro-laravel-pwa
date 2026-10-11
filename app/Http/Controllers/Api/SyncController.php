<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use App\Sync\SyncCursor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public const PAGE_SIZE = 100;

    /**
     * Pull sync: entries (including soft deletes) changed after the cursor,
     * oldest change first, with their photos. Without a cursor it starts
     * from the beginning, which is how a second device catches up.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['since' => ['nullable', 'string', 'max:200']]);

        $since = $request->filled('since') ? SyncCursor::decode($request->string('since')->toString()) : null;

        abort_if($request->filled('since') && $since === null, 422, 'The sync cursor is not valid.');

        $page = Entry::withTrashed()
            ->whereBelongsTo($request->user())
            ->when($since, fn (Builder $query) => $query->where(function (Builder $query) use ($since): void {
                $query->where('updated_at', '>', $since->updatedAt->format('Y-m-d H:i:s.v'))
                    ->orWhere(fn (Builder $query) => $query
                        ->where('updated_at', $since->updatedAt->format('Y-m-d H:i:s.v'))
                        ->where('id', '>', $since->id));
            }))
            ->with('photos')
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $page->count() > self::PAGE_SIZE;
        $entries = $page->take(self::PAGE_SIZE);
        $next = $entries->isEmpty() ? $since : SyncCursor::fromEntry($entries->last());

        return response()->json([
            'data' => EntryResource::collection($entries)->resolve($request),
            'next_cursor' => $next?->encode(),
            'has_more' => $hasMore,
        ]);
    }
}
