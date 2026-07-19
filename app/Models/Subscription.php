<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_EXPIRED = 'expired';

    public const PAYMENT_PROVIDER_MANUAL = 'manual';

    public const PAYMENT_PROVIDER_MANUAL_PIX = 'manual_pix';

    public const PAYMENT_PROVIDER_MERCADO_PAGO = 'mercado_pago';

    protected $fillable = [
        'wash_location_id',
        'plan_id',
        'status',
        'started_at',
        'ends_at',
        'payment_provider',
        'external_reference',
        'provider_preference_id',
        'provider_payment_id',
        'checkout_url',
        'paid_at',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Aguardando ativação',
            self::STATUS_ACTIVE => 'Ativa',
            self::STATUS_CANCELED => 'Cancelada',
            self::STATUS_EXPIRED => 'Expirada',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function paymentProviderLabel(): string
    {
        return match ($this->payment_provider) {
            self::PAYMENT_PROVIDER_MERCADO_PAGO => 'Mercado Pago',
            self::PAYMENT_PROVIDER_MANUAL_PIX => 'Pix manual',
            self::PAYMENT_PROVIDER_MANUAL, null => 'Manual',
            default => $this->payment_provider,
        };
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
