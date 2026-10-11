<?php

declare(strict_types=1);

use App\Enums\Tag;
use App\Models\Entry;
use App\Models\EntryPhoto;
use Illuminate\Support\Str;

it('accepts a client-generated ulid as its key', function (): void {
    $id = (string) Str::ulid();

    $entry = Entry::factory()->create(['id' => $id]);

    expect($entry->id)->toBe($id)
        ->and(Entry::find($id))->not->toBeNull();
});

it('casts tag to the enum', function (): void {
    $entry = Entry::factory()->create(['tag' => 'fungus']);

    expect($entry->fresh()->tag)->toBe(Tag::Fungus);
});

it('keeps millisecond precision on client_updated_at', function (): void {
    $entry = Entry::factory()->create(['client_updated_at' => '2026-10-11 10:15:30.123']);

    expect($entry->fresh()->client_updated_at->format('v'))->toBe('123');
});

it('soft deletes and owns photos', function (): void {
    $photo = EntryPhoto::factory()->create();
    $entry = $photo->entry;

    $entry->delete();

    expect(Entry::find($entry->id))->toBeNull()
        ->and(Entry::withTrashed()->find($entry->id)->photos)->toHaveCount(1);
});
