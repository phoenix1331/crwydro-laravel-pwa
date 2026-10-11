<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Tag;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * Define the model's default state. Coordinates fall within Wales.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recordedAt = $this->faker->dateTimeBetween('-30 days');

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'notes' => $this->faker->optional()->paragraph(),
            'tag' => $this->faker->randomElement(Tag::cases()),
            'lat' => $this->faker->latitude(51.4, 53.4),
            'lng' => $this->faker->longitude(-5.3, -2.7),
            'accuracy_m' => $this->faker->randomFloat(2, 3, 50),
            'altitude_m' => $this->faker->optional()->randomFloat(2, 0, 1085),
            'recorded_at' => $recordedAt,
            'client_updated_at' => $recordedAt,
        ];
    }
}
