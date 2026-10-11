<?php

declare(strict_types=1);

use App\Jobs\ProcessEntryPhoto;
use App\Models\Entry;
use App\Models\EntryPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * A JPEG with a minimal, valid EXIF block (Orientation = 1) inserted after the SOI marker.
 */
function jpegWithExif(int $width, int $height): string
{
    $jpeg = UploadedFile::fake()->image('photo.jpg', $width, $height)->getContent();

    // TIFF header (little endian), one IFD0 entry: Orientation (0x0112), SHORT, count 1, value 1; no next IFD
    $tiff = "II*\x00\x08\x00\x00\x00"."\x01\x00"."\x12\x01\x03\x00\x01\x00\x00\x00\x01\x00\x00\x00"."\x00\x00\x00\x00";
    $payload = "Exif\x00\x00".$tiff;
    $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

    return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
}

beforeEach(function (): void {
    Storage::fake('local');
});

it('strips exif, writes a thumbnail and records the final size', function (): void {
    $photo = EntryPhoto::factory()->create(['path' => 'photos/1/entry/photo.jpg', 'thumb_path' => null]);
    Storage::disk('local')->put($photo->path, jpegWithExif(1600, 1200));

    expect(Storage::disk('local')->get($photo->path))->toContain("Exif\x00\x00");

    (new ProcessEntryPhoto($photo->id))->handle();

    $photo->refresh();
    $stored = Storage::disk('local')->get($photo->path);

    expect($stored)->not->toContain("Exif\x00\x00")
        ->and($photo->thumb_path)->toBe('photos/1/entry/photo_thumb.jpg')
        ->and($photo->width)->toBe(1600)
        ->and($photo->height)->toBe(1200)
        ->and($photo->bytes)->toBe(strlen($stored));

    [$thumbWidth, $thumbHeight] = getimagesizefromstring(Storage::disk('local')->get($photo->thumb_path));

    expect($thumbWidth)->toBe(400)->and($thumbHeight)->toBe(300);
});

it('caps an oversized original at 1600px', function (): void {
    $photo = EntryPhoto::factory()->create(['path' => 'photos/1/entry/big.jpg']);
    Storage::disk('local')->put($photo->path, UploadedFile::fake()->image('big.jpg', 4000, 3000)->getContent());

    (new ProcessEntryPhoto($photo->id))->handle();

    expect($photo->fresh())->width->toBe(1600)->height->toBe(1200);
});

it('does nothing if the photo has gone', function (): void {
    (new ProcessEntryPhoto('01K7A3RB4C6D8E0F2G4H6J8K0M'))->handle();
})->throwsNoExceptions();

it('is queued when a new photo is uploaded, but not on a repeat upload', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $entry = Entry::factory()->for($user)->create();
    $photoId = (string) Str::ulid();
    $bytes = UploadedFile::fake()->image('p.jpg', 800, 600)->getContent();

    foreach ([1, 2] as $attempt) {
        $this->call('PUT', "/api/entries/{$entry->id}/photos/{$photoId}", server: ['CONTENT_TYPE' => 'image/jpeg', 'HTTP_ACCEPT' => 'application/json'], content: $bytes);
    }

    Queue::assertPushed(ProcessEntryPhoto::class, 1);
    Queue::assertPushed(fn (ProcessEntryPhoto $job): bool => $job->photoId === $photoId);
});
