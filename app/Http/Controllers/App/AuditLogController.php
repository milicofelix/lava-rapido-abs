<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $validated = $request->validate([
            'action' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'start' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'end' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today, 'after_or_equal:start'],
        ]);

        $filters = [
            'action' => trim((string) ($validated['action'] ?? '')),
            'user_id' => trim((string) ($validated['user_id'] ?? '')),
            'search' => trim((string) ($validated['search'] ?? '')),
            'start' => $validated['start'] ?? $today,
            'end' => $validated['end'] ?? $today,
        ];

        if (Carbon::parse($filters['start'])->isAfter(Carbon::parse($filters['end']))) {
            return back()
                ->withErrors(['end' => 'A data final deve ser igual ou posterior a data inicial.'])
                ->withInput();
        }

        $logs = TenantContext::scopeByColumn(AuditLog::query())
            ->with(['user'])
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('description', 'like', '%'.$filters['search'].'%')
                        ->orWhere('subject_label', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['start'], fn ($query) => $query->where('created_at', '>=', $filters['start'].' 00:00:00'))
            ->when($filters['end'], fn ($query) => $query->where('created_at', '<=', $filters['end'].' 23:59:59'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('app.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => AuditLog::actions(),
            'users' => TenantContext::scopeUsers(User::query())->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
