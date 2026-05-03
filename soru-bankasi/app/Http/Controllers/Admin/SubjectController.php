<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Subject::class);

        $status = $request->string('status')->value();

        $subjects = Subject::query()
            ->withCount('questions')
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Subject::class);

        return view('admin.subjects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Subject::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subjects,name'],
        ]);

        Subject::query()->create([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', 'Ders basariyla olusturuldu.');
    }

    public function edit(Subject $subject): View
    {
        $this->authorize('update', $subject);

        return view('admin.subjects.edit', [
            'subject' => $subject,
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subjects,name,' . $subject->id],
            'is_active' => ['required', 'boolean'],
        ]);

        $subject->update([
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name'], $subject->id),
            'is_active' => (bool) $validated['is_active'],
        ]);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', 'Ders basariyla guncellendi.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        $subject->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', 'Ders pasif duruma alindi.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slugBase = $baseSlug !== '' ? $baseSlug : 'ders';
        $slug = $slugBase;
        $counter = 1;

        while (
            Subject::query()
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $slugBase . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}