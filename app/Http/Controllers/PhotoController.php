<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EntryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    /**
     * Stream a photo from the private disk to its owner (web session or API token). Falls back to the
     * full image if the queued thumbnail has not been made yet.
     */
    public function __invoke(Request $request, EntryPhoto $photo, string $variant = 'full'): StreamedResponse
    {
        abort_unless((int) $photo->entry()->withTrashed()->value('user_id') === $request->user()->id, 404);

        $path = $variant === 'thumb' && $photo->thumb_path ? $photo->thumb_path : $photo->path;

        return Storage::disk(config('crwydro.photos_disk'))->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
