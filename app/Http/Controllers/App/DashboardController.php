<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('app.dashboard', [
            'customerCount' => Customer::count(),
            'vehicleCount' => Vehicle::count(),
            'serviceCount' => Service::count(),
            'activeServiceCount' => Service::where('active', true)->count(),
            'recentCustomers' => Customer::latest()->withCount('vehicles')->limit(5)->get(),
        ]);
    }
}
