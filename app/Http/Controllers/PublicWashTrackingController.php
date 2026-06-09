<?php

namespace App\Http\Controllers;

use App\Models\WashOrder;
use Illuminate\View\View;

class PublicWashTrackingController extends Controller
{
    public function __invoke(string $code): View
    {
        $washOrder = WashOrder::query()
            ->where('code', $code)
            ->when(ctype_digit($code), fn ($query) => $query->orWhere('id', (int) $code))
            ->with(['vehicle', 'services', 'statusHistories'])
            ->firstOrFail();

        return view('tracking.show', [
            'washOrder' => $washOrder,
            'statuses' => WashOrder::statuses(),
            'progressStatuses' => WashOrder::publicProgressStatuses(),
        ]);
    }
}
