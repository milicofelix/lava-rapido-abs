<?php

namespace App\Http\Controllers\App\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WashLocationManagementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $locations = WashLocation::query()
            ->with(['owners' => fn ($query) => $query->orderBy('name')])
            ->withCount('users')
            ->when($status !== '' && array_key_exists($status, WashLocation::accountStatuses()), function ($query) use ($status) {
                $query->where(function ($query) use ($status) {
                    $query->where('subscription_status', $status)
                        ->orWhere(function ($query) use ($status) {
                            $query->whereNull('subscription_status')->where('account_status', $status);
                        });
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('users', function ($query) use ($search) {
                            $query->where('role', User::ROLE_OWNER)
                                ->where(function ($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'trial' => WashLocation::query()->where('subscription_status', WashLocation::ACCOUNT_STATUS_TRIAL)->count(),
            'active' => WashLocation::query()->where('subscription_status', WashLocation::ACCOUNT_STATUS_ACTIVE)->count(),
            'expired' => WashLocation::query()->where('subscription_status', WashLocation::ACCOUNT_STATUS_EXPIRED)->count(),
            'suspended' => WashLocation::query()->where('subscription_status', WashLocation::ACCOUNT_STATUS_SUSPENDED)->count(),
            'total' => WashLocation::query()->count(),
        ];

        return view('app.super-admin.locations.index', [
            'locations' => $locations,
            'plans' => Plan::query()->active()->orderBy('price')->orderBy('name')->get(),
            'statuses' => WashLocation::accountStatuses(),
            'status' => $status,
            'search' => $search,
            'summary' => $summary,
        ]);
    }

    public function extendTrial(Request $request, WashLocation $washLocation): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', Rule::in([7, 15, 30])],
        ]);

        $baseDate = $washLocation->trial_ends_at && $washLocation->trial_ends_at->isFuture()
            ? $washLocation->trial_ends_at->copy()
            : now();

        $washLocation->forceFill([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_started_at' => $washLocation->trial_started_at ?: now(),
            'trial_ends_at' => $baseDate->addDays((int) $validated['days']),
            'subscription_ends_at' => null,
            'blocked_at' => null,
            'public_visible' => true,
        ])->save();

        return back()->with('success', 'Trial prorrogado com segurança.');
    }

    public function activateSubscription(Request $request, WashLocation $washLocation): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'subscription_ends_at' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $washLocation->subscriptions()
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE])
            ->update(['status' => Subscription::STATUS_CANCELED]);

        $washLocation->subscriptions()->create([
            'plan_id' => $validated['plan_id'],
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'ends_at' => $validated['subscription_ends_at'],
        ]);

        $washLocation->forceFill([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => $validated['subscription_ends_at'],
            'blocked_at' => null,
            'public_visible' => true,
        ])->save();

        return back()->with('success', 'Assinatura ativada para a unidade.');
    }

    public function suspend(WashLocation $washLocation): RedirectResponse
    {
        $washLocation->forceFill([
            'account_status' => WashLocation::ACCOUNT_STATUS_SUSPENDED,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_SUSPENDED,
            'blocked_at' => now(),
            'public_visible' => false,
        ])->save();

        return back()->with('success', 'Unidade suspensa. O acesso operacional foi bloqueado.');
    }

    public function reactivate(WashLocation $washLocation): RedirectResponse
    {
        $subscriptionEndsAt = $washLocation->subscription_ends_at && $washLocation->subscription_ends_at->isFuture()
            ? $washLocation->subscription_ends_at
            : now()->addDays(30);

        $washLocation->forceFill([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => $subscriptionEndsAt,
            'blocked_at' => null,
            'public_visible' => true,
        ])->save();

        return back()->with('success', 'Unidade reativada com assinatura ativa.');
    }
}
