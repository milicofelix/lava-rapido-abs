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
        'latitude',
        'longitude',
        'active_orders_count',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'map_x' => 'integer',
            'map_y' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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


    public function fullAddress(): string
    {
        return collect([$this->address, $this->district, $this->city])
            ->filter()
            ->implode(' - ');
    }

    public function mapLatitude(): float
    {
        if ($this->latitude !== null) {
            return (float) $this->latitude;
        }

        return -23.55052 + (((int) $this->map_y - 50) / 1000);
    }

    public function mapLongitude(): float
    {
        if ($this->longitude !== null) {
            return (float) $this->longitude;
        }

        return -46.63331 + (((int) $this->map_x - 50) / 1000);
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}
