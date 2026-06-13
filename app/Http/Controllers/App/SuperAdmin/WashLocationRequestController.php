<?php

namespace App\Http\Controllers\App\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use App\Support\DefaultServices;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WashLocationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $requests = WashLocationRequest::query()
            ->with('washLocation')
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
        $locationRequest->load(['decidedBy', 'washLocation']);

        return view('app.super-admin.location-requests.show', [
            'locationRequest' => $locationRequest,
        ]);
    }

    public function approve(Request $request, WashLocationRequest $locationRequest): RedirectResponse
    {
        if (! $locationRequest->isPending()) {
            return back()->with('error', 'Essa solicitação já foi analisada.');
        }

        $validated = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($locationRequest, $validated, $request): void {
            $now = now();

            $location = WashLocation::query()->create([
                'name' => $locationRequest->business_name,
                'address' => $locationRequest->address,
                'district' => $locationRequest->district,
                'city' => trim($locationRequest->city.'/'.$locationRequest->state, '/'),
                'status' => WashLocation::STATUS_OPEN,
                'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
                'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
                'public_visible' => true,
                'trial_started_at' => $now,
                'trial_ends_at' => $now->copy()->addDays(15),
                'subscription_ends_at' => null,
                'blocked_at' => null,
                'approved_location_request_id' => $locationRequest->id,
                'active_orders_count' => 0,
                'phone' => $locationRequest->phone,
            ]);

            DefaultServices::seedForLocation($location);

            $this->createOrAttachOwnerUser($locationRequest, $location);

            $locationRequest->forceFill([
                'status' => WashLocationRequest::STATUS_APPROVED,
                'decision_notes' => $validated['decision_notes'] ?? null,
                'decided_at' => $now,
                'decided_by_user_id' => $request->user()->id,
                'wash_location_id' => $location->id,
            ])->save();
        });

        return redirect()
            ->route('super-admin.location-requests.show', $locationRequest)
            ->with('success', 'Solicitação aprovada. A unidade entrou em trial por 15 dias, recebeu os serviços padrão e o responsável foi vinculado como dono.');
    }

    public function reject(Request $request, WashLocationRequest $locationRequest): RedirectResponse
    {
        if (! $locationRequest->isPending()) {
            return back()->with('error', 'Essa solicitação já foi analisada.');
        }

        $validated = $request->validate([
            'decision_notes' => ['required', 'string', 'max:1000'],
        ]);

        $locationRequest->forceFill([
            'status' => WashLocationRequest::STATUS_REJECTED,
            'decision_notes' => $validated['decision_notes'],
            'decided_at' => now(),
            'decided_by_user_id' => $request->user()->id,
        ])->save();

        return redirect()
            ->route('super-admin.location-requests.show', $locationRequest)
            ->with('success', 'Solicitação rejeitada com segurança. Nenhuma unidade foi criada no mapa.');
    }

    private function createOrAttachOwnerUser(WashLocationRequest $locationRequest, WashLocation $location): User
    {
        $email = trim(strtolower($locationRequest->email));

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $locationRequest->responsible_name;
            $user->password = Hash::make(Str::password(32));
        }

        $user->role = User::ROLE_OWNER;
        $user->wash_location_id = $location->id;
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
