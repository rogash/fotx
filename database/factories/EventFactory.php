<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->sentence(3);

        return [
            'user_id' => User::factory()->state(['role' => 'photographer']),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'event_date' => fake()->dateTimeBetween('-1 month', '+2 months'),
            'location' => fake()->city(),
            'description' => fake()->paragraph(),
            'price_per_photo' => fake()->randomFloat(2, 12, 45),
            'status' => 'published',
        ];
    }
}
