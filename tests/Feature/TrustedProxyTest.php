<?php

declare(strict_types=1);

it('builds https asset urls behind a tls-terminating proxy such as ngrok', function (): void {
    $this->withHeaders([
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'crwydro.example.ngrok-free.dev',
        'X-Forwarded-Port' => '443',
    ])->get('/')
        ->assertOk()
        ->assertSee('https://crwydro.example.ngrok-free.dev/build/assets/', false)
        ->assertDontSee('http://crwydro.example.ngrok-free.dev/build/assets/', false);
});
