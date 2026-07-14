<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNotification extends Model
{
    use HasFactory;

    public const CHANNEL_WHATSAPP_MANUAL = 'whatsapp_manual';

    public const STATUS_PREPARED = 'prepared';

    public const STATUS_SENT_MANUALLY = 'sent_manually';

    public const TEMPLATE_TRACKING_LINK = 'tracking_link';

    public const TEMPLATE_STATUS_UPDATE = 'status_update';

    public const TEMPLATE_READY_FOR_PICKUP = 'ready_for_pickup';

    public const TEMPLATE_WASH_STARTED = 'wash_started';

    public const TEMPLATE_WASH_COMPLETED = 'wash_completed';

    public const TEMPLATE_PROMOTION = 'promotion';

    protected $fillable = [
        'wash_order_id',
        'customer_id',
        'user_id',
        'channel',
        'template_key',
        'target',
        'message',
        'action_url',
        'status',
        'prepared_at',
        'manually_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'prepared_at' => 'datetime',
            'manually_sent_at' => 'datetime',
        ];
    }

    public static function templates(): array
    {
        return [
            self::TEMPLATE_TRACKING_LINK => 'Enviar link de acompanhamento',
            self::TEMPLATE_STATUS_UPDATE => 'Atualizar cliente sobre o status',
            self::TEMPLATE_READY_FOR_PICKUP => 'Avisar que esta pronto para retirada',
            self::TEMPLATE_WASH_STARTED => 'Lavagem iniciada',
            self::TEMPLATE_WASH_COMPLETED => 'Lavagem concluida',
            self::TEMPLATE_PROMOTION => 'Promocao',
        ];
    }

    public function templateLabel(): string
    {
        return self::templates()[$this->template_key] ?? $this->template_key;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SENT_MANUALLY => 'Enviado manualmente',
            default => 'Preparado',
        };
    }

    public function washOrder(): BelongsTo
    {
        return $this->belongsTo(WashOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
