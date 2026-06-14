<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'action' => trim((string) $request->query('action')),
            'user_id' => trim((string) $request->query('user_id')),
            'search' => trim((string) $request->query('search')),
            'start' => $request->query('start', now()->subDays(30)->toDateString()),
            'end' => $request->query('end', now()->toDateString()),
        ];

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
