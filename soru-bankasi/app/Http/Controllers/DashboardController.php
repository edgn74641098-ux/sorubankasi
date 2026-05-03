<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'activeTest' => Test::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('started_at')
                ->first(),
            'recentTests' => Test::query()
                ->with('subject:id,name')
                ->where('user_id', $user->id)
                ->where('status', 'finished')
                ->latest('ended_at')
                ->limit(5)
                ->get(),
            'totalScore' => (int) $user->total_score,
        ]);
    }
}
