<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryPhoto;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('exports located entries as gpx waypoints, escaping text', function (): void {
    Entry::factory()->for($this->user)->create([
        'title' => 'Pabi Cymreig <Welsh poppy> & friends',
        'tag' => 'flower',
        'lat' => 52.6935,
        'lng' => -3.901,
        'altitude_m' => 412,
        'recorded_at' => '2026-10-10 10:15:30.123',
    ]);
    Entry::factory()->for($this->user)->create(['lat' => null, 'lng' => null]);
    Entry::factory()->create(['title' => 'Someone else']);

    $response = $this->actingAs($this->user)->get('/export/entries.gpx')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/gpx+xml')
        ->assertHeader('Content-Disposition', 'attachment; filename="crwydro-entries-'.now()->format('Y-m-d').'.gpx"');

    $gpx = simplexml_load_string($response->getContent());
    $gpx->registerXPathNamespace('g', 'http://www.topografix.com/GPX/1/1');
    $waypoints = $gpx->xpath('//g:wpt');

    expect($waypoints)->toHaveCount(1)
        ->and((string) $waypoints[0]['lat'])->toBe('52.6935')
        ->and((string) $waypoints[0]->name)->toBe('Pabi Cymreig <Welsh poppy> & friends')
        ->and((string) $waypoints[0]->time)->toBe('2026-10-10T10:15:30.123Z')
        ->and((string) $waypoints[0]->type)->toBe('flower');
});

it('exports every entry as csv with a photo count and guards against formulas', function (): void {
    $entry = Entry::factory()->for($this->user)->create(['title' => '=HYPERLINK("http://evil")', 'route_id' => 'clawdd-offa', 'lat' => null, 'lng' => null]);
    EntryPhoto::factory()->count(2)->for($entry)->create();

    $response = $this->actingAs($this->user)->get('/export/entries.csv')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $rows = array_map(str_getcsv(...), array_filter(explode("\n", $response->getContent())));

    expect($rows[0])->toBe(['id', 'title', 'notes', 'tag', 'walk', 'lat', 'lng', 'accuracy_m', 'altitude_m', 'recorded_at', 'photos'])
        ->and($rows)->toHaveCount(2)
        ->and($rows[1][1])->toBe('\'=HYPERLINK("http://evil")')
        ->and($rows[1][4])->toBe('clawdd-offa')
        ->and($rows[1][10])->toBe('2');
});

it('serves the same exports to the api with a token', function (): void {
    Entry::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user);

    $this->get('/api/export/entries.gpx')->assertOk();
    $this->get('/api/export/entries.csv')->assertOk();
});

it('requires auth', function (): void {
    $this->get('/export/entries.gpx')->assertRedirect('/login');
    $this->getJson('/api/export/entries.csv')->assertUnauthorized();
});
