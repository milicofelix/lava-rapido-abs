<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Support\Loyalty\LoyaltyProgress;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LoyaltyCampaignController extends Controller
{
    public function __invoke(): View
    {
        $program = LoyaltyProgram::query()
            ->where('wash_location_id', TenantContext::currentLocationId())
            ->where('is_active', true)
            ->first();

        $nearReward = $this->nearRewardCampaign($program);
        $expiringCoupons = $this->expiringCouponsCampaign();
        $inactiveCustomers = $this->inactiveCustomersCampaign();

        return view('app.loyalty-campaigns.index', [
            'program' => $program,
            'campaigns' => [
                [
                    'key' => 'near_reward',
                    'title' => 'Perto de ganhar',
                    'description' => 'Clientes que faltam poucas lavagens para receber um benefício.',
                    'tone' => 'fuchsia',
                    'items' => $nearReward,
                ],
                [
                    'key' => 'expiring_coupon',
                    'title' => 'Cupom vencendo',
                    'description' => 'Clientes com cupom ativo que vence nos próximos 7 dias.',
                    'tone' => 'amber',
                    'items' => $expiringCoupons,
                ],
                [
                    'key' => 'inactive_customer',
                    'title' => 'Sem retorno recente',
                    'description' => 'Clientes com histórico de lavagem, mas sem voltar há mais de 45 dias.',
                    'tone' => 'blue',
                    'items' => $inactiveCustomers,
                ],
            ],
        ]);
    }

    private function nearRewardCampaign(?LoyaltyProgram $program)
    {
        if (! $program) {
            return collect();
        }

        return TenantContext::scopeCustomers(Customer::query())
            ->withCount('washOrders')
            ->whereHas('washOrders')
            ->orderBy('name')
            ->limit(120)
            ->get()
            ->map(function (Customer $customer) use ($program): array {
                $progress = LoyaltyProgress::forCustomer($customer, $program);

                return [
                    'customer' => $customer,
                    'title' => $customer->name,
                    'subtitle' => $progress['label'],
                    'meta' => $progress['current'].'/'.$progress['threshold'].' lavagens válidas',
                    'badge' => 'Faltam '.$progress['remaining'],
                    'message' => "Olá {$customer->name}! Falta pouco para você ganhar seu benefício de fidelidade. ".
                        "Você já tem {$progress['current']} de {$progress['threshold']} lavagens válidas. ".
                        'Passe aqui quando puder para continuar acumulando.',
                    'progress' => $progress['percent'],
                    'remaining' => $progress['remaining'],
                ];
            })
            ->filter(fn (array $item) => $item['progress'] > 0
                && $item['progress'] < 100
                && $item['remaining'] <= 2)
            ->sortByDesc('progress')
            ->take(12)
            ->values();
    }

    private function expiringCouponsCampaign()
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->with(['customer', 'rewardService', 'loyaltyProgram', 'sourceWashOrder.services'])
            ->activeAndValid()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(7)->endOfDay()])
            ->orderBy('expires_at')
            ->limit(12)
            ->get()
            ->map(fn (LoyaltyCoupon $coupon) => [
                'customer' => $coupon->customer,
                'title' => $coupon->customer?->name ?? 'Cliente não informado',
                'subtitle' => $coupon->benefitLabel(),
                'meta' => 'Cupom '.$coupon->code,
                'badge' => 'Vence '.$coupon->expires_at?->format('d/m/Y'),
                'message' => "Olá {$coupon->customer?->name}! Seu cupom {$coupon->code} está perto de vencer. ".
                    "Benefício: {$coupon->benefitLabel()}. ".
                    'Use até '.$coupon->expires_at?->format('d/m/Y').'.',
                'progress' => null,
            ])
            ->values();
    }

    private function inactiveCustomersCampaign()
    {
        return TenantContext::scopeCustomers(Customer::query())
            ->withMax('washOrders', 'entered_at')
            ->withCount('washOrders')
            ->whereHas('washOrders', fn (Builder $query) => $query->where('entered_at', '<', now()->subDays(45)))
            ->whereDoesntHave('washOrders', fn (Builder $query) => $query->where('entered_at', '>=', now()->subDays(45)))
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(function (Customer $customer) {
                $lastWashAt = $customer->wash_orders_max_entered_at
                    ? Carbon::parse($customer->wash_orders_max_entered_at)
                    : null;

                return [
                    'customer' => $customer,
                    'title' => $customer->name,
                    'subtitle' => 'Última lavagem em '.($lastWashAt?->format('d/m/Y') ?? '-'),
                    'meta' => $customer->wash_orders_count.' lavagem'.($customer->wash_orders_count === 1 ? '' : 's').' no histórico',
                    'badge' => 'Reativação',
                    'message' => "Olá {$customer->name}! Sentimos sua falta por aqui. ".
                        'Quando quiser deixar seu veículo em dia, nossa equipe está pronta para atender você novamente.',
                    'progress' => null,
                ];
            });
    }
}
