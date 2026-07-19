<?php

namespace App\Services\Subscriptions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WashLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualPixSubscriptionService
{
    public function createPending(WashLocation $location, Plan $plan): Subscription
    {
        return DB::transaction(function () use ($location, $plan) {
            $location->subscriptions()
                ->where('status', Subscription::STATUS_PENDING)
                ->update(['status' => Subscription::STATUS_CANCELED]);

            $subscription = $location->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'started_at' => null,
                'ends_at' => null,
                'payment_provider' => Subscription::PAYMENT_PROVIDER_MANUAL_PIX,
            ]);

            $reference = sprintf('PIX-AF-%s', str_pad((string) $subscription->id, 6, '0', STR_PAD_LEFT));
            $copyPaste = $this->copyPasteCode($plan, $reference);

            $subscription->forceFill([
                'external_reference' => $reference,
                'provider_payload' => [
                    'type' => 'manual_pix',
                    'pix_key' => $this->pixKey(),
                    'receiver_name' => $this->receiverName(),
                    'receiver_city' => $this->receiverCity(),
                    'amount' => number_format((float) $plan->price, 2, '.', ''),
                    'reference' => $reference,
                    'copy_paste' => $copyPaste,
                    'generated_at' => now()->toDateTimeString(),
                ],
            ])->save();

            return $subscription->load('plan');
        });
    }

    public function pixKey(): string
    {
        return (string) config('services.subscription_pix.key', 'milicofelix@gmail.com');
    }

    public function receiverName(): string
    {
        return $this->emvText((string) config('services.subscription_pix.receiver_name', 'AutoFlow ABS'), 25);
    }

    public function receiverCity(): string
    {
        return $this->emvText((string) config('services.subscription_pix.receiver_city', 'SAO PAULO'), 15);
    }

    private function copyPasteCode(Plan $plan, string $reference): string
    {
        $merchantAccount = $this->tlv('00', 'br.gov.bcb.pix')
            .$this->tlv('01', $this->pixKey())
            .$this->tlv('02', $this->emvText("Assinatura AutoFlow {$reference}", 72));
        $additionalData = $this->tlv('05', $this->txid($reference));

        $payload = $this->tlv('00', '01')
            .$this->tlv('26', $merchantAccount)
            .$this->tlv('52', '0000')
            .$this->tlv('53', '986')
            .$this->tlv('54', number_format((float) $plan->price, 2, '.', ''))
            .$this->tlv('58', 'BR')
            .$this->tlv('59', $this->receiverName())
            .$this->tlv('60', $this->receiverCity())
            .$this->tlv('62', $additionalData);

        $payloadForCrc = $payload.'6304';

        return $payloadForCrc.$this->crc16($payloadForCrc);
    }

    private function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private function txid(string $reference): string
    {
        return Str::of($reference)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->limit(25, '')
            ->toString();
    }

    private function emvText(string $value, int $limit): string
    {
        return Str::of(Str::ascii($value))
            ->upper()
            ->replaceMatches('/[^A-Z0-9 @._-]/', '')
            ->squish()
            ->limit($limit, '')
            ->toString();
    }

    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        for ($offset = 0, $length = strlen($payload); $offset < $length; $offset++) {
            $crc ^= ord($payload[$offset]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021)
                    : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
