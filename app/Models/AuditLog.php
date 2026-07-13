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

    public const ACTION_CUSTOMERS_IMPORTED = 'customers.imported';

    public const ACTION_WASH_ORDER_CREATED = 'wash_order.created';

    public const ACTION_WASH_ORDER_STATUS_CHANGED = 'wash_order.status_changed';

    public const ACTION_PAYMENT_REGISTERED = 'payment.registered';

    public const ACTION_LOYALTY_COUPON_APPLIED = 'loyalty_coupon.applied';

    public const ACTION_LOYALTY_COUPON_REMOVED = 'loyalty_coupon.removed';

    public const ACTION_LOYALTY_COUPON_EXPIRED = 'loyalty_coupon.expired';

    public const ACTION_LOYALTY_COUPON_CANCELED = 'loyalty_coupon.canceled';

    public const ACTION_LOYALTY_COUPONS_PROCESSED = 'loyalty_coupons.processed';

    public const ACTION_LOCATION_PROFILE_UPDATED = 'location.profile_updated';

    public const ACTION_LOCATION_REQUEST_APPROVED = 'location_request.approved';

    public const ACTION_LOCATION_REQUEST_REJECTED = 'location_request.rejected';

    public const ACTION_ROLE_PERMISSIONS_UPDATED = 'role_permissions.updated';

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
            self::ACTION_CUSTOMERS_IMPORTED => 'Clientes importados',
            self::ACTION_WASH_ORDER_CREATED => 'Lavagem criada',
            self::ACTION_WASH_ORDER_STATUS_CHANGED => 'Status alterado',
            self::ACTION_PAYMENT_REGISTERED => 'Pagamento registrado',
            self::ACTION_LOYALTY_COUPON_APPLIED => 'Cupom de fidelidade aplicado',
            self::ACTION_LOYALTY_COUPON_REMOVED => 'Cupom de fidelidade removido',
            self::ACTION_LOYALTY_COUPON_EXPIRED => 'Cupom de fidelidade expirado',
            self::ACTION_LOYALTY_COUPON_CANCELED => 'Cupom de fidelidade cancelado',
            self::ACTION_LOYALTY_COUPONS_PROCESSED => 'Cupons de fidelidade processados',
            self::ACTION_LOCATION_PROFILE_UPDATED => 'Perfil da unidade atualizado',
            self::ACTION_LOCATION_REQUEST_APPROVED => 'Solicitação aprovada',
            self::ACTION_LOCATION_REQUEST_REJECTED => 'Solicitação rejeitada',
            self::ACTION_ROLE_PERMISSIONS_UPDATED => 'Permissões atualizadas',
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
