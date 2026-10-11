<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

it('has a welsh string for every english string, and no extras', function (): void {
    $english = array_keys(Arr::dot(require lang_path('en/app.php')));
    $welsh = array_keys(Arr::dot(require lang_path('cy/app.php')));

    expect(array_diff($english, $welsh))->toBe([])
        ->and(array_diff($welsh, $english))->toBe([]);
});

it('keeps the same placeholders in both languages', function (): void {
    $english = Arr::dot(require lang_path('en/app.php'));
    $welsh = Arr::dot(require lang_path('cy/app.php'));

    foreach ($english as $key => $message) {
        preg_match_all('/:[a-z]+/', $message, $expected);
        preg_match_all('/:[a-z]+/', $welsh[$key], $actual);

        expect(array_unique($actual[0]))->toEqualCanonicalizing(array_unique($expected[0]), "Placeholders differ for {$key}");
    }
});

it('exports flat json for the offline client', function (): void {
    $this->artisan('crwydro:export-lang')->assertSuccessful();

    $welsh = json_decode(File::get(resource_path('js/lang/cy.json')), true);

    expect($welsh['banner.offline'])->toStartWith('Dim signal? Dim problem.')
        ->and($welsh['nav.entries'])->toBe('Cofnodion');
});

it('renders the server pages in welsh when the app chose welsh', function (): void {
    $this->withUnencryptedCookie('crwydro_locale', 'cy')
        ->get('/login')
        ->assertOk()
        ->assertSee('Mewngofnodi')
        ->assertSee('Pob cofnod o’r bryniau, ar un map.', false);

    $user = User::factory()->create();
    Entry::factory()->for($user)->create(['tag' => 'bird']);

    $this->actingAs($user)
        ->withUnencryptedCookie('crwydro_locale', 'cy')
        ->get('/map')
        ->assertSee('Eich cofnodion')
        ->assertSee('1 cofnod wedi’i gysoni', false)
        ->assertSee('Aderyn');
});

it('falls back to english for an unknown locale cookie', function (): void {
    $this->withUnencryptedCookie('crwydro_locale', 'fr')->get('/login')->assertSee('Every entry from the hill, on one map.');
});
