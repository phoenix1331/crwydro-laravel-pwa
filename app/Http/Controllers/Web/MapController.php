<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Tag;
use App\Http\Controllers\Controller;
use App\Http\Resources\MapEntryResource;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * Every synced entry for the signed-in walker, newest first.
     */
    public function __invoke(Request $request): View
    {
        $entries = $request->user()
            ->entries()
            ->with('photos')
            ->latest('recorded_at')
            ->get();

        return view('web.map', [
            'entries' => MapEntryResource::collection($entries)->resolve($request),
            'routes' => $entries->pluck('route_id')->filter()->unique()->values(),
            'labels' => [
                'tags' => collect(Tag::cases())->mapWithKeys(fn (Tag $tag): array => [$tag->value => __("app.tags.{$tag->value}")]),
                'route' => __('app.map.route'),
                'key' => __('app.map.key'),
            ],
        ]);
    }
}
