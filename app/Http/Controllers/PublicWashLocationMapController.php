<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\WashLocation;
use Illuminate\Http\Request;
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
            ->when($onlyOpen, fn ($query) => $query->where('status', WashLocation::STATUS_OPEN))
            ->when(! $onlyOpen && $status !== '', fn ($query) => $query->where('status', $status))
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
            ->get();

        return view('public.locations.index', [
            'locations' => $locations,
            'status' => $status,
            'search' => $search,
            'onlyOpen' => $onlyOpen,
            'statuses' => WashLocation::statuses(),
            'mapLocations' => $locations->map(fn (WashLocation $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'detail_url' => route('public.locations.show', ['location' => $location->slug]),
                'address' => $location->fullAddress(),
                'district' => $location->district,
                'city' => $location->city,
                'status' => $location->status,
                'status_label' => $location->statusLabel(),
                'phone' => $location->phone,
                'active_orders_count' => $location->active_orders_count,
                'latitude' => $location->mapLatitude(),
                'longitude' => $location->mapLongitude(),
            ])->values(),
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

        return view('public.locations.show', [
            'location' => $location,
            'services' => $services,
            'whatsappUrl' => $location->whatsappUrl(),
            'directionsUrl' => $this->directionsUrl($location),
        ]);
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
