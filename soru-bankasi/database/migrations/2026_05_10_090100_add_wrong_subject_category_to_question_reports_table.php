<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE question_reports
                MODIFY category ENUM('WRONG_ANSWER','UNCLEAR_WORDING','TYPO','WRONG_SUBJECT','OTHER')
                NOT NULL DEFAULT 'OTHER'
            ");
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('ALTER TABLE question_reports RENAME TO question_reports_old');
            DB::statement("
                CREATE TABLE question_reports (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INTEGER NOT NULL,
                    question_id INTEGER NOT NULL,
                    category TEXT NOT NULL CHECK (category IN ('WRONG_ANSWER','UNCLEAR_WORDING','TYPO','WRONG_SUBJECT','OTHER')) DEFAULT 'OTHER',
                    note TEXT NULL,
                    status TEXT NOT NULL CHECK (status IN ('pending','approved','rejected','resolved')) DEFAULT 'pending',
                    reviewed_by INTEGER NULL,
                    reviewed_at DATETIME NULL,
                    review_note TEXT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    suggested_correct_option VARCHAR(1) NULL,
                    user_message TEXT NULL,
                    suggested_subject_id INTEGER NULL,
                    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE,
                    FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY(suggested_subject_id) REFERENCES subjects(id) ON DELETE SET NULL
                )
            ");
            DB::statement("
                INSERT INTO question_reports (
                    id, user_id, question_id, category, note, status, reviewed_by, reviewed_at, review_note,
                    created_at, updated_at, suggested_correct_option, user_message, suggested_subject_id
                )
                SELECT
                    id, user_id, question_id, category, note, status, reviewed_by, reviewed_at, review_note,
                    created_at, updated_at, suggested_correct_option, user_message, suggested_subject_id
                FROM question_reports_old
            ");
            DB::statement('DROP TABLE question_reports_old');
            DB::statement('CREATE INDEX question_reports_question_id_status_index ON question_reports(question_id, status)');
            DB::statement('CREATE INDEX question_reports_user_id_created_at_index ON question_reports(user_id, created_at)');
            DB::statement('CREATE INDEX question_reports_reviewed_by_reviewed_at_index ON question_reports(reviewed_by, reviewed_at)');
            DB::statement('CREATE INDEX question_reports_suggested_subject_id_status_index ON question_reports(suggested_subject_id, status)');
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE question_reports
                MODIFY category ENUM('WRONG_ANSWER','UNCLEAR_WORDING','TYPO','OTHER')
                NOT NULL DEFAULT 'OTHER'
            ");
        }
    }
};
