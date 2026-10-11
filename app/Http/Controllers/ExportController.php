<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\EntryExporter;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GPX and CSV downloads of the walker's synced entries. Served both from the
 * web session (map page links) and the token API.
 */
class ExportController extends Controller
{
    public function gpx(Request $request, EntryExporter $exporter): Response
    {
        return $this->download($exporter->gpx($this->entries($request)), 'gpx', 'application/gpx+xml');
    }

    public function csv(Request $request, EntryExporter $exporter): Response
    {
        return $this->download($exporter->csv($this->entries($request)), 'csv', 'text/csv; charset=UTF-8');
    }

    /**
     * @return Collection<int, Entry>
     */
    private function entries(Request $request): Collection
    {
        return $request->user()->entries()->withCount('photos')->oldest('recorded_at')->get();
    }

    private function download(string $body, string $extension, string $contentType): Response
    {
        $filename = 'crwydro-entries-'.now()->format('Y-m-d').'.'.$extension;

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
