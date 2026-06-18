<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Subscriptions\MercadoPagoCheckoutService;
use App\Services\Subscriptions\SubscriptionActivator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoCheckoutService $mercadoPago,
        SubscriptionActivator $activator,
    ): JsonResponse {
        $paymentId = data_get($request->all(), 'data.id')
            ?? $request->input('id')
            ?? $request->input('resource');

        if (! $paymentId || ! $mercadoPago->isConfigured()) {
            return response()->json(['status' => 'ignored']);
        }

        try {
            $payment = $mercadoPago->findPayment($paymentId);
        } catch (Throwable) {
            return response()->json(['status' => 'retry'], 503);
        }

        $externalReference = data_get($payment, 'external_reference');

        if (! $externalReference) {
            return response()->json(['status' => 'ignored']);
        }

        $subscription = Subscription::query()
            ->where('external_reference', $externalReference)
            ->first();

        if (! $subscription) {
            return response()->json(['status' => 'ignored']);
        }

        $status = data_get($payment, 'status');

        if ($status === 'approved') {
            $providerPaymentId = (string) data_get($payment, 'id', $paymentId);

            if ($subscription->status === Subscription::STATUS_ACTIVE
                && $subscription->provider_payment_id === $providerPaymentId
                && $subscription->paid_at !== null) {
                $subscription->forceFill(['provider_payload' => $payment])->save();

                return response()->json(['status' => 'already_processed']);
            }

            $activator->activate($subscription, null, [
                'payment_provider' => 'mercado_pago',
                'provider_payment_id' => $providerPaymentId,
                'paid_at' => data_get($payment, 'date_approved')
                    ? Carbon::parse(data_get($payment, 'date_approved'))
                    : now(),
                'provider_payload' => $payment,
            ]);

            return response()->json(['status' => 'activated']);
        }

        if (in_array($status, ['cancelled', 'rejected', 'refunded', 'charged_back'], true)) {
            $subscription->forceFill([
                'status' => Subscription::STATUS_CANCELED,
                'provider_payment_id' => (string) data_get($payment, 'id', $paymentId),
                'provider_payload' => $payment,
            ])->save();
        }

        return response()->json(['status' => 'received']);
    }
}
