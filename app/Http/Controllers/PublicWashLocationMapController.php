<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PublicWashLocationMapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = trim((string) $request->query('status'));
        $search = trim((string) $request->query('q'));
        $onlyOpen = $request->boolean('only_open');

        $locations = WashLocation::query()
            ->where('public_visible', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereIn('account_status', [WashLocation::ACCOUNT_STATUS_TRIAL, WashLocation::ACCOUNT_STATUS_ACTIVE])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like) {
                    $query
                        ->where('name', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('district', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderByRaw('status = ? desc', [WashLocation::STATUS_OPEN])
            ->orderByDesc('active_orders_count')
            ->orderBy('name')
            ->get()
            ->filter(function (WashLocation $location) use ($onlyOpen, $status) {
                $publicStatus = $location->publicStatus();

                if ($onlyOpen) {
                    return $publicStatus === WashLocation::STATUS_OPEN || $publicStatus === WashLocation::STATUS_BUSY;
                }

                return $status === '' || $publicStatus === $status;
            })
            ->sortBy([
                fn (WashLocation $location) => match ($location->publicStatus()) {
                    WashLocation::STATUS_OPEN => 0,
                    WashLocation::STATUS_BUSY => 1,
                    default => 2,
                },
                fn (WashLocation $location) => -1 * $location->active_orders_count,
                fn (WashLocation $location) => $location->name,
            ])
            ->values();
        $reviewSummaries = $this->reviewSummariesFor($locations);

        return view('public.locations.index', [
            'locations' => $locations,
            'reviewSummaries' => $reviewSummaries,
            'status' => $status,
            'search' => $search,
            'onlyOpen' => $onlyOpen,
            'statuses' => WashLocation::statuses(),
            'mapLocations' => $locations->map(function (WashLocation $location) use ($reviewSummaries) {
                $summary = $reviewSummaries[$location->id] ?? null;

                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'detail_url' => route('public.locations.show', ['location' => $location->slug]),
                    'address' => $location->fullAddress(),
                    'district' => $location->district,
                    'city' => $location->city,
                    'status' => $location->publicStatus(),
                    'status_label' => $location->publicStatusLabel(),
                    'opening_hours' => $location->opening_hours ?: $location->openingHoursSummary(),
                    'phone' => $location->phone,
                    'directions_url' => $this->directionsUrl($location),
                    'active_orders_count' => $location->active_orders_count,
                    'rating_average' => $summary['average'] ?? null,
                    'rating_count' => $summary['count'] ?? 0,
                    'latitude' => $location->mapLatitude(),
                    'longitude' => $location->mapLongitude(),
                ];
            })->values(),
        ]);
    }

    public function show(WashLocation $location): View
    {
        abort_unless($location->isPubliclyVisible(), 404);

        $services = Service::query()
            ->where('wash_location_id', $location->id)
            ->where('active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        $testimonials = $this->testimonialsFor($location);

        return view('public.locations.show', [
            'location' => $location,
            'services' => $services,
            'testimonials' => $testimonials,
            'testimonialsSummary' => [
                'count' => $testimonials->count(),
                'average' => $testimonials->count() > 0
                    ? round((float) $testimonials->avg('rating'), 1)
                    : null,
            ],
            'whatsappUrl' => $location->whatsappUrl(),
            'directionsUrl' => $this->directionsUrl($location),
            'operatingSummary' => $this->operatingSummary($location),
            'businessHours' => $this->businessHoursForView($location),
        ]);
    }

    private function testimonialsFor(WashLocation $location)
    {
        return WashOrder::query()
            ->with(['customer', 'services'])
            ->where('wash_location_id', $location->id)
            ->where('customer_review_public', true)
            ->whereNotNull('customer_review_rating')
            ->whereNotNull('customer_review_comment')
            ->latest('customer_reviewed_at')
            ->limit(6)
            ->get()
            ->map(fn (WashOrder $washOrder) => [
                'author' => $this->publicCustomerName((string) $washOrder->customer?->name),
                'rating' => (int) $washOrder->customer_review_rating,
                'comment' => $washOrder->customer_review_comment,
                'reviewed_at' => $washOrder->customer_reviewed_at?->format('d/m/Y'),
                'service' => $washOrder->services->pluck('pivot.service_name')->filter()->first(),
            ]);
    }

    private function reviewSummariesFor($locations): array
    {
        $locationIds = $locations->pluck('id')->all();

        if ($locationIds === []) {
            return [];
        }

        return WashOrder::query()
            ->select('wash_location_id')
            ->selectRaw('COUNT(*) as reviews_count')
            ->selectRaw('AVG(customer_review_rating) as reviews_average')
            ->whereIn('wash_location_id', $locationIds)
            ->where('customer_review_public', true)
            ->whereNotNull('customer_review_rating')
            ->whereNotNull('customer_review_comment')
            ->groupBy('wash_location_id')
            ->get()
            ->mapWithKeys(fn (WashOrder $summary) => [
                $summary->wash_location_id => [
                    'count' => (int) $summary->reviews_count,
                    'average' => round((float) $summary->reviews_average, 1),
                ],
            ])
            ->all();
    }

    private function publicCustomerName(string $name): string
    {
        $parts = collect(preg_split('/\s+/', trim($name)) ?: [])->filter()->values();

        if ($parts->isEmpty()) {
            return 'Cliente AutoFlow';
        }

        if ($parts->count() === 1) {
            return $parts->first();
        }

        return $parts->first().' '.mb_substr((string) $parts->last(), 0, 1).'.';
    }

    /**
     * @return array{status: string, status_label: string, today_label: string, today_hours: string, next_event: string}
     */
    private function operatingSummary(WashLocation $location): array
    {
        $now = now();
        $publicStatus = $location->publicStatus($now);

        return [
            'status' => $publicStatus,
            'status_label' => $location->publicStatusLabel($now),
            'today_label' => WashLocation::businessHourDays()[strtolower($now->englishDayOfWeek)] ?? 'Hoje',
            'today_hours' => $this->hoursLabelForDay($location, strtolower($now->englishDayOfWeek)),
            'next_event' => $publicStatus === WashLocation::STATUS_CLOSED
                ? $this->nextOpeningLabel($location, $now)
                : $this->closingLabel($location, $now),
        ];
    }

    /**
     * @return array<int, array{day: string, hours: string, is_today: bool}>
     */
    private function businessHoursForView(WashLocation $location): array
    {
        $today = strtolower(now()->englishDayOfWeek);

        return collect(WashLocation::businessHourDays())
            ->map(fn (string $label, string $day) => [
                'day' => $label,
                'hours' => $this->hoursLabelForDay($location, $day),
                'is_today' => $day === $today,
            ])
            ->values()
            ->all();
    }

    private function hoursLabelForDay(WashLocation $location, string $day): string
    {
        $hours = $location->normalizedBusinessHours()[$day] ?? null;

        if (! $hours || ! $hours['is_open']) {
            return 'Fechado';
        }

        return $hours['opens'].' às '.$hours['closes'];
    }

    private function closingLabel(WashLocation $location, Carbon $moment): string
    {
        $hours = $location->normalizedBusinessHours();
        $todayKey = strtolower($moment->englishDayOfWeek);
        $todayHours = $hours[$todayKey] ?? null;

        if ($todayHours && $todayHours['is_open']) {
            $open = $moment->copy()->setTimeFromTimeString($todayHours['opens']);
            $close = $moment->copy()->setTimeFromTimeString($todayHours['closes']);

            if ($close->lessThanOrEqualTo($open)) {
                $close->addDay();
            }

            if ($moment->greaterThanOrEqualTo($open) && $moment->lessThan($close)) {
                return 'Aberto até '.$close->format('H:i');
            }
        }

        $previousKey = strtolower($moment->copy()->subDay()->englishDayOfWeek);
        $previousHours = $hours[$previousKey] ?? null;

        if ($previousHours && $previousHours['is_open']) {
            $open = $moment->copy()->subDay()->setTimeFromTimeString($previousHours['opens']);
            $close = $moment->copy()->subDay()->setTimeFromTimeString($previousHours['closes']);

            if ($close->lessThanOrEqualTo($open)) {
                $close->addDay();
            }

            if ($moment->greaterThanOrEqualTo($open) && $moment->lessThan($close)) {
                return 'Aberto até '.$close->format('H:i');
            }
        }

        return 'Aberto agora';
    }

    private function nextOpeningLabel(WashLocation $location, Carbon $moment): string
    {
        if ($location->status === WashLocation::STATUS_CLOSED) {
            return 'Fechado pelo estabelecimento';
        }

        $hours = $location->normalizedBusinessHours();
        $labels = WashLocation::businessHourDays();

        for ($daysAhead = 0; $daysAhead < 8; $daysAhead++) {
            $candidateDay = $moment->copy()->addDays($daysAhead);
            $dayKey = strtolower($candidateDay->englishDayOfWeek);
            $dayHours = $hours[$dayKey] ?? null;

            if (! $dayHours || ! $dayHours['is_open']) {
                continue;
            }

            $opensAt = $candidateDay->copy()->setTimeFromTimeString($dayHours['opens']);

            if ($opensAt->greaterThan($moment)) {
                $prefix = $daysAhead === 0 ? 'Abre hoje' : 'Abre '.$labels[$dayKey];

                return $prefix.' às '.$opensAt->format('H:i');
            }
        }

        return 'Horário indisponível';
    }

    private function directionsUrl(WashLocation $location): string
    {
        if ($location->hasCoordinates()) {
            return sprintf(
                'https://www.google.com/maps/dir/?api=1&destination=%s,%s',
                $location->mapLatitude(),
                $location->mapLongitude(),
            );
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($location->fullAddress());
    }
}
