<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'created_by' => User::factory(),
            'approved_by' => User::factory(),
            'source_type' => 'admin',
            'question_text' => fake()->sentence(10),
            'option_a' => fake()->sentence(),
            'option_b' => fake()->sentence(),
            'option_c' => fake()->sentence(),
            'option_d' => fake()->sentence(),
            'option_e' => fake()->sentence(),
            'correct_option' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
            'explanation_text' => fake()->sentence(12),
            'difficulty_score' => fake()->numberBetween(1, 10),
            'correct_count' => 0,
            'wrong_count' => 0,
            'status' => 'draft',
            'approved_at' => null,
            'current_version' => 1,
        ];
    }
}