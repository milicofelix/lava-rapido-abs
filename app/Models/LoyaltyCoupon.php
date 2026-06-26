<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyCoupon extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_USED = 'used';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'wash_location_id',
        'loyalty_program_id',
        'customer_id',
        'source_wash_order_id',
        'reward_service_id',
        'code',
        'status',
        'earned_at',
        'expires_at',
        'used_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Ativo',
            self::STATUS_USED => 'Usado',
            self::STATUS_EXPIRED => 'Expirado',
            self::STATUS_CANCELED => 'Cancelado',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function benefitLabel(): string
    {
        if ($this->rewardService) {
            return $this->rewardService->name;
        }

        if (! $this->loyaltyProgram) {
            return 'Beneficio configurado';
        }

        return match ($this->loyaltyProgram->reward_type) {
            LoyaltyProgram::REWARD_DISCOUNT_AMOUNT => 'Desconto de R$ '.number_format((float) $this->loyaltyProgram->discount_value, 2, ',', '.'),
            LoyaltyProgram::REWARD_DISCOUNT_PERCENT => 'Desconto de '.number_format((float) $this->loyaltyProgram->discount_value, 0, ',', '.').'%',
            LoyaltyProgram::REWARD_SAME_SERVICE => $this->sourceWashOrder?->services?->first()?->name ?? 'Mesmo servico da contagem',
            default => 'Beneficio configurado',
        };
    }

    public function whatsappShareMessage(): string
    {
        $customerName = $this->customer?->name ?? 'cliente';
        $locationName = $this->washLocation?->name ?? 'nosso lava-rapido';
        $expiresAt = $this->expires_at?->format('d/m/Y') ?? 'sem data de vencimento';

        return "Ola {$customerName}! Voce ganhou um cupom de fidelidade no {$locationName}.\n\n".
            "Codigo: {$this->code}\n".
            "Beneficio: {$this->benefitLabel()}\n".
            "Validade: {$expiresAt}\n\n".
            'Apresente este cupom na proxima visita.';
    }

    public function whatsappShareUrl(): ?string
    {
        return $this->customer?->whatsappManualUrl($this->whatsappShareMessage());
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sourceWashOrder(): BelongsTo
    {
        return $this->belongsTo(WashOrder::class, 'source_wash_order_id');
    }

    public function rewardService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'reward_service_id');
    }
}
