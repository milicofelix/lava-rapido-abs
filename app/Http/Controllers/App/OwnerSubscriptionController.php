<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Subscriptions\MercadoPagoCheckoutService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerSubscriptionController extends Controller
{
    public function show(Request $request, MercadoPagoCheckoutService $mercadoPago): View
    {
        $location = TenantContext::currentLocation()?->load(['currentSubscription.plan', 'activeSubscription.plan']);
        abort_unless($location, 404);

        return view('app.subscriptions.show', [
            'location' => $location,
            'currentSubscription' => $location->currentSubscription,
            'activeSubscription' => $location->activeSubscription,
            'plans' => Plan::query()->active()->orderBy('price')->orderBy('name')->get(),
            'mercadoPagoConfigured' => $mercadoPago->isConfigured(),
            'paymentReturn' => $this->paymentReturnMessage($request),
        ]);
    }

    public function choose(Request $request, MercadoPagoCheckoutService $mercadoPago): RedirectResponse
    {
        $location = TenantContext::currentLocation();
        abort_unless($location, 404);

        $data = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
        ]);

        $location->subscriptions()
            ->where('status', Subscription::STATUS_PENDING)
            ->update(['status' => Subscription::STATUS_CANCELED]);

        $subscription = $location->subscriptions()->create([
            'plan_id' => $data['plan_id'],
            'status' => Subscription::STATUS_PENDING,
            'started_at' => null,
            'ends_at' => null,
        ]);

        if ($mercadoPago->isConfigured()) {
            try {
                $preference = $mercadoPago->createPreference($subscription);
            } catch (RequestException $exception) {
                $subscription->forceFill(['status' => Subscription::STATUS_CANCELED])->save();

                report($exception);

                return back()->with('status', 'Nao foi possivel abrir o checkout do Mercado Pago agora. Verifique as credenciais e tente novamente.');
            }

            $checkoutUrl = $preference['init_point'] ?? $preference['sandbox_init_point'] ?? null;

            if ($checkoutUrl) {
                return redirect()->away($checkoutUrl);
            }

            return back()->with('status', 'Plano escolhido, mas o Mercado Pago nao retornou o link de pagamento. Tente novamente em instantes.');
        }

        return back()->with('status', 'Plano escolhido. A assinatura ficará ativa após confirmação manual do Super Admin.');
    }

    public function cancelPending(): RedirectResponse
    {
        $location = TenantContext::currentLocation();
        abort_unless($location, 404);

        $updated = $location->subscriptions()
            ->where('status', Subscription::STATUS_PENDING)
            ->update(['status' => Subscription::STATUS_CANCELED]);

        if ($updated === 0) {
            return back()->with('status', 'Nenhuma assinatura pendente para cancelar.');
        }

        return back()->with('status', 'Escolha de plano cancelada. Voce pode selecionar outro plano quando quiser.');
    }

    private function paymentReturnMessage(Request $request): ?array
    {
        $status = $request->string('collection_status')->toString()
            ?: $request->string('status')->toString();

        return match ($status) {
            'approved' => [
                'type' => 'success',
                'title' => 'Pagamento aprovado',
                'message' => 'Recebemos o retorno do Mercado Pago. A assinatura sera ativada assim que o webhook confirmar o pagamento.',
            ],
            'pending', 'in_process' => [
                'type' => 'warning',
                'title' => 'Pagamento em analise',
                'message' => 'O Mercado Pago ainda esta processando o pagamento. A assinatura sera atualizada automaticamente quando houver confirmacao.',
            ],
            'rejected', 'cancelled', 'failure' => [
                'type' => 'danger',
                'title' => 'Pagamento nao aprovado',
                'message' => 'O pagamento nao foi aprovado. Voce pode tentar novamente ou escolher outro plano.',
            ],
            default => null,
        };
    }
}
