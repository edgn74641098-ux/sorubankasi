<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Subject::class);

        $status = $request->string('status')->value();

        $subjects = Subject::query()
            ->withCount('questions')
            ->whereNull('archived_at')
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
            'archived_at' => null,
            'purge_after' => null,
        ]);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', 'Ders basariyla guncellendi.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        $archiveAt = now();
        $purgeAfter = $this->purgeAfter($archiveAt);

        $subject->update([
            'is_active' => false,
            'archived_at' => $archiveAt,
            'purge_after' => $purgeAfter,
        ]);

        $subject->questions()
            ->whereNull('archived_at')
            ->update([
                'status' => 'archived',
                'approved_by' => null,
                'approved_at' => null,
                'archived_at' => $archiveAt,
                'purge_after' => $purgeAfter,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.archive.index')
            ->with('success', $this->archiveMessage('Ders arsive tasindi.'));
    }

    private function purgeAfter($archiveAt)
    {
        if (! $this->settingsService->getBool('archive_auto_prune_enabled', true)) {
            return null;
        }

        return $archiveAt->copy()->addDays($this->settingsService->getInt('archive_retention_days', 7));
    }

    private function archiveMessage(string $prefix): string
    {
        if (! $this->settingsService->getBool('archive_auto_prune_enabled', true)) {
            return $prefix . ' Otomatik silme kapali.';
        }

        return $prefix . ' ' . $this->settingsService->getInt('archive_retention_days', 7) . ' gun sonra otomatik silme icin isaretlendi.';
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
