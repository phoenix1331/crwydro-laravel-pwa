<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entry;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entry
 */
class EntryResource extends JsonResource
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
            'accuracy_m' => $this->accuracy_m,
            'altitude_m' => $this->altitude_m,
            'route_id' => $this->route_id,
            'recorded_at' => $this->iso($this->recorded_at),
            'client_updated_at' => $this->iso($this->client_updated_at),
            'updated_at' => $this->iso($this->updated_at),
            'deleted_at' => $this->iso($this->deleted_at),
        ];
    }

    private function iso(?DateTimeInterface $date): ?string
    {
        return $date?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z');
    }
}
