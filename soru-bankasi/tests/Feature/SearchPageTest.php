<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_subjects_and_questions(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Adli Bilisim',
            'slug' => 'adli-bilisim',
            'is_active' => true,
        ]);
        $hiddenSubject = Subject::query()->create([
            'name' => 'Pasif Ders',
            'slug' => 'pasif-ders',
            'is_active' => false,
        ]);

        Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Mobil cihaz incelemesinde Faraday cantasi hangi amacla kullanilir?',
            'option_a' => 'Sinyal izolasyonu',
            'option_b' => 'Kelime islem',
            'option_c' => 'Veritabani',
            'option_d' => 'Haritalama',
            'option_e' => 'Sunum',
            'correct_option' => 'A',
            'explanation_text' => 'Faraday cantasi cihaz sinyallerini izole eder.',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        Question::factory()->create([
            'subject_id' => $hiddenSubject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Faraday pasif derste gorunmemeli.',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Faraday taslak soruda gorunmemeli.',
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'Faraday']))
            ->assertOk()
            ->assertSee('Ara')
            ->assertSee('Soru Sonuclari')
            ->assertSee('Mobil cihaz incelemesinde Faraday cantasi')
            ->assertSee('Sinyal izolasyonu')
            ->assertSee('Dogru cevap')
            ->assertSee('Adli Bilisim')
            ->assertDontSee('pasif derste gorunmemeli')
            ->assertDontSee('taslak soruda gorunmemeli');
    }

    public function test_search_page_is_available_from_navigation(): void
    {
        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('search.index'), false)
            ->assertSee('Ara');
    }

    public function test_user_can_filter_search_results_by_subject(): void
    {
        $user = $this->createVerifiedUser();
        $networkSubject = Subject::query()->create([
            'name' => 'Ag Guvenligi',
            'slug' => 'ag-guvenligi',
            'is_active' => true,
        ]);
        $mobileSubject = Subject::query()->create([
            'name' => 'Mobil Adli Bilisim',
            'slug' => 'mobil-adli-bilisim',
            'is_active' => true,
        ]);

        Question::factory()->create([
            'subject_id' => $networkSubject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Sifreleme ag trafiginde nasil kullanilir?',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        Question::factory()->create([
            'subject_id' => $mobileSubject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Sifreleme mobil cihazlarda hangi veriyi korur?',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('search.index', [
                'q' => 'Sifreleme',
                'subject_id' => $mobileSubject->id,
            ]))
            ->assertOk()
            ->assertSee('Tum dersler')
            ->assertSee('Mobil Adli Bilisim')
            ->assertSee('Sifreleme mobil cihazlarda')
            ->assertDontSee('Sifreleme ag trafiginde');
    }

    public function test_subject_filter_lists_all_active_questions_when_search_is_empty_with_pagination(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Mobil Adli Bilisim',
            'slug' => 'mobil-adli-bilisim',
            'is_active' => true,
        ]);
        $otherSubject = Subject::query()->create([
            'name' => 'Ag Guvenligi',
            'slug' => 'ag-guvenligi',
            'is_active' => true,
        ]);

        foreach (range(1, 55) as $index) {
            Question::factory()->create([
                'subject_id' => $subject->id,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'question_text' => "Mobil tum soru {$index}",
                'status' => 'active',
                'approved_at' => now(),
            ]);
        }

        Question::factory()->create([
            'subject_id' => $otherSubject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Diger dersten soru gorunmemeli',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $firstPage = $this->actingAs($user)
            ->get(route('search.index', [
                'q' => '',
                'subject_id' => $subject->id,
            ]));

        $firstPage
            ->assertOk()
            ->assertSee('Secili dersteki tum aktif sorular')
            ->assertDontSee('Diger dersten soru gorunmemeli')
            ->assertSee('?subject_id=' . $subject->id . '&amp;page=2#question-results', false);

        $this->assertSame(50, substr_count($firstPage->getContent(), 'class="sb-stat-card"'));

        $secondPage = $this->actingAs($user)
            ->get(route('search.index', [
                'q' => '',
                'subject_id' => $subject->id,
                'page' => 2,
            ]));

        $secondPage
            ->assertOk()
            ->assertSee('Secili dersteki tum aktif sorular');
        $this->assertSame(5, substr_count($secondPage->getContent(), 'class="sb-stat-card"'));
    }

    public function test_user_can_download_filtered_search_results_as_pdf(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Mobil Adli Bilisim',
            'slug' => 'mobil-adli-bilisim',
            'is_active' => true,
        ]);

        Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'question_text' => 'Faraday cantasi ne ise yarar?',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('search.pdf', [
                'q' => 'Faraday',
                'subject_id' => $subject->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    private function createVerifiedUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'user']);

        return User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }
}
