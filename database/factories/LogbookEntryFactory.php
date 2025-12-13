<?php

namespace Database\Factories;

use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogbookEntry>
 */
class LogbookEntryFactory extends Factory
{
    protected $model = LogbookEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'week_number' => $this->faker->numberBetween(1, 12),
            'entry_text' => $this->faker->paragraphs(2, true),
            'file_path' => null,
            'status' => 'draft',
            'supervisor_status' => null,
            'supervisor_comment' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'ai_analysis_json' => null,
            'submitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => [
            'status' => 'pending_review',
            'supervisor_status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'supervisor_status' => 'verified',
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);
    }
}
