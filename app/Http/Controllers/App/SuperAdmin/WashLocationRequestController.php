<?php

namespace App\Http\Controllers\App\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WashLocationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WashLocationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $requests = WashLocationRequest::query()
            ->when($status !== '' && array_key_exists($status, WashLocationRequest::statuses()), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('responsible_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'pending' => WashLocationRequest::query()->where('status', WashLocationRequest::STATUS_PENDING_REVIEW)->count(),
            'approved' => WashLocationRequest::query()->where('status', WashLocationRequest::STATUS_APPROVED)->count(),
            'rejected' => WashLocationRequest::query()->where('status', WashLocationRequest::STATUS_REJECTED)->count(),
            'total' => WashLocationRequest::query()->count(),
        ];

        return view('app.super-admin.location-requests.index', [
            'requests' => $requests,
            'statuses' => WashLocationRequest::statuses(),
            'status' => $status,
            'search' => $search,
            'summary' => $summary,
        ]);
    }

    public function show(WashLocationRequest $locationRequest): View
    {
        return view('app.super-admin.location-requests.show', [
            'locationRequest' => $locationRequest,
        ]);
    }
}
