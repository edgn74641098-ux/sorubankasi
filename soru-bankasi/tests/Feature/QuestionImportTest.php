<?php

namespace Tests\Feature;

use App\Models\Role;
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

    private function createAdmin(): User
    {
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

        return User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
    }
}
