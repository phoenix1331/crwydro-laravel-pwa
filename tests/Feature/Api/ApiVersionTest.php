<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('says which api version the server speaks on every response', function (): void {
    $this->getJson('/api/ping')->assertHeader('X-Crwydro-Api', '1');
    $this->getJson('/api/sync')->assertUnauthorized()->assertHeader('X-Crwydro-Api', '1');
});

it('refuses writes from a client older than the minimum, before touching data', function (): void {
    config(['crwydro.min_client_api' => 2]);
    Sanctum::actingAs(User::factory()->create());

    $this->withHeader('X-Crwydro-Client-Api', '1')
        ->putJson('/api/entries/'.Str::ulid(), ['title' => 'Old client', 'tag' => 'view', 'recorded_at' => now(), 'client_updated_at' => now()])
        ->assertStatus(426);

    $this->assertDatabaseCount('entries', 0);
});

it('accepts current clients and tools that send no version', function (): void {
    config(['crwydro.min_client_api' => 2]);

    $this->withHeader('X-Crwydro-Client-Api', '2')->getJson('/api/ping')->assertOk();
    $this->getJson('/api/ping')->assertOk();
});
