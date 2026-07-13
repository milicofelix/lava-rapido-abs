<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_PIX = 'pix';

    public const METHOD_DEBIT_CARD = 'debit_card';

    public const METHOD_CREDIT_CARD = 'credit_card';

    public const METHOD_COURTESY = 'courtesy';

    public const METHOD_CREDIT_PENDING = 'credit_pending';

    protected $fillable = [
        'wash_order_id',
        'user_id',
        'reversed_by_user_id',
        'method',
        'amount',
        'paid_at',
        'reversed_at',
        'notes',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public static function methods(): array
    {
        return [
            self::METHOD_CASH => 'Dinheiro',
            self::METHOD_PIX => 'Pix',
            self::METHOD_DEBIT_CARD => 'Cartao debito',
            self::METHOD_CREDIT_CARD => 'Cartao credito',
            self::METHOD_COURTESY => 'Cortesia',
            self::METHOD_CREDIT_PENDING => 'Fiado / pendente',
        ];
    }

    public function methodLabel(): string
    {
        return self::methods()[$this->method] ?? $this->method;
    }

    public function scopeEffective(Builder $query): Builder
    {
        return $query->whereNull('reversed_at');
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    public function washOrder(): BelongsTo
    {
        return $this->belongsTo(WashOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }
}
