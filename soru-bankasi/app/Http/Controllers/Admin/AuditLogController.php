<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()
            ->with('actor:id,name,email,role_id')
            ->where(function ($query): void {
                $query
                    ->whereNotIn('action', ['auth.login', 'auth.logout'])
                    ->orWhereHas('actor.role', fn ($roleQuery) => $roleQuery->whereIn('name', ['admin', 'editor']));
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->value().'%'))
            ->when($request->filled('entity_type'), fn ($query) => $query->where('entity_type', $request->string('entity_type')->value()))
            ->when($request->filled('actor_id'), fn ($query) => $query->where('actor_id', $request->integer('actor_id')))
            ->when($request->filled('ip_address'), fn ($query) => $query->where('ip_address', 'like', '%'.$request->string('ip_address')->value().'%'))
            ->when($request->filled('severity'), function ($query) use ($request): void {
                $actions = $this->severityActions($request->string('severity')->value());
                if ($actions !== []) {
                    $query->whereIn('action', $actions);
                }
            })
            ->when($request->filled('date_from'), fn ($query) => $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay()));

        $logs = (clone $query)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $today = AuditLog::query()->where('created_at', '>=', now()->startOfDay())->count();
        $criticalToday = AuditLog::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->whereIn('action', $this->severityActions('critical'))
            ->count();
        $failedLoginsToday = AuditLog::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->whereIn('action', ['auth.login_failed', 'auth.captcha_failed', 'auth.login_blocked_passive'])
            ->count();
        $settingsChangesWeek = AuditLog::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('action', 'settings.updated')
            ->count();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->input('action'),
                'entity_type' => $request->input('entity_type'),
                'actor_id' => $request->input('actor_id'),
                'ip_address' => $request->input('ip_address'),
                'severity' => $request->input('severity'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            'stats' => [
                'today' => $today,
                'critical_today' => $criticalToday,
                'failed_logins_today' => $failedLoginsToday,
                'settings_changes_week' => $settingsChangesWeek,
            ],
            'entityTypes' => AuditLog::query()->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type'),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'actors' => User::query()->whereIn('id', AuditLog::query()->select('actor_id')->whereNotNull('actor_id'))->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    private function severityActions(string $severity): array
    {
        return match ($severity) {
            'critical' => [
                'settings.updated',
                'user.role_updated',
                'user.status_updated',
                'user.deleted',
                'question.rollback',
                'question.archived_bulk',
                'archive.subject_removed',
                'archive.subject_removed_bulk',
                'archive.question_removed',
                'archive.question_removed_bulk',
                'archive.subject_restored_bulk',
                'archive.question_restored_bulk',
            ],
            'security' => [
                'auth.login',
                'auth.logout',
                'auth.login_failed',
                'auth.captcha_failed',
                'auth.login_blocked_passive',
                'auth.registered',
                'auth.registration_captcha_failed',
            ],
            'content' => [
                'subject.created',
                'subject.updated',
                'subject.archived',
                'question.created',
                'question.updated',
                'question.activated',
                'question.activated_bulk',
                'question.archived',
                'question.archived_bulk',
                'archive.subject_removed',
                'archive.subject_removed_bulk',
                'archive.question_removed',
                'archive.question_removed_bulk',
                'question.reported',
                'question_report.approved',
                'question_report.rejected',
                'user_submission.approved',
                'user_submission.rejected',
                'user_submission.approval_revoked',
                'import.deleted',
            ],
            default => [],
        };
    }
}
