<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'stats' => [
                'subjects' => $this->safeCount('subjects', fn () => Subject::query()->where('is_active', true)),
                'questions' => $this->safeCount('questions', fn () => Question::query()->where('status', 'active')),
                'tests' => $this->safeCount('tests', fn () => Test::query()->where('status', 'finished')),
                'users' => $this->safeCount('users', fn () => User::query()),
            ],
        ]);
    }

    private function safeCount(string $table, callable $query): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        /** @var Builder $builder */
        $builder = $query();

        return $builder->count();
    }
}
