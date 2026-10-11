<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EntryPhoto;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EntryPhoto
 */
class EntryPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_id' => $this->entry_id,
            'width' => $this->width,
            'height' => $this->height,
            'bytes' => $this->bytes,
            'has_thumb' => $this->thumb_path !== null,
            'url' => "/api/photos/{$this->id}",
            'thumb_url' => "/api/photos/{$this->id}/thumb",
            'created_at' => $this->created_at?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z'),
        ];
    }
}
