<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoyaltyProgram;
use App\Services\Loyalty\EvaluateLoyaltyProgramService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProcessLoyaltyCouponsController extends Controller
{
    public function __invoke(Request $request, EvaluateLoyaltyProgramService $loyalty): RedirectResponse
    {
        $program = LoyaltyProgram::query()
            ->with(['qualifyingService', 'rewardService'])
            ->where('wash_location_id', TenantContext::currentLocationId())
            ->where('is_active', true)
            ->first();

        if (! $program) {
            throw ValidationException::withMessages([
                'loyalty_program' => 'Ative o programa de fidelidade antes de processar cupons pendentes.',
            ]);
        }

        $created = $loyalty->handleEligibleCustomers($program);

        AuditLogger::record(
            AuditLog::ACTION_LOYALTY_COUPONS_PROCESSED,
            ($request->user()?->name ?? 'Sistema').' processou cupons pendentes de fidelidade.',
            null,
            ['created_coupons' => $created],
            $request->user(),
        );

        return redirect()
            ->route('loyalty-reports.index')
            ->with('status', $created === 1
                ? '1 cupom pendente foi gerado.'
                : $created.' cupons pendentes foram gerados.');
    }
}
