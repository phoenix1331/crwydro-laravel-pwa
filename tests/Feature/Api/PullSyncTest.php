<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SyncController;
use App\Models\Entry;
use App\Models\EntryPhoto;
use App\Models\User;
use App\Sync\SyncCursor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

function entryAt(User $user, string $updatedAt, array $attributes = []): Entry
{
    $entry = Entry::factory()->for($user)->create($attributes);
    Entry::withTrashed()->whereKey($entry->id)->update(['updated_at' => $updatedAt]);

    return $entry->fresh() ?? Entry::withTrashed()->find($entry->id);
}

it('returns everything from the start without a cursor, oldest change first', function (): void {
    $second = entryAt($this->user, '2026-10-11 10:00:00.200');
    $first = entryAt($this->user, '2026-10-11 10:00:00.100');

    $this->getJson('/api/sync')
        ->assertOk()
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.1.id', $second->id)
        ->assertJsonStructure(['data' => [['id', 'title', 'client_updated_at', 'deleted_at', 'photos']], 'next_cursor']);
});

it('only returns changes after the cursor', function (): void {
    entryAt($this->user, '2026-10-11 10:00:00.100');
    $cursor = $this->getJson('/api/sync')->json('next_cursor');
    $later = entryAt($this->user, '2026-10-11 11:00:00.000');

    $this->getJson('/api/sync?since='.$cursor)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $later->id);
});

it('never skips entries changed in the same millisecond across pages', function (): void {
    $ids = collect(range(1, 3))->map(fn () => entryAt($this->user, '2026-10-11 10:00:00.500')->id)->sort()->values();
    $seen = [];
    $cursor = null;

    // Shrink pages by walking with a cursor positioned on each entry in turn
    foreach (range(1, 3) as $step) {
        $response = $this->getJson('/api/sync'.($cursor ? '?since='.$cursor : ''))->assertOk();
        $seen[] = $response->json('data.0.id');
        $cursor = SyncCursor::fromEntry(Entry::find($response->json('data.0.id')))->encode();
    }

    expect($seen)->toBe($ids->all());
});

it('pages large change sets with has_more', function (): void {
    Entry::factory()->count(SyncController::PAGE_SIZE + 5)->for($this->user)->create();

    $first = $this->getJson('/api/sync')->assertJsonPath('has_more', true);
    expect($first->json('data'))->toHaveCount(SyncController::PAGE_SIZE);

    $this->getJson('/api/sync?since='.$first->json('next_cursor'))
        ->assertJsonPath('has_more', false)
        ->assertJsonCount(5, 'data');
});

it('includes soft deletes so other devices can remove them', function (): void {
    $entry = Entry::factory()->for($this->user)->create();
    $entry->delete();

    $this->getJson('/api/sync')->assertJsonPath('data.0.deleted_at', fn ($value) => $value !== null);
});

it('only returns the walker\'s own entries', function (): void {
    Entry::factory()->create();

    $this->getJson('/api/sync')->assertJsonCount(0, 'data')->assertJsonPath('next_cursor', null);
});

it('echoes the cursor when nothing has changed', function (): void {
    entryAt($this->user, '2026-10-11 10:00:00.100');
    $cursor = $this->getJson('/api/sync')->json('next_cursor');

    $this->getJson('/api/sync?since='.$cursor)->assertJsonCount(0, 'data')->assertJsonPath('next_cursor', $cursor);
});

it('rejects a malformed cursor', function (): void {
    $this->getJson('/api/sync?since=not-a-cursor')->assertUnprocessable();
});

it('surfaces an entry again when a photo is added to it later', function (): void {
    Storage::fake('local');
    $entry = entryAt($this->user, '2026-10-11 10:00:00.100');
    $cursor = $this->getJson('/api/sync')->json('next_cursor');

    $photoId = (string) Str::ulid();
    $this->call('PUT', "/api/entries/{$entry->id}/photos/{$photoId}", server: ['CONTENT_TYPE' => 'image/jpeg', 'HTTP_ACCEPT' => 'application/json'], content: UploadedFile::fake()->image('p.jpg', 800, 600)->getContent())->assertCreated();

    $this->getJson('/api/sync?since='.$cursor)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.photos.0.id', $photoId)
        ->assertJsonPath('data.0.photos.0.thumb_url', "/api/photos/{$photoId}/thumb");
});

it('serves photos to the api with a token', function (): void {
    Storage::fake('local');
    $photo = EntryPhoto::factory()->for(Entry::factory()->for($this->user))->create(['path' => 'photos/a.jpg']);
    Storage::disk('local')->put('photos/a.jpg', UploadedFile::fake()->image('a.jpg', 100, 100)->getContent());

    $this->get("/api/photos/{$photo->id}")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
});

it('requires auth', function (): void {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/sync')->assertUnauthorized();
});
