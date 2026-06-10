<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WashLocation extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_BUSY = 'busy';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name',
        'address',
        'district',
        'city',
        'status',
        'map_x',
        'map_y',
        'active_orders_count',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'map_x' => 'integer',
            'map_y' => 'integer',
            'active_orders_count' => 'integer',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Aberto',
            self::STATUS_BUSY => 'Em movimento',
            self::STATUS_CLOSED => 'Fechado',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}
