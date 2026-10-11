<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('sends guests to the login page', function (): void {
    $this->get('/map')->assertRedirect('/login');
});

it('logs in with a session and lands on the map', function (): void {
    User::factory()->create(['email' => 'walker@crwydro.test']);

    $this->post('/login', ['email' => 'walker@crwydro.test', 'password' => 'password'])
        ->assertRedirect('/map');

    $this->assertAuthenticated();
});

it('rejects bad credentials', function (): void {
    User::factory()->create(['email' => 'walker@crwydro.test']);

    $this->from('/login')
        ->post('/login', ['email' => 'walker@crwydro.test', 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('shows only the walker\'s own synced entries, without deleted ones', function (): void {
    $user = User::factory()->create();
    $mine = Entry::factory()->for($user)->create(['title' => 'Barcud coch', 'route_id' => 'llwybr-glyndwr']);
    Entry::factory()->for($user)->create(['title' => 'Deleted'])->delete();
    Entry::factory()->create(['title' => 'Someone else']);

    $response = $this->actingAs($user)->get('/map')->assertOk();

    $response->assertSee('Barcud coch')
        ->assertDontSee('Deleted')
        ->assertDontSee('Someone else')
        ->assertViewHas('entries', fn (array $entries): bool => count($entries) === 1 && $entries[0]['id'] === $mine->id)
        ->assertViewHas('routes', fn ($routes): bool => $routes->all() === ['llwybr-glyndwr']);
});

it('serves a photo only to its owner, preferring the thumbnail when asked', function (): void {
    Storage::fake('local');
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->create();
    $photo = EntryPhoto::factory()->for($entry)->create(['path' => 'photos/full.jpg', 'thumb_path' => 'photos/full_thumb.jpg']);
    Storage::disk('local')->put('photos/full.jpg', UploadedFile::fake()->image('a.jpg', 1600, 1200)->getContent());
    Storage::disk('local')->put('photos/full_thumb.jpg', UploadedFile::fake()->image('b.jpg', 400, 300)->getContent());

    $full = $this->actingAs($user)->get("/photos/{$photo->id}")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $thumb = $this->actingAs($user)->get("/photos/{$photo->id}/thumb")->assertOk();

    expect(getimagesizefromstring($full->streamedContent())[0])->toBe(1600)
        ->and(getimagesizefromstring($thumb->streamedContent())[0])->toBe(400);

    $this->actingAs(User::factory()->create())->get("/photos/{$photo->id}")->assertNotFound();
});

it('falls back to the full photo until the thumbnail exists', function (): void {
    Storage::fake('local');
    $user = User::factory()->create();
    $photo = EntryPhoto::factory()->for(Entry::factory()->for($user))->create(['path' => 'photos/full.jpg', 'thumb_path' => null]);
    Storage::disk('local')->put('photos/full.jpg', UploadedFile::fake()->image('a.jpg', 1600, 1200)->getContent());

    $response = $this->actingAs($user)->get("/photos/{$photo->id}/thumb")->assertOk();

    expect(getimagesizefromstring($response->streamedContent())[0])->toBe(1600);
});

it('logs out', function (): void {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});
