<?php

namespace App\Services;

use App\Models\Test;
use App\Models\TestItem;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnswerEvaluationService
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {
    }

    public function evaluate(Test $test, TestItem $testItem, ?string $answer): array
    {
        if ($test->status !== 'active') {
            throw $this->httpException(422, 'inactive_test', 'Bu test artık aktif değil.');
        }

        $mode = $test->feedback_mode ?: $this->settingsService->getString('test_feedback_mode', 'DELAYED_FEEDBACK');
        $normalizedAnswer = $answer ? strtoupper($answer) : null;

        if ($normalizedAnswer !== null && ! in_array($normalizedAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
            throw $this->httpException(422, 'invalid_answer', 'Geçersiz cevap seçeneği.');
        }

        $payload = [
            'saved' => true,
            'feedback_mode' => $mode,
            'locked' => false,
            'is_correct' => null,
            'explanation' => null,
        ];

        if (($mode === 'INSTANT_FEEDBACK_LOCKED' || $mode === 'NO_FEEDBACK') && $testItem->answered_at !== null) {
            throw $this->httpException(422, 'answer_locked', 'Bu soru için cevap kilitlendi.');
        }

        $update = [
            'user_answer' => $normalizedAnswer,
            'answered_at' => Carbon::now(),
        ];

        if ($mode === 'INSTANT_FEEDBACK_LOCKED') {
            $isCorrect = $normalizedAnswer !== null && $normalizedAnswer === $testItem->question->correct_option;
            $update['is_correct'] = $isCorrect;

            $payload['locked'] = true;
            $payload['is_correct'] = $isCorrect;
            $payload['explanation'] = $testItem->question->explanation_text;
        } elseif ($mode === 'NO_FEEDBACK') {
            // NO_FEEDBACK modunda cevap kilitlenir ama geri bildirim gösterilmez
            $isCorrect = $normalizedAnswer !== null && $normalizedAnswer === $testItem->question->correct_option;
            $update['is_correct'] = $isCorrect;
            
            $payload['locked'] = true;
            // NO_FEEDBACK: is_correct ve explanation gösterilmez
            $payload['is_correct'] = null;
            $payload['explanation'] = null;
        }

        $testItem->update($update);

        return $payload;
    }

    private function httpException(int $status, string $error, string $message): HttpException
    {
        return new HttpException($status, json_encode([
            'error' => $error,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE));
    }
}