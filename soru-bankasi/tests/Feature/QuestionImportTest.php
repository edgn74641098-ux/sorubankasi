<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportRow;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

        $row = \App\Models\QuestionImportRow::query()->find($rowId);

        $this->assertNotNull($row);
        $this->assertIsArray($row->payload_json);
        $this->assertEquals('2+2 kactir?', $row->payload_json['question_text']);

        $this->actingAs($admin)
            ->get(route('admin.imports.show', $batchId))
            ->assertOk()
            ->assertSee('Önizleme Satırları', false)
            ->assertSee('2+2 kactir?', false)
            ->assertSee('Soru (dosyadan)', false);

        Cache::forget('import.preview.'.$batchId);

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

        $this->actingAs($admin)
            ->get(route('admin.imports.show', $batchId))
            ->assertOk()
            ->assertSee('İşlenen satırlar', false)
            ->assertSee('2+2 kactir?', false)
            ->assertSee('inserted', false);
    }

    public function test_admin_can_see_import_statuses_and_delete_import_batch(): void
    {
        $admin = $this->createAdmin();

        $preview = QuestionImportBatch::query()->create([
            'uploaded_by' => $admin->id,
            'file_name' => 'bekleyen.csv',
            'file_type' => 'CSV',
            'total_rows' => 2,
            'success_rows' => 2,
            'failed_rows' => 0,
            'status' => 'preview',
        ]);

        QuestionImportRow::query()->create([
            'batch_id' => $preview->id,
            'question_hash' => 'pending-hash',
            'action' => 'pending',
            'payload_json' => ['question_text' => 'Bekleyen soru'],
        ]);

        $completed = QuestionImportBatch::query()->create([
            'uploaded_by' => $admin->id,
            'file_name' => 'onaylanan.csv',
            'file_type' => 'CSV',
            'total_rows' => 1,
            'success_rows' => 1,
            'failed_rows' => 0,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        QuestionImportRow::query()->create([
            'batch_id' => $completed->id,
            'question_hash' => 'inserted-hash',
            'action' => 'inserted',
            'payload_json' => ['question_text' => 'Uygulanan soru'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Bekliyor')
            ->assertSee('Onaylandi')
            ->assertSee('bekleyen.csv')
            ->assertSee('onaylanan.csv')
            ->assertSee(route('admin.imports.destroy', $preview), false);

        $this->actingAs($admin)
            ->delete(route('admin.imports.destroy', $preview))
            ->assertRedirect(route('admin.imports.index'));

        $this->assertDatabaseMissing('question_import_batches', [
            'id' => $preview->id,
        ]);

        $this->assertDatabaseMissing('question_import_rows', [
            'batch_id' => $preview->id,
        ]);

        $this->assertDatabaseHas('question_import_batches', [
            'id' => $completed->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'import.deleted',
            'entity_type' => 'question_import_batches',
        ]);
    }

    public function test_admin_can_import_csv_without_explanation_column(): void
    {
        $admin = $this->createAdmin();
        Subject::query()->create([
            'name' => 'Matematik',
            'slug' => 'matematik',
            'is_active' => true,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'questions-without-explanation');
        file_put_contents($path, implode("\n", [
            'subject,question_text,option_a,option_b,option_c,option_d,option_e,correct_option',
            'Matematik,Aciklamasiz soru yuklenebilir mi?,Evet,Hayir,Belki,Asla,Farketmez,A',
        ]));

        $file = new UploadedFile($path, 'import-no-explanation.csv', 'text/csv', null, true);

        $this->actingAs($admin)
            ->post(route('admin.imports.store'), ['file' => $file])
            ->assertRedirect();

        $row = QuestionImportRow::query()->first();
        $this->assertSame('', $row->payload_json['explanation_text']);
    }

    public function test_import_reactivates_archived_subject_and_inserts_when_old_question_is_archived(): void
    {
        $admin = $this->createAdmin();
        $subject = Subject::query()->create([
            'name' => 'Siber Guvenlik ve Savunma Politikalari',
            'slug' => 'siber-guvenlik-ve-savunma-politikalari',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => null,
            'question_text' => 'Politika sorusu tekrar yuklenebilir mi?',
            'status' => 'archived',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'archived-subject-import');
        file_put_contents($path, implode("\n", [
            'subject,question_text,option_a,option_b,option_c,option_d,option_e,correct_option,explanation_text',
            'Siber Guvenlik ve Savunma Politikalari,Politika sorusu tekrar yuklenebilir mi?,A,B,C,D,E,A,Aciklama',
        ]));

        $file = new UploadedFile($path, 'archived-subject-import.csv', 'text/csv', null, true);

        $this->actingAs($admin)
            ->post(route('admin.imports.store'), ['file' => $file])
            ->assertRedirect();

        $row = QuestionImportRow::query()->first();
        $this->assertNull($row->matched_question_id);

        $this->actingAs($admin)
            ->post(route('admin.imports.confirm', QuestionImportBatch::query()->value('id')), [
                'actions' => [
                    $row->id => 'insert',
                ],
            ])
            ->assertRedirect();

        $subject->refresh();
        $this->assertTrue($subject->is_active);
        $this->assertNull($subject->archived_at);
        $this->assertSame(1, Question::query()
            ->where('subject_id', $subject->id)
            ->where('status', 'active')
            ->where('question_text', 'Politika sorusu tekrar yuklenebilir mi?')
            ->count());
        $this->assertSame(1, Question::query()
            ->where('subject_id', $subject->id)
            ->where('status', 'archived')
            ->where('question_text', 'Politika sorusu tekrar yuklenebilir mi?')
            ->count());
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
