<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostalServer>
 */
class PostalServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Mail Server',
            'host' => $this->faker->domainName,
            'port' => '3306',
            'database' => 'postal_' . $this->faker->slug,
            'username' => $this->faker->userName,
            'password' => $this->faker->password,
            'api_key' => $this->faker->optional()->uuid,
            'api_url' => $this->faker->optional()->url,
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'additional_config' => $this->faker->optional()->randomElement([
                ['charset' => 'utf8mb4'],
                ['ssl_mode' => 'required'],
                ['timeout' => 30]
            ])
        ];
    }

    /**
     * Indicate that the server is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the server is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
