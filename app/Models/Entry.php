<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Tag;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'notes',
    'tag',
    'lat',
    'lng',
    'accuracy_m',
    'altitude_m',
    'recorded_at',
    'client_updated_at',
])]
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * Millisecond precision so last-write-wins can order rapid offline edits.
     */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<EntryPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(EntryPhoto::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tag' => Tag::class,
            'lat' => 'float',
            'lng' => 'float',
            'accuracy_m' => 'float',
            'altitude_m' => 'float',
            'recorded_at' => 'immutable_datetime',
            'client_updated_at' => 'immutable_datetime',
        ];
    }
}
