<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QuestionReport;
use App\Models\Role;
use App\Models\Test;
use App\Models\User;
use App\Models\UserSubmittedQuestion;
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
        $roles = Role::query()->orderBy('name')->get();
        $roleCounts = User::query()
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.name, COUNT(*) as aggregate')
            ->groupBy('roles.name')
            ->pluck('aggregate', 'roles.name');

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'passive' => User::query()->where('is_active', false)->count(),
            'today' => User::query()->whereDate('created_at', today())->count(),
            'active_7' => User::query()
                ->whereHas('tests', fn ($query) => $query->where('status', 'finished')->where('ended_at', '>=', now()->subDays(7)))
                ->count(),
            'unverified' => User::query()->whereNull('email_verified_at')->count(),
            'admins' => (int) ($roleCounts['admin'] ?? 0),
            'editors' => (int) ($roleCounts['editor'] ?? 0),
            'regular_users' => (int) ($roleCounts['user'] ?? 0),
        ];

        $users = User::query()
            ->with('role:id,name')
            ->withCount([
                'tests',
                'tests as finished_tests_count' => fn ($query) => $query->where('status', 'finished'),
                'submittedQuestions',
                'submittedQuestions as approved_submissions_count' => fn ($query) => $query->where('status', 'approved'),
                'submittedQuestions as rejected_submissions_count' => fn ($query) => $query->where('status', 'rejected'),
                'questionReports',
                'questionReports as approved_reports_count' => fn ($query) => $query->where('status', 'approved'),
                'questionReports as rejected_reports_count' => fn ($query) => $query->where('status', 'rejected'),
            ])
            ->withSum(['tests as correct_total' => fn ($query) => $query->where('status', 'finished')], 'correct_count')
            ->withSum(['tests as wrong_total' => fn ($query) => $query->where('status', 'finished')], 'wrong_count')
            ->withSum(['tests as question_total' => fn ($query) => $query->where('status', 'finished')], 'question_count')
            ->addSelect([
                'last_test_at' => Test::query()
                    ->select('ended_at')
                    ->whereColumn('tests.user_id', 'users.id')
                    ->where('status', 'finished')
                    ->latest('ended_at')
                    ->limit(1),
                'last_login_at' => AuditLog::query()
                    ->select('created_at')
                    ->whereColumn('audit_logs.actor_id', 'users.id')
                    ->where('action', 'auth.login')
                    ->latest('created_at')
                    ->limit(1),
                'last_login_ip' => AuditLog::query()
                    ->select('ip_address')
                    ->whereColumn('audit_logs.actor_id', 'users.id')
                    ->where('action', 'auth.login')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->when($request->filled('role_id'), fn ($query) => $query->where('role_id', $request->integer('role_id')))
            ->when($request->filled('email_status'), function ($query) use ($request): void {
                if ($request->input('email_status') === 'verified') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->input('email_status') === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when($request->filled('account_status'), function ($query) use ($request): void {
                if ($request->input('account_status') === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->input('account_status') === 'passive') {
                    $query->where('is_active', false);
                }
            })
            ->when($request->filled('activity'), function ($query) use ($request): void {
                match ($request->input('activity')) {
                    'active_7' => $query->whereHas('tests', fn ($nested) => $nested
                        ->where('status', 'finished')
                        ->where('ended_at', '>=', now()->subDays(7))),
                    'inactive_30' => $query->whereDoesntHave('tests', fn ($nested) => $nested
                        ->where('status', 'finished')
                        ->where('ended_at', '>=', now()->subDays(30))),
                    'no_tests' => $query->whereDoesntHave('tests', fn ($nested) => $nested->where('status', 'finished')),
                    default => null,
                };
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.$request->string('search')->value().'%';
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $userIds = $users->getCollection()->pluck('id');
        $recentAuditLogs = AuditLog::query()
            ->whereIn('actor_id', $userIds)
            ->latest('created_at')
            ->limit(max(1, $userIds->count() * 5))
            ->get()
            ->groupBy('actor_id');
        $recentLoginIps = AuditLog::query()
            ->whereIn('actor_id', $userIds)
            ->where('action', 'auth.login')
            ->whereNotNull('ip_address')
            ->latest('created_at')
            ->latest('id')
            ->limit(max(1, $userIds->count() * 10))
            ->get()
            ->groupBy('actor_id')
            ->map(fn ($logs) => $logs
                ->unique('ip_address')
                ->take(5)
                ->values());
        $userReports = QuestionReport::query()
            ->with([
                'question:id,subject_id,question_text,option_a,option_b,option_c,option_d,option_e,correct_option,status',
                'question.subject:id,name',
                'reviewedBy:id,name,email',
            ])
            ->whereIn('user_id', $userIds)
            ->latest('created_at')
            ->get()
            ->groupBy('user_id');
        $userSubmissions = UserSubmittedQuestion::query()
            ->with([
                'subject:id,name',
                'reviewedBy:id,name,email',
                'approvedQuestion:id,question_text,correct_option,status',
            ])
            ->whereIn('user_id', $userIds)
            ->latest('created_at')
            ->get()
            ->groupBy('user_id');

        $users->getCollection()->each(function (User $user) use ($recentAuditLogs, $recentLoginIps, $userReports, $userSubmissions): void {
            $user->recent_audit_logs = $recentAuditLogs->get($user->id, collect())->take(5);
            $user->recent_login_ips = $recentLoginIps->get($user->id, collect());
            $user->admin_question_reports = $userReports->get($user->id, collect());
            $user->admin_submitted_questions = $userSubmissions->get($user->id, collect());
        });

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats,
            'filters' => [
                'role_id' => $request->input('role_id'),
                'email_status' => $request->input('email_status'),
                'account_status' => $request->input('account_status'),
                'activity' => $request->input('activity'),
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

        $newRole = Role::query()->findOrFail($newRoleId);
        if ($oldRoleId !== $newRoleId && $user->isAdmin() && $newRole->name !== 'admin') {
            $adminCount = User::query()
                ->whereHas('role', fn ($query) => $query->where('name', 'admin'))
                ->count();

            if ($adminCount <= 1) {
                return back()->withErrors([
                    'role_id' => 'Sistemdeki son admin kullanicisinin yetkisi kaldirilamaz.',
                ]);
            }
        }

        if ($oldRoleId !== $newRoleId) {
            $user->update(['role_id' => $newRoleId]);

            $oldRole = Role::query()->find($oldRoleId);
            $this->auditLog->record(
                $request->user(),
                'user.role_updated',
                'users',
                $user->id,
                ['role_id' => $oldRoleId, 'role' => $oldRole?->name],
                ['role_id' => $newRoleId, 'role' => $newRole->name],
                "Kullanici rolu {$oldRole?->name} rolunden {$newRole->name} rolune guncellendi.",
                $request
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanici rolu guncellendi.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $newStatus = (bool) $validated['is_active'];

        if ($request->user()->is($user) && ! $newStatus) {
            return back()->withErrors([
                'is_active' => 'Kendi hesabinizi pasif duruma alamazsiniz.',
            ]);
        }

        if ($user->isAdmin() && ! $newStatus && $this->activeAdminCount() <= 1) {
            return back()->withErrors([
                'is_active' => 'Sistemdeki son aktif admin pasif duruma alinamaz.',
            ]);
        }

        if ($user->is_active !== $newStatus) {
            $oldStatus = $user->is_active;
            $user->update(['is_active' => $newStatus]);

            $this->auditLog->record(
                $request->user(),
                'user.status_updated',
                'users',
                $user->id,
                ['is_active' => $oldStatus],
                ['is_active' => $newStatus],
                $newStatus ? 'Kullanici hesabi aktif edildi.' : 'Kullanici hesabi pasif duruma alindi.',
                $request
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $newStatus ? 'Kullanici aktif edildi.' : 'Kullanici pasif duruma alindi.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors([
                'user' => 'Admin yetkili kullanicilar silinemez.',
            ]);
        }

        if ($request->user()->is($user)) {
            return back()->withErrors([
                'user' => 'Kendi hesabinizi yonetim panelinden silemezsiniz.',
            ]);
        }

        $this->auditLog->record(
            $request->user(),
            'user.deleted',
            'users',
            $user->id,
            ['email' => $user->email, 'role' => $user->role?->name],
            ['deleted_at' => now()->toIso8601String()],
            'Kullanici hesabi silindi.',
            $request
        );

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanici silindi.');
    }

    private function activeAdminCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'admin'))
            ->count();
    }
}
