<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
        'used_wash_order_id',
        'used_by_user_id',
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
        return self::statuses()[$this->effectiveStatus()] ?? $this->effectiveStatus();
    }

    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_ACTIVE && $this->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }

    public function scopeActiveAndValid(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function benefitLabel(): string
    {
        if ($this->rewardService) {
            return $this->rewardService->name;
        }

        if (! $this->loyaltyProgram) {
            return 'Benefício configurado';
        }

        return match ($this->loyaltyProgram->reward_type) {
            LoyaltyProgram::REWARD_DISCOUNT_AMOUNT => 'Desconto de R$ '.number_format((float) $this->loyaltyProgram->discount_value, 2, ',', '.'),
            LoyaltyProgram::REWARD_DISCOUNT_PERCENT => 'Desconto de '.number_format((float) $this->loyaltyProgram->discount_value, 0, ',', '.').'%',
            LoyaltyProgram::REWARD_SAME_SERVICE => $this->sourceWashOrder?->services?->first()?->name ?? 'Mesmo serviço da contagem',
            LoyaltyProgram::REWARD_FIXED_SERVICE => $this->sourceWashOrder?->services?->first()?->name ?? 'Serviço da lavagem premiada',
            default => 'Benefício configurado',
        };
    }

    public function whatsappShareMessage(): string
    {
        $customerName = $this->customer?->name ?? 'cliente';
        $locationName = $this->washLocation?->name ?? 'nosso lava-rapido';
        $expiresAt = $this->expires_at?->format('d/m/Y') ?? 'sem data de vencimento';

        return "Olá {$customerName}! Você ganhou um cupom de fidelidade no {$locationName}.\n\n".
            "Código: {$this->code}\n".
            "Benefício: {$this->benefitLabel()}\n".
            "Validade: {$expiresAt}\n\n".
            'Apresente este cupom na próxima visita.';
    }

    public function whatsappShareUrl(): ?string
    {
        return $this->customer?->whatsappManualUrl($this->whatsappShareMessage());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
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

    public function usedWashOrder(): BelongsTo
    {
        return $this->belongsTo(WashOrder::class, 'used_wash_order_id');
    }

    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function rewardService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'reward_service_id');
    }
}
