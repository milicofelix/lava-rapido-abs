<?php

namespace App\Models;

use Database\Factories\CashRegisterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    /** @use HasFactory<CashRegisterFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'wash_location_id',
        'opened_by_user_id',
        'closed_by_user_id',
        'status',
        'opening_balance',
        'counted_cash',
        'expected_cash',
        'cash_difference',
        'opened_at',
        'closed_at',
        'opening_notes',
        'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public static function openRegister(?int $washLocationId = null): ?self
    {
        return self::query()
            ->when($washLocationId !== null, fn ($query) => $query->where('wash_location_id', $washLocationId))
            ->where('status', self::STATUS_OPEN)
            ->latest('opened_at')
            ->first();
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function statusLabel(): string
    {
        return $this->status === self::STATUS_CLOSED ? 'Fechado' : 'Aberto';
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }
}
