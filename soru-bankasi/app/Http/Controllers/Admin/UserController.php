<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('role:id,name')
            ->withCount(['tests', 'submittedQuestions'])
            ->when($request->filled('role_id'), fn ($query) => $query->where('role_id', $request->integer('role_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.$request->string('search')->value().'%';
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'filters' => [
                'role_id' => $request->input('role_id'),
                'search' => $request->input('search'),
            ],
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')],
        ]);

        $oldRoleId = $user->role_id;
        $newRoleId = (int) $validated['role_id'];

        if ($request->user()->is($user) && $oldRoleId !== $newRoleId && $user->isAdmin()) {
            $newRole = Role::query()->findOrFail($newRoleId);
            if ($newRole->name !== 'admin') {
                return back()->withErrors([
                    'role_id' => 'Kendi admin yetkinizi kaldiramazsiniz.',
                ]);
            }
        }

        if ($oldRoleId !== $newRoleId) {
            $user->update(['role_id' => $newRoleId]);

            $this->auditLog->record(
                $request->user(),
                'user.role_updated',
                'users',
                $user->id,
                ['role_id' => $oldRoleId],
                ['role_id' => $newRoleId],
                'Kullanici rolu guncellendi.',
                $request
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanici rolu guncellendi.');
    }
}
