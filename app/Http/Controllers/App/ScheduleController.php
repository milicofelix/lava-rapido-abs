<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\WashOrder;
use App\Services\WashOrders\ChangeWashOrderStatusService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

class ScheduleController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->abortUnlessScheduleIsEnabled();

        $selectedDate = $this->selectedDate((string) $request->query('date', now()->toDateString()));
        $startOfDay = $selectedDate->copy()->startOfDay();
        $endOfDay = $selectedDate->copy()->endOfDay();
        $currentLocation = TenantContext::currentLocation();
        $businessDay = $this->businessDayFor($selectedDate);

        $washOrders = TenantContext::scopeWashOrders(WashOrder::query())
            ->with(['customer', 'vehicle', 'teamMembers', 'services'])
            ->whereBetween('entered_at', [$startOfDay, $endOfDay])
            ->orderByRaw('COALESCE(estimated_completion_at, entered_at) asc')
            ->get();

        $activeStatuses = collect(WashOrder::statuses())
            ->keys()
            ->diff([WashOrder::STATUS_DELIVERED, WashOrder::STATUS_CANCELED])
            ->all();

        $delayed = $washOrders->filter(fn (WashOrder $washOrder) => in_array($washOrder->status, $activeStatuses, true)
            && $washOrder->estimated_completion_at
            && $washOrder->estimated_completion_at->isPast());

        return view('app.schedule.index', [
            'selectedDate' => $selectedDate,
            'previousDate' => $selectedDate->copy()->subDay()->toDateString(),
            'nextDate' => $selectedDate->copy()->addDay()->toDateString(),
            'washOrders' => $washOrders,
            'businessDay' => $businessDay,
            'suggestedScheduleAt' => $this->suggestedScheduleAt($selectedDate, $businessDay),
            'canCreateOnSelectedDate' => $currentLocation?->canOpenWashOrderAt($this->suggestedScheduleAt($selectedDate, $businessDay)) ?? true,
            'hourlySlots' => $this->hourlySlots($selectedDate, $businessDay, $washOrders),
            'summary' => [
                'total' => $washOrders->count(),
                'open' => $washOrders->whereIn('status', $activeStatuses)->count(),
                'delayed' => $delayed->count(),
                'delivered' => $washOrders->where('status', WashOrder::STATUS_DELIVERED)->count(),
            ],
        ]);
    }

    public function reschedule(Request $request, WashOrder $washOrder): RedirectResponse
    {
        $this->abortUnlessScheduleIsEnabled();
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'reschedule_reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'scheduled_at.required' => 'Informe a nova data e horário.',
            'scheduled_at.date' => 'Informe uma data e horário válidos.',
        ]);

        $washOrder->load(['services', 'washLocation']);
        $scheduledAt = Carbon::parse($data['scheduled_at']);

        if (! $this->canManageAppointment($washOrder)) {
            return back()->withErrors([
                'schedule' => 'Somente lavagens aguardando e sem pagamento podem ser reagendadas.',
            ])->withInput();
        }

        if ($scheduledAt->isPast()) {
            return back()->withErrors([
                'scheduled_at' => 'Escolha uma data e horário futuros para reagendar.',
            ])->withInput();
        }

        if (! ($washOrder->washLocation?->canOpenWashOrderAt($scheduledAt) ?? true)) {
            return back()->withErrors([
                'scheduled_at' => 'O novo horário está fora do expediente configurado para a unidade.',
            ])->withInput();
        }

        $previousEnteredAt = $washOrder->entered_at?->copy();
        $previousEstimatedAt = $washOrder->estimated_completion_at?->copy();
        $duration = $this->appointmentDurationMinutes($washOrder);

        $washOrder->forceFill([
            'entered_at' => $scheduledAt,
            'estimated_completion_at' => $scheduledAt->copy()->addMinutes($duration),
        ])->save();

        $reason = trim((string) ($data['reschedule_reason'] ?? ''));
        $washOrder->statusHistories()->create([
            'user_id' => $request->user()?->id,
            'from_status' => $washOrder->status,
            'to_status' => $washOrder->status,
            'notes' => 'Reagendada de '.$previousEnteredAt?->format('d/m/Y H:i').' para '.$scheduledAt->format('d/m/Y H:i').($reason !== '' ? '. Motivo: '.$reason : '.'),
        ]);

        AuditLogger::record(
            AuditLog::ACTION_WASH_ORDER_RESCHEDULED,
            ($request->user()?->name ?? 'Sistema').' reagendou a lavagem '.$washOrder->code.' para '.$scheduledAt->format('d/m/Y H:i').'.',
            $washOrder,
            [
                'previous_entered_at' => $previousEnteredAt?->toDateTimeString(),
                'previous_estimated_completion_at' => $previousEstimatedAt?->toDateTimeString(),
                'entered_at' => $scheduledAt->toDateTimeString(),
                'estimated_completion_at' => $washOrder->estimated_completion_at?->toDateTimeString(),
                'reason' => $reason,
            ],
            $request->user(),
        );

        return redirect()
            ->route('schedule.index', ['date' => $scheduledAt->toDateString()])
            ->with('status', 'Lavagem reagendada com sucesso.');
    }

    public function cancel(
        Request $request,
        WashOrder $washOrder,
        ChangeWashOrderStatusService $changeStatus,
    ): RedirectResponse {
        $this->abortUnlessScheduleIsEnabled();
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'cancel_reason.required' => 'Informe o motivo do cancelamento.',
            'cancel_reason.min' => 'Informe um motivo de cancelamento com pelo menos 5 caracteres.',
        ]);

        $selectedDate = $washOrder->entered_at?->toDateString() ?? now()->toDateString();

        try {
            $changeStatus->handle($washOrder, WashOrder::STATUS_CANCELED, $request->user(), $data['cancel_reason']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('schedule.index', ['date' => $selectedDate])
            ->with('status', 'Lavagem cancelada com sucesso.');
    }

    private function abortUnlessScheduleIsEnabled(): void
    {
        if (! AppSetting::isModuleEnabled('module_schedule')) {
            abort(403);
        }
    }

    private function selectedDate(string $date): Carbon
    {
        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    /**
     * @return array{is_open: bool, opens: string, closes: string, label: string}
     */
    private function businessDayFor(Carbon $date): array
    {
        $location = TenantContext::currentLocation();
        $hours = $location?->normalizedBusinessHours() ?? [];
        $dayKey = strtolower($date->englishDayOfWeek);
        $dayHours = $hours[$dayKey] ?? ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'];

        return [
            'is_open' => (bool) ($dayHours['is_open'] ?? false),
            'opens' => (string) ($dayHours['opens'] ?? '08:00'),
            'closes' => (string) ($dayHours['closes'] ?? '18:00'),
            'label' => $dayHours['is_open'] ?? false
                ? ($dayHours['opens'] ?? '08:00').' às '.($dayHours['closes'] ?? '18:00')
                : 'Fechado',
        ];
    }

    private function suggestedScheduleAt(Carbon $date, array $businessDay): Carbon
    {
        if (! $businessDay['is_open']) {
            return $date->copy()->setTime(8, 0);
        }

        return $date->copy()->setTimeFromTimeString($businessDay['opens']);
    }

    private function canManageAppointment(WashOrder $washOrder): bool
    {
        return $washOrder->status === WashOrder::STATUS_AWAITING
            && ! $washOrder->hasIdentifiedPayment();
    }

    private function appointmentDurationMinutes(WashOrder $washOrder): int
    {
        if ($washOrder->entered_at && $washOrder->estimated_completion_at) {
            return max(1, (int) $washOrder->entered_at->diffInMinutes($washOrder->estimated_completion_at));
        }

        $serviceMinutes = (int) $washOrder->services->sum(fn ($service) => (int) ($service->pivot->estimated_minutes ?? $service->estimated_minutes ?? 0));

        return max(30, $serviceMinutes);
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    private function hourlySlots(Carbon $date, array $businessDay, $washOrders): array
    {
        if (! $businessDay['is_open']) {
            return [];
        }

        $cursor = $date->copy()->setTimeFromTimeString($businessDay['opens']);
        $end = $date->copy()->setTimeFromTimeString($businessDay['closes']);

        if ($end->lessThanOrEqualTo($cursor)) {
            $end->addDay();
        }

        $slots = [];

        while ($cursor->lessThan($end)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addHour();

            $slots[] = [
                'label' => $slotStart->format('H:i'),
                'count' => $washOrders->filter(fn (WashOrder $washOrder) => $washOrder->entered_at
                    && $washOrder->entered_at->greaterThanOrEqualTo($slotStart)
                    && $washOrder->entered_at->lessThan($slotEnd))->count(),
            ];

            $cursor->addHour();
        }

        return $slots;
    }
}
