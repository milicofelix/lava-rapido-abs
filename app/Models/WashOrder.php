<?php

namespace App\Models;

use App\Support\WashOrders\WashOrderStatusFlow;
use Database\Factories\WashOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WashOrder extends Model
{
    /** @use HasFactory<WashOrderFactory> */
    use HasFactory;

    public const STATUS_AWAITING = 'aguardando';

    public const STATUS_PREPARING = 'em_preparacao';

    public const STATUS_WASHING = 'lavando';

    public const STATUS_VACUUMING = 'aspirando';

    public const STATUS_WAXING = 'aplicando_cera';

    public const STATUS_FINISHING = 'finalizando';

    public const STATUS_READY = 'pronto_para_retirada';

    public const STATUS_DELIVERED = 'entregue';

    public const STATUS_CANCELED = 'cancelado';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_COURTESY = 'courtesy';

    public const PAYMENT_CREDIT_PENDING = 'credit_pending';

    protected $fillable = [
        'code',
        'wash_location_id',
        'customer_id',
        'vehicle_id',
        'assigned_user_id',
        'total_amount',
        'status',
        'payment_status',
        'entered_at',
        'estimated_completion_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'estimated_completion_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WashOrder $washOrder) {
            $washOrder->code ??= self::generateCode();
            $washOrder->entered_at ??= now();
            $washOrder->status ??= self::STATUS_AWAITING;
        });
    }

    public static function statuses(): array
    {
        return WashOrderStatusFlow::labels();
    }

    public static function activeStatuses(): array
    {
        return WashOrderStatusFlow::activeStatuses();
    }

    public static function publicProgressStatuses(): array
    {
        return WashOrderStatusFlow::publicProgressStatuses();
    }

    public function statusLabel(): string
    {
        return WashOrderStatusFlow::labelFor($this->status);
    }

    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_PENDING => 'Pendente',
            self::PAYMENT_PAID => 'Pago',
            self::PAYMENT_COURTESY => 'Cortesia',
            self::PAYMENT_CREDIT_PENDING => 'Fiado / pendente',
        ];
    }

    public function paymentStatusLabel(): string
    {
        return self::paymentStatuses()[$this->payment_status] ?? $this->payment_status;
    }

    public function trackingUrl(): string
    {
        return route('tracking.show', $this->code);
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['service_name', 'price', 'estimated_minutes'])
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customerNotifications(): HasMany
    {
        return $this->hasMany(CustomerNotification::class);
    }

    private static function generateCode(): string
    {
        do {
            $code = 'ABS-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
