<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EntryPhoto>
 */
class EntryPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_id' => Entry::factory(),
            'path' => 'photos/'.Str::ulid()->toBase32().'.jpg',
            'thumb_path' => null,
            'width' => 1600,
            'height' => 1200,
            'bytes' => $this->faker->numberBetween(150_000, 900_000),
        ];
    }
}
