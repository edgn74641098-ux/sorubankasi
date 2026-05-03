<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_confirm_csv_import(): void
    {
        $admin = $this->createAdmin();
        Subject::query()->create([
            'name' => 'Matematik',
            'slug' => 'matematik',
            'is_active' => true,
        ]);

        $file = new UploadedFile(
            base_path('tests/Fixtures/import-questions.csv'),
            'import.csv',
            'text/csv',
            null,
            true
        );

        $previewResponse = $this->actingAs($admin)->post(route('admin.imports.store'), [
            'file' => $file,
        ]);

        $previewResponse->assertRedirect();
        $this->assertDatabaseCount('question_import_batches', 1);
        $this->assertDatabaseCount('question_import_rows', 1);

        $batchId = \App\Models\QuestionImportBatch::query()->value('id');
        $rowId = \App\Models\QuestionImportRow::query()->value('id');

        $confirmResponse = $this->actingAs($admin)->post(route('admin.imports.confirm', $batchId), [
            'actions' => [
                $rowId => 'insert',
            ],
        ]);

        $confirmResponse->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'question_text' => '2+2 kactir?',
            'source_type' => 'import',
            'status' => 'active',
        ]);
    }

    private function createAdmin(): User
    {
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

        return User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
    }
}
