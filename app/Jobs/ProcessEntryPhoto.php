<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EntryPhoto;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Re-encode an uploaded photo without EXIF (so a precise location never
 * travels with the file), cap it at 1600px and create its list thumbnail. The browser client
 * already strips EXIF when it resizes, so this protects against other clients.
 */
class ProcessEntryPhoto implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const MAX_EDGE = 1600;

    public const THUMB_EDGE = 400;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public string $photoId) {}

    public function uniqueId(): string
    {
        return $this->photoId;
    }

    public function handle(): void
    {
        $photo = EntryPhoto::find($this->photoId);

        if (! $photo) {
            return;
        }

        $disk = Storage::disk(config('crwydro.photos_disk'));
        $manager = new ImageManager(Driver::class);

        // Decoding applies the EXIF orientation, so the stripped copy still displays upright.
        // The browser already resizes to 1600px; the cap covers other API clients.
        $image = $manager->decodeBinary($disk->get($photo->path))->scaleDown(self::MAX_EDGE, self::MAX_EDGE);

        $stripped = (string) $image->encode(new JpegEncoder(quality: 85, strip: true));
        $disk->put($photo->path, $stripped);

        $thumbPath = Str::replaceLast('.jpg', '_thumb.jpg', $photo->path);
        $thumb = $image->scaleDown(self::THUMB_EDGE, self::THUMB_EDGE)->encode(new JpegEncoder(quality: 75, strip: true));
        $disk->put($thumbPath, (string) $thumb);

        $original = $manager->decodeBinary($stripped);

        $photo->update([
            'thumb_path' => $thumbPath,
            'width' => $original->width(),
            'height' => $original->height(),
            'bytes' => strlen($stripped),
        ]);
    }
}
