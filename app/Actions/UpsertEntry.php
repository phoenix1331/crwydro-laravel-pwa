<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Entry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpsertEntry
{
    /**
     * Create or update an entry keyed by its client-minted ULID.
     *
     * Retries of the same write are no-ops, and writes older than the stored
     * client_updated_at are ignored (last-write-wins). Returns the entry and
     * whether a new row was created.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: Entry, 1: bool}
     */
    public function handle(User $user, string $id, array $attributes): array
    {
        $attributes['recorded_at'] = CarbonImmutable::parse($attributes['recorded_at'])->utc();
        $attributes['client_updated_at'] = CarbonImmutable::parse($attributes['client_updated_at'])->utc();

        try {
            return DB::transaction(fn (): array => $this->write($user, $id, $attributes));
        } catch (UniqueConstraintViolationException) {
            // A concurrent retry created the row first; apply this write as an update
            return DB::transaction(fn (): array => $this->write($user, $id, $attributes));
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Entry, 1: bool}
     */
    private function write(User $user, string $id, array $attributes): array
    {
        $entry = Entry::withTrashed()->lockForUpdate()->find($id);

        if (! $entry) {
            $entry = new Entry($attributes);
            $entry->id = $id;
            $entry->user()->associate($user);
            $entry->save();

            return [$entry, true];
        }

        if ($entry->user_id !== $user->id) {
            throw new AccessDeniedHttpException;
        }

        if ($entry->trashed() || $attributes['client_updated_at']->lte($entry->client_updated_at)) {
            return [$entry, false];
        }

        $entry->update($attributes);

        return [$entry, false];
    }
}
