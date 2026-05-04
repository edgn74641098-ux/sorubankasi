<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QualityQueueService
{
    /**
     * Minimum number of attempts before quality check applies
     */
    private const MINIMUM_ATTEMPTS = 30;

    /**
     * Threshold for too-easy questions: wrong_rate < 0.20
     */
    private const TOO_EASY_THRESHOLD = 0.20;

    /**
     * Threshold for too-hard questions: wrong_rate > 0.75
     */
    private const TOO_HARD_THRESHOLD = 0.75;

    /**
     * Get questions marked as too easy
     */
    public function getTooEasyQuestions(int $limit = 10): Collection
    {
        return Question::query()
            ->select(['id', 'subject_id', 'question_text', 'correct_count', 'wrong_count', 'difficulty_score'])
            ->with('subject:id,name')
            ->where('status', 'active')
            ->whereRaw('(correct_count + wrong_count) >= ?', [self::MINIMUM_ATTEMPTS])
            ->whereRaw('(wrong_count * 1.0) / (correct_count + wrong_count) < ?', [self::TOO_EASY_THRESHOLD])
            ->orderBy('wrong_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get questions marked as too hard
     */
    public function getTooHardQuestions(int $limit = 10): Collection
    {
        return Question::query()
            ->select(['id', 'subject_id', 'question_text', 'correct_count', 'wrong_count', 'difficulty_score'])
            ->with('subject:id,name')
            ->where('status', 'active')
            ->whereRaw('(correct_count + wrong_count) >= ?', [self::MINIMUM_ATTEMPTS])
            ->whereRaw('(wrong_count * 1.0) / (correct_count + wrong_count) > ?', [self::TOO_HARD_THRESHOLD])
            ->orderByDesc('wrong_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get count of too-easy questions
     */
    public function getTooEasyCount(): int
    {
        return (int) Question::query()
            ->where('status', 'active')
            ->whereRaw('(correct_count + wrong_count) >= ?', [self::MINIMUM_ATTEMPTS])
            ->whereRaw('(wrong_count * 1.0) / (correct_count + wrong_count) < ?', [self::TOO_EASY_THRESHOLD])
            ->count();
    }

    /**
     * Get count of too-hard questions
     */
    public function getTooHardCount(): int
    {
        return (int) Question::query()
            ->where('status', 'active')
            ->whereRaw('(correct_count + wrong_count) >= ?', [self::MINIMUM_ATTEMPTS])
            ->whereRaw('(wrong_count * 1.0) / (correct_count + wrong_count) > ?', [self::TOO_HARD_THRESHOLD])
            ->count();
    }

    /**
     * Calculate wrong rate for a question
     */
    public function getWrongRate(Question $question): float
    {
        $total = $question->correct_count + $question->wrong_count;
        if ($total === 0) {
            return 0.5; // Default to middle if no attempts
        }

        return round($question->wrong_count / $total, 4);
    }

    /**
     * Check if question is eligible for quality queue
     */
    public function isEligibleForQualityCheck(Question $question): bool
    {
        return ($question->correct_count + $question->wrong_count) >= self::MINIMUM_ATTEMPTS;
    }

    /**
     * Determine quality status of a question
     */
    public function getQualityStatus(Question $question): string
    {
        if (!$this->isEligibleForQualityCheck($question)) {
            return 'PENDING'; // Not enough attempts
        }

        $wrongRate = $this->getWrongRate($question);

        if ($wrongRate < self::TOO_EASY_THRESHOLD) {
            return 'TOO_EASY';
        }

        if ($wrongRate > self::TOO_HARD_THRESHOLD) {
            return 'TOO_HARD';
        }

        return 'NORMAL';
    }

    public function getMostReportedCount(): int
    {
        return (int) DB::table('questions')
            ->join('question_reports', 'question_reports.question_id', '=', 'questions.id')
            ->where('questions.status', 'active')
            ->groupBy('questions.id')
            ->havingRaw('COUNT(question_reports.id) >= 2')
            ->get(['questions.id'])
            ->count();
    }

    public function getMostReportedQuestions(int $limit = 10): Collection
    {
        return Question::query()
            ->select(['questions.id', 'questions.subject_id', 'questions.question_text', 'questions.correct_count', 'questions.wrong_count', 'questions.difficulty_score'])
            ->selectRaw('COUNT(question_reports.id) as reports_count')
            ->with('subject:id,name')
            ->join('question_reports', 'question_reports.question_id', '=', 'questions.id')
            ->where('questions.status', 'active')
            ->groupBy('questions.id', 'questions.subject_id', 'questions.question_text', 'questions.correct_count', 'questions.wrong_count', 'questions.difficulty_score')
            ->havingRaw('COUNT(question_reports.id) >= 2')
            ->orderByDesc(DB::raw('COUNT(question_reports.id)'))
            ->limit($limit)
            ->get();
    }
}
