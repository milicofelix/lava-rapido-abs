<?php

namespace App\Models;

use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory;

    public const TYPE_SUPPLY = 'supply';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    protected $fillable = [
        'cash_register_id',
        'user_id',
        'type',
        'amount',
        'description',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_SUPPLY => 'Suprimento',
            self::TYPE_WITHDRAWAL => 'Sangria',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
