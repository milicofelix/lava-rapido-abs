<?php

namespace App\Services\WashOrders;

use App\Models\Service;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

class CalculateWashTotalService
{
    /**
     * @param  array<int>  $serviceIds
     */
    public function handle(array $serviceIds): array
    {
        $services = Service::query()
            ->when(TenantContext::currentLocationId(), fn ($query, int $locationId) => $query->where('wash_location_id', $locationId))
            ->whereIn('id', $serviceIds)
            ->where('active', true)
            ->get()
            ->sortBy(fn (Service $service) => array_search($service->id, $serviceIds, true))
            ->values();

        return [
            'services' => $services,
            'total' => $this->total($services),
            'estimated_minutes' => $services->sum('estimated_minutes'),
        ];
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    private function total(Collection $services): string
    {
        return number_format((float) $services->sum('base_price'), 2, '.', '');
    }
}
