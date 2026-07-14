<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'wash_location_id',
        'name',
        'phone',
        'email',
        'cpf',
        'notes',
    ];

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function washOrders(): HasMany
    {
        return $this->hasMany(WashOrder::class);
    }

    public function loyaltyCoupons(): HasMany
    {
        return $this->hasMany(LoyaltyCoupon::class);
    }

    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', $this->phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55')) {
            return $digits;
        }

        if (in_array(strlen($digits), [10, 11], true)) {
            return '55'.$digits;
        }

        return $digits;
    }

    public function whatsappManualUrl(string $message): ?string
    {
        $number = $this->whatsappNumber();

        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public function whatsappTrackingUrl(WashOrder $washOrder): ?string
    {
        return $this->whatsappManualUrl("Ola {$this->name}, acompanhe o status da lavagem do seu veiculo pelo link: {$washOrder->trackingUrl()}");
    }
}
