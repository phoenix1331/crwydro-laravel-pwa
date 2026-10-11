<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

function jpegBytes(int $width = 1600, int $height = 1200): string
{
    return UploadedFile::fake()->image('photo.jpg', $width, $height)->getContent();
}

function putPhoto(string $entryId, string $photoId, string $bytes): TestResponse
{
    return test()->call(
        'PUT',
        "/api/entries/{$entryId}/photos/{$photoId}",
        server: ['CONTENT_TYPE' => 'image/jpeg', 'HTTP_ACCEPT' => 'application/json'],
        content: $bytes,
    );
}

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->entry = Entry::factory()->for($this->user)->create();
    $this->photoId = (string) Str::ulid();
});

it('stores a photo sent as the raw body', function (): void {
    putPhoto($this->entry->id, $this->photoId, jpegBytes())
        ->assertCreated()
        ->assertJsonPath('data.id', $this->photoId)
        ->assertJsonPath('data.width', 1600)
        ->assertJsonPath('data.height', 1200);

    $photo = EntryPhoto::find($this->photoId);

    expect($photo->entry_id)->toBe($this->entry->id);
    Storage::disk('local')->assertExists($photo->path);
});

it('treats a re-upload of the same photo as a no-op', function (): void {
    putPhoto($this->entry->id, $this->photoId, jpegBytes())->assertCreated();
    putPhoto($this->entry->id, $this->photoId, jpegBytes(800, 600))
        ->assertOk()
        ->assertJsonPath('data.width', 1600);

    expect(EntryPhoto::count())->toBe(1);
});

it('accepts photos for an entry that is already deleted', function (): void {
    $this->entry->delete();

    putPhoto($this->entry->id, $this->photoId, jpegBytes())->assertCreated();
});

it('rejects a photo id that already belongs to another entry', function (): void {
    putPhoto($this->entry->id, $this->photoId, jpegBytes())->assertCreated();
    $second = Entry::factory()->for($this->user)->create();

    putPhoto($second->id, $this->photoId, jpegBytes())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photo');
});

it('limits each entry to four photos', function (): void {
    EntryPhoto::factory()->count(4)->for($this->entry)->create();

    putPhoto($this->entry->id, $this->photoId, jpegBytes())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photo');
});

it('requires the entry to exist', function (): void {
    putPhoto((string) Str::ulid(), $this->photoId, jpegBytes())->assertNotFound();
});

it('forbids uploading to another user\'s entry', function (): void {
    $other = Entry::factory()->create();

    putPhoto($other->id, $this->photoId, jpegBytes())->assertForbidden();

    expect(EntryPhoto::count())->toBe(0);
});

it('rejects a body that is not a jpeg', function (): void {
    putPhoto($this->entry->id, $this->photoId, 'definitely not a photo')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photo');

    $png = UploadedFile::fake()->image('photo.png', 100, 100)->getContent();

    putPhoto($this->entry->id, $this->photoId, $png)->assertUnprocessable();
});

it('rejects an empty body', function (): void {
    putPhoto($this->entry->id, $this->photoId, '')->assertUnprocessable();
});

it('rejects a photo over the size limit', function (): void {
    config(['crwydro.photo_max_bytes' => 1000]);

    putPhoto($this->entry->id, $this->photoId, jpegBytes())->assertStatus(413);
});

it('requires auth', function (): void {
    $this->app['auth']->forgetGuards();

    putPhoto($this->entry->id, $this->photoId, jpegBytes())->assertUnauthorized();
});
