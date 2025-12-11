<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Internship>
 */
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // student
            'application_id' => null,
            'company_name' => $this->faker->company(),
            'supervisor_name' => $this->faker->name(),
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->addWeeks(10)->toDateString(),
            'status' => 'pending',
            'faculty_supervisor_id' => null,
        ];
    }

    /**
     * Attach a faculty supervisor to the internship.
     */
    public function withFaculty(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'faculty_supervisor_id' => User::factory()->faculty(),
            ];
        });
    }

    /**
     * Example state for an active internship.
     */
    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
