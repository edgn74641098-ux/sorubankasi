<?php

namespace App\Services;

use App\Models\Question;

class DifficultyCalculationService
{
    /**
     * Smoothing constants for Bayesian estimation
     */
    private const SMOOTHING_WRONG_COUNT = 3;
    private const SMOOTHING_TOTAL_ATTEMPTS = 6;

    /**
     * Difficulty score bounds
     */
    private const MIN_DIFFICULTY = 1.0;
    private const MAX_DIFFICULTY = 10.0;

    /**
     * Calculate difficulty score using Bayesian smoothing
     * 
     * Formula:
     *   smoothed_wrong_rate = (wrong_count + 3) / (attempt_count + 6)
     *   difficulty_score = CLAMP(1.0, 10.0, ROUND(1 + 9 * smoothed_wrong_rate, 1))
     * 
     * Higher wrong_rate = harder question = higher difficulty score
     */
    public function calculateDifficultyScore(Question $question): float
    {
        $totalAttempts = $question->correct_count + $question->wrong_count;
        
        // Apply Bayesian smoothing
        $smoothedWrongRate = ($question->wrong_count + self::SMOOTHING_WRONG_COUNT) /
                            ($totalAttempts + self::SMOOTHING_TOTAL_ATTEMPTS);
        
        // Scale to 1.0-10.0 range
        $difficulty = 1.0 + (9.0 * $smoothedWrongRate);
        
        // Clamp to bounds and round to 1 decimal
        return round(max(self::MIN_DIFFICULTY, min(self::MAX_DIFFICULTY, $difficulty)), 1);
    }

    /**
     * Update question's difficulty score
     */
    public function updateDifficultyScore(Question $question): float
    {
        $question->refresh();

        $newScore = $this->calculateDifficultyScore($question);
        $question->update(['difficulty_score' => $newScore]);

        return $newScore;
    }

    /**
     * Get difficulty label for UI display
     */
    public function getDifficultyLabel(float $score): string
    {
        return match (true) {
            $score < 2.0 => 'Çok Kolay',
            $score < 4.0 => 'Kolay',
            $score < 6.0 => 'Orta',
            $score < 8.0 => 'Zor',
            default => 'Çok Zor',
        };
    }

    /**
     * Get difficulty color for UI display
     */
    public function getDifficultyColor(float $score): string
    {
        return match (true) {
            $score < 2.0 => 'success',
            $score < 4.0 => 'info',
            $score < 6.0 => 'secondary',
            $score < 8.0 => 'warning',
            default => 'danger',
        };
    }
}
