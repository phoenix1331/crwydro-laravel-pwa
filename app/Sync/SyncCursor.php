<?php

declare(strict_types=1);

namespace App\Sync;

use App\Models\Entry;
use Carbon\CarbonImmutable;

/**
 * Opaque pull-sync position: the last entry seen, ordered by (updated_at, id).
 * The id tiebreak means entries changed in the same millisecond are never skipped.
 */
final readonly class SyncCursor
{
    public function __construct(
        public CarbonImmutable $updatedAt,
        public string $id,
    ) {}

    public static function fromEntry(Entry $entry): self
    {
        return new self(CarbonImmutable::parse($entry->updated_at), $entry->id);
    }

    public static function decode(?string $cursor): ?self
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        $parts = $decoded === false ? [] : explode('|', $decoded, 2);

        if (count($parts) !== 2 || ! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $parts[1])) {
            return null;
        }

        try {
            return new self(CarbonImmutable::parse($parts[0]), $parts[1]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function encode(): string
    {
        return rtrim(strtr(base64_encode($this->updatedAt->utc()->format('Y-m-d H:i:s.v').'|'.$this->id), '+/', '-_'), '=');
    }
}
