<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EntryPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['path', 'thumb_path', 'width', 'height', 'bytes'])]
class EntryPhoto extends Model
{
    /** @use HasFactory<EntryPhotoFactory> */
    use HasFactory, HasUlids;

    /**
     * Touch the entry when a photo is added or processed, so pull sync sees it as changed.
     *
     * @var list<string>
     */
    protected $touches = ['entry'];

    /**
     * @return BelongsTo<Entry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'bytes' => 'integer',
        ];
    }
}
