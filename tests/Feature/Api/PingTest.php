<?php

declare(strict_types=1);

it('answers without auth and is never cached', function (): void {
    $this->getJson('/api/ping')
        ->assertOk()
        ->assertJson(['ok' => true])
        ->assertHeader('Cache-Control', 'no-store, private');
});
