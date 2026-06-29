<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // user_id format from FEATURE_MATRIX: YYYY-NNNNN (student) / YYYY-NNNN (educator)
        return [
            'user_type' => 'student',
            'user_id' => fake()->year().'-'.fake()->unique()->numerify('#####'),
            'given_name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function educator(): static
    {
        return $this->state(fn () => ['user_type' => 'educator']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['user_type' => 'admin']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
