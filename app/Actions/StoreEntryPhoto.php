<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Entry;
use App\Models\EntryPhoto;
use finfo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoreEntryPhoto
{
    /**
     * Store a photo keyed by its client-minted ULID. Re-uploading an existing
     * photo is a no-op. Returns the photo and whether it was newly created.
     *
     * @return array{0: EntryPhoto, 1: bool}
     */
    public function handle(Entry $entry, string $id, string $bytes): array
    {
        $existing = EntryPhoto::find($id);

        if ($existing) {
            return [$this->guardSameEntry($existing, $entry), false];
        }

        if ($entry->photos()->count() >= config('crwydro.photos_per_entry')) {
            throw ValidationException::withMessages([
                'photo' => 'An entry can have at most '.config('crwydro.photos_per_entry').' photos.',
            ]);
        }

        [$width, $height] = $this->dimensions($bytes);

        $path = "photos/{$entry->user_id}/{$entry->id}/{$id}.jpg";
        Storage::disk(config('crwydro.photos_disk'))->put($path, $bytes);

        try {
            $photo = new EntryPhoto([
                'path' => $path,
                'width' => $width,
                'height' => $height,
                'bytes' => strlen($bytes),
            ]);
            $photo->id = $id;
            $photo->entry()->associate($entry);
            $photo->save();
        } catch (UniqueConstraintViolationException) {
            // A concurrent retry stored it first; the file path is deterministic so nothing to clean up
            return [$this->guardSameEntry(EntryPhoto::findOrFail($id), $entry), false];
        }

        return [$photo, true];
    }

    private function guardSameEntry(EntryPhoto $photo, Entry $entry): EntryPhoto
    {
        if ($photo->entry_id !== $entry->id) {
            throw ValidationException::withMessages([
                'photo' => 'This photo id belongs to a different entry.',
            ]);
        }

        return $photo;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dimensions(string $bytes): array
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $size = $mime === 'image/jpeg' ? @getimagesizefromstring($bytes) : false;

        if ($size === false) {
            throw ValidationException::withMessages([
                'photo' => 'The photo must be a JPEG image.',
            ]);
        }

        return [$size[0], $size[1]];
    }
}
