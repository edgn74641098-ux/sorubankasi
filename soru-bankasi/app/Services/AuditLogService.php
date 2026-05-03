<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        ?User $actor,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $reason = null,
        ?Request $request = null
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
