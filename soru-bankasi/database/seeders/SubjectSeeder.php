<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            'Matematik',
            'Türkçe',
            'Fen Bilimleri',
            'Sosyal Bilgiler',
        ];

        foreach ($subjects as $name) {
            Subject::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}

