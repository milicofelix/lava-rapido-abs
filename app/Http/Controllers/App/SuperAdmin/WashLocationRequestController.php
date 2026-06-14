<?php

namespace App\Http\Controllers\App\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use App\Support\AddressGeocoder;
use App\Support\DefaultServices;
use App\Support\MapsCoordinates;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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

    public function geocode(WashLocationRequest $locationRequest, AddressGeocoder $geocoder): JsonResponse
    {
        $coordinates = $geocoder->geocode($this->addressForGeocoding($locationRequest));

        if ($coordinates === null) {
            return response()->json([
                'fallback_required' => true,
                'message' => 'Não encontrei coordenadas automaticamente. Abra no Maps e cole a URL completa no campo de apoio.',
                'maps_url' => $this->mapsSearchUrl($locationRequest),
            ]);
        }

        return response()->json([
            'fallback_required' => false,
            ...$coordinates,
        ]);
    }

    public function approve(Request $request, WashLocationRequest $locationRequest, AddressGeocoder $geocoder): RedirectResponse
    {
        if (! $locationRequest->isPending()) {
            return back()->with('error', 'Essa solicitação já foi analisada.');
        }

        $this->mergeCoordinatesFromMapsUrl($request);
        $this->mergeCoordinatesFromAddress($request, $locationRequest, $geocoder);

        $validated = $request->validate([
            'google_maps_url' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'owner_password' => ['nullable', 'confirmed', 'min:8', 'max:120'],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($locationRequest, $validated, $request): void {
            $now = now();

            $location = WashLocation::query()->create([
                'name' => $locationRequest->business_name,
                'address' => $locationRequest->address,
                'address_number' => $locationRequest->address_number,
                'district' => $locationRequest->district,
                'city' => $locationRequest->city,
                'state' => $locationRequest->state,
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
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);

            DefaultServices::seedForLocation($location);

            $this->createOrAttachOwnerUser($locationRequest, $location, $validated['owner_password'] ?? null);

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

    private function createOrAttachOwnerUser(WashLocationRequest $locationRequest, WashLocation $location, ?string $plainPassword = null): User
    {
        $email = trim(strtolower($locationRequest->email));

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $locationRequest->responsible_name;
            $user->password = $plainPassword !== null
                ? Hash::make($plainPassword)
                : ($locationRequest->owner_password ?: Hash::make(Str::password(32)));
        }

        $user->role = User::ROLE_OWNER;
        $user->wash_location_id = $location->id;
        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function mergeCoordinatesFromMapsUrl(Request $request): void
    {
        if (filled($request->input('latitude')) && filled($request->input('longitude'))) {
            return;
        }

        $coordinates = MapsCoordinates::extractFromUrl($request->input('google_maps_url'));

        if ($coordinates === null) {
            return;
        }

        $request->merge([
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ]);
    }

    private function mergeCoordinatesFromAddress(Request $request, WashLocationRequest $locationRequest, AddressGeocoder $geocoder): void
    {
        if (filled($request->input('latitude')) && filled($request->input('longitude'))) {
            return;
        }

        $coordinates = $geocoder->geocode($this->addressForGeocoding($locationRequest));

        if ($coordinates === null) {
            return;
        }

        $request->merge([
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ]);
    }

    private function addressForGeocoding(WashLocationRequest $locationRequest): string
    {
        return collect([
            trim(collect([$locationRequest->address, $locationRequest->address_number])->filter()->implode(', ')),
            $locationRequest->district,
            $locationRequest->city,
            $locationRequest->state,
            $locationRequest->zip_code,
            'Brasil',
        ])->filter()->implode(', ');
    }

    private function mapsSearchUrl(WashLocationRequest $locationRequest): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->addressForGeocoding($locationRequest));
    }
}
