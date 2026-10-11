<?php

declare(strict_types=1);

use App\Models\User;

it('issues a token for valid credentials', function (): void {
    $user = User::factory()->create(['email' => 'walker@crwydro.test']);

    $response = $this->postJson('/api/login', [
        'email' => 'walker@crwydro.test',
        'password' => 'password',
        'device_name' => 'pixel',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('user.id', $user->id);

    expect($user->tokens()->count())->toBe(1);
});

it('issues a token that never expires', function (): void {
    User::factory()->create(['email' => 'walker@crwydro.test']);

    $this->postJson('/api/login', [
        'email' => 'walker@crwydro.test',
        'password' => 'password',
        'device_name' => 'pixel',
    ]);

    expect(config('sanctum.expiration'))->toBeNull()
        ->and(User::first()->tokens()->first()->expires_at)->toBeNull();
});

it('rejects bad credentials', function (): void {
    User::factory()->create(['email' => 'walker@crwydro.test']);

    $this->postJson('/api/login', [
        'email' => 'walker@crwydro.test',
        'password' => 'wrong',
        'device_name' => 'pixel',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('requires email, password and device name', function (): void {
    $this->postJson('/api/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

it('requires a token for protected routes', function (): void {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('accepts a bearer token on protected routes', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('pixel')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});
