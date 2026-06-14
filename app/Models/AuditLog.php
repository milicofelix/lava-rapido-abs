<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public const ACTION_CUSTOMER_CREATED = 'customer.created';

    public const ACTION_CUSTOMER_UPDATED = 'customer.updated';

    public const ACTION_WASH_ORDER_CREATED = 'wash_order.created';

    public const ACTION_WASH_ORDER_STATUS_CHANGED = 'wash_order.status_changed';

    public const ACTION_PAYMENT_REGISTERED = 'payment.registered';

    protected $fillable = [
        'wash_location_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public static function actions(): array
    {
        return [
            self::ACTION_CUSTOMER_CREATED => 'Cliente criado',
            self::ACTION_CUSTOMER_UPDATED => 'Cliente editado',
            self::ACTION_WASH_ORDER_CREATED => 'Lavagem criada',
            self::ACTION_WASH_ORDER_STATUS_CHANGED => 'Status alterado',
            self::ACTION_PAYMENT_REGISTERED => 'Pagamento registrado',
        ];
    }

    public function actionLabel(): string
    {
        return self::actions()[$this->action] ?? $this->action;
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
