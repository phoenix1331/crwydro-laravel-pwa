<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function entryPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Welsh poppy on the Minffordd Path',
        'notes' => 'Pabi Cymreig by the stream below Llyn Cau',
        'tag' => 'flower',
        'lat' => 52.6893,
        'lng' => -3.8936,
        'accuracy_m' => 8.5,
        'altitude_m' => 412.0,
        'recorded_at' => '2026-10-11T10:15:30.123Z',
        'client_updated_at' => '2026-10-11T10:15:30.123Z',
    ], $overrides);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->id = (string) Str::ulid();
});

it('creates an entry with the client ulid', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload())
        ->assertCreated()
        ->assertJsonPath('data.id', $this->id)
        ->assertJsonPath('data.tag', 'flower')
        ->assertJsonPath('data.client_updated_at', '2026-10-11T10:15:30.123Z');

    expect(Entry::find($this->id)->user_id)->toBe($this->user->id);
});

it('gives one row when the same ulid is sent twice', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload())->assertCreated();
    $this->putJson("/api/entries/{$this->id}", entryPayload())->assertOk();

    expect(Entry::count())->toBe(1);
});

it('applies a newer update', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload())->assertCreated();

    $this->putJson("/api/entries/{$this->id}", entryPayload([
        'title' => 'Welsh poppy, fixed typo',
        'client_updated_at' => '2026-10-11T18:00:00.000Z',
    ]))->assertOk()->assertJsonPath('data.title', 'Welsh poppy, fixed typo');

    expect(Entry::find($this->id)->title)->toBe('Welsh poppy, fixed typo');
});

it('ignores an update older than the stored client_updated_at', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload([
        'title' => 'Newest',
        'client_updated_at' => '2026-10-11T18:00:00.000Z',
    ]))->assertCreated();

    $this->putJson("/api/entries/{$this->id}", entryPayload([
        'title' => 'Stale',
        'client_updated_at' => '2026-10-11T17:59:59.999Z',
    ]))->assertOk()->assertJsonPath('data.title', 'Newest');

    expect(Entry::find($this->id)->title)->toBe('Newest');
});

it('does not resurrect a deleted entry', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload())->assertCreated();
    $this->deleteJson("/api/entries/{$this->id}")->assertNoContent();

    $this->putJson("/api/entries/{$this->id}", entryPayload([
        'client_updated_at' => '2026-10-12T09:00:00.000Z',
    ]))->assertOk()->assertJsonPath('data.deleted_at', fn ($value) => $value !== null);

    expect(Entry::find($this->id))->toBeNull();
});

it('soft deletes and is safe to delete twice', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload())->assertCreated();

    $this->deleteJson("/api/entries/{$this->id}")->assertNoContent();
    $this->deleteJson("/api/entries/{$this->id}")->assertNoContent();

    expect(Entry::withTrashed()->find($this->id)->trashed())->toBeTrue();
});

it('treats deleting an unknown entry as done', function (): void {
    $this->deleteJson("/api/entries/{$this->id}")->assertNoContent();
});

it('forbids touching another user\'s entry', function (): void {
    $other = Entry::factory()->create(['title' => 'Not yours']);

    $this->putJson("/api/entries/{$other->id}", entryPayload([
        'client_updated_at' => '2030-01-01T00:00:00.000Z',
    ]))->assertForbidden();
    $this->deleteJson("/api/entries/{$other->id}")->assertForbidden();

    expect($other->fresh()->title)->toBe('Not yours')
        ->and($other->fresh()->trashed())->toBeFalse();
});

it('attaches an entry to a featured walk', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload(['route_id' => 'cadair-idris-minffordd']))
        ->assertCreated()
        ->assertJsonPath('data.route_id', 'cadair-idris-minffordd');
});

it('rejects an unknown walk', function (): void {
    $this->putJson("/api/entries/{$this->id}", entryPayload(['route_id' => 'the-pennine-way']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('route_id');
});

it('validates the payload', function (): void {
    $this->putJson("/api/entries/{$this->id}", ['tag' => 'dragon', 'lat' => 52.1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'tag', 'lng', 'recorded_at', 'client_updated_at']);
});

it('rejects an id that is not a ulid', function (): void {
    $this->putJson('/api/entries/not-a-ulid', entryPayload())->assertNotFound();
});

it('requires auth', function (): void {
    $this->app['auth']->forgetGuards();

    $this->putJson("/api/entries/{$this->id}", entryPayload())->assertUnauthorized();
    $this->deleteJson("/api/entries/{$this->id}")->assertUnauthorized();
});
