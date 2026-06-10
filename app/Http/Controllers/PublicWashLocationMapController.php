<?php

namespace App\Http\Controllers;

use App\Models\WashLocation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicWashLocationMapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = trim((string) $request->query('status'));

        $locations = WashLocation::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw("status = ? desc", [WashLocation::STATUS_OPEN])
            ->orderByDesc('active_orders_count')
            ->orderBy('name')
            ->get();

        return view('public.locations.index', [
            'locations' => $locations,
            'status' => $status,
            'statuses' => WashLocation::statuses(),
            'mapLocations' => $locations->map(fn (WashLocation $location) => [
                'id' => $location->id,
                'name' => $location->name,
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
}
