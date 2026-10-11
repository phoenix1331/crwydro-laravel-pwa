<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entry;
use App\Models\EntryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entry
 */
class MapEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'notes' => $this->notes,
            'tag' => $this->tag,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'route_id' => $this->route_id,
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'photos' => $this->photos->map(fn (EntryPhoto $photo): array => [
                'thumb' => route('photos.show', [$photo, 'thumb']),
                'full' => route('photos.show', $photo),
            ])->all(),
        ];
    }
}
