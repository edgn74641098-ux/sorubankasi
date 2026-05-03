<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->value().'%'))
            ->when($request->filled('entity_type'), fn ($query) => $query->where('entity_type', $request->string('entity_type')->value()))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->input('action'),
                'entity_type' => $request->input('entity_type'),
            ],
        ]);
    }
}
