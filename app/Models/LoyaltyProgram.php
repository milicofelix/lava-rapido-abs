<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    public const COUNT_ANY = 'any';

    public const COUNT_CATEGORY = 'category';

    public const COUNT_SERVICE = 'service';

    public const REWARD_FIXED_SERVICE = 'fixed_service';

    public const REWARD_SAME_SERVICE = 'same_service';

    public const REWARD_DISCOUNT_AMOUNT = 'discount_amount';

    public const REWARD_DISCOUNT_PERCENT = 'discount_percent';

    protected $fillable = [
        'wash_location_id',
        'is_active',
        'threshold',
        'count_scope',
        'qualifying_service_id',
        'qualifying_category',
        'reward_type',
        'reward_service_id',
        'discount_value',
        'coupon_valid_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'discount_value' => 'decimal:2',
        ];
    }

    public static function countScopes(): array
    {
        return [
            self::COUNT_ANY => 'Qualquer lavagem',
            self::COUNT_CATEGORY => 'Por categoria de serviço',
            self::COUNT_SERVICE => 'Por serviço específico',
        ];
    }

    public static function rewardTypes(): array
    {
        return [
            self::REWARD_FIXED_SERVICE => 'Serviço definido',
            self::REWARD_SAME_SERVICE => 'Mesmo serviço da contagem',
            self::REWARD_DISCOUNT_AMOUNT => 'Desconto em reais',
            self::REWARD_DISCOUNT_PERCENT => 'Desconto percentual',
        ];
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function qualifyingService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'qualifying_service_id');
    }

    public function rewardService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'reward_service_id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(LoyaltyCoupon::class);
    }
}
