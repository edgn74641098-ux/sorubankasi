<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'test_feedback_mode' => 'DELAYED_FEEDBACK',
            'registration_open' => true,
            'inactive_login_message' => 'Kullanici hesabiniz pasif duruma getirilmistir. Lutfen yonetici ile iletisime gecin.',
            'email_verification_required' => false,
            'google_auth_enabled' => false,
            'password_reset_enabled' => false,
            'daily_test_limit' => 20,
            'daily_question_limit' => 20,
            'login_rate_limit' => 5,
            'login_lockout_duration' => 900,
            'minimum_leaderboard_tests' => 3,
            'correct_answer_points' => 5,
            'wrong_answer_penalty_enabled' => false,
            'wrong_answer_penalty_points' => 0,
            'blank_answer_points' => 0,
            'leaderboard_global_limit' => 20,
            'leaderboard_weekly_limit' => 5,
            'leaderboard_form_limit' => 5,
            'question_report_accept_message' => 'Itiraziniz kabul edildi. Katkiniz icin tesekkur ederiz. Sorunun dogru cevabi {old_answer} yerine {new_answer} olarak guncellendi.',
            'user_submissions_enabled' => true,
            'submission_approval_reward' => 10,
            'submission_rejection_note_required' => true,
            'archive_retention_days' => 7,
            'archive_auto_prune_enabled' => true,
            'maintenance_mode' => false,
            'backup_mode' => 'manual',
        ];

        foreach ($settings as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $setting->setTypedValue($value);
            $setting->save();
        }
    }
}
