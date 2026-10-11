<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Entry;
use Illuminate\Support\Collection;
use XMLWriter;

class EntryExporter
{
    /**
     * GPX 1.1 waypoints, one per entry with a location. Opens in OS Maps,
     * Garmin Connect, Komoot and most GIS tools.
     *
     * @param  Collection<int, Entry>  $entries
     */
    public function gpx(Collection $entries): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('gpx');
        $xml->writeAttribute('version', '1.1');
        $xml->writeAttribute('creator', config('app.name'));
        $xml->writeAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');

        $xml->startElement('metadata');
        $xml->writeElement('name', config('app.name').' entries');
        $xml->writeElement('time', now()->utc()->toIso8601ZuluString());
        $xml->endElement();

        foreach ($entries->filter(fn (Entry $entry): bool => $entry->lat !== null && $entry->lng !== null) as $entry) {
            $xml->startElement('wpt');
            $xml->writeAttribute('lat', (string) $entry->lat);
            $xml->writeAttribute('lon', (string) $entry->lng);

            if ($entry->altitude_m !== null) {
                $xml->writeElement('ele', (string) $entry->altitude_m);
            }

            $xml->writeElement('time', $entry->recorded_at->utc()->toIso8601ZuluString('millisecond'));
            $xml->writeElement('name', $entry->title);

            if ($entry->notes) {
                $xml->writeElement('desc', $entry->notes);
            }

            $xml->writeElement('type', $entry->tag->value);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * @param  Collection<int, Entry>  $entries
     */
    public function csv(Collection $entries): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['id', 'title', 'notes', 'tag', 'walk', 'lat', 'lng', 'accuracy_m', 'altitude_m', 'recorded_at', 'photos'], escape: '');

        foreach ($entries as $entry) {
            fputcsv($handle, [
                $entry->id,
                $this->safeText($entry->title),
                $this->safeText($entry->notes),
                $entry->tag->value,
                $entry->route_id,
                $entry->lat,
                $entry->lng,
                $entry->accuracy_m,
                $entry->altitude_m,
                $entry->recorded_at->utc()->toIso8601ZuluString('millisecond'),
                $entry->photos_count ?? $entry->photos()->count(),
            ], escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Stop spreadsheet apps treating free text as a formula (CSV injection).
     */
    private function safeText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
