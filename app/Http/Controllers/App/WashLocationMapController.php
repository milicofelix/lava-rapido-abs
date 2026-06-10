<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WashLocation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WashLocationMapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = trim((string) $request->query('status'));

        $locations = WashLocation::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('active_orders_count')
            ->orderBy('name')
            ->get();

        return view('app.locations.map', [
            'locations' => $locations,
            'status' => $status,
            'statuses' => WashLocation::statuses(),
        ]);
    }
}
