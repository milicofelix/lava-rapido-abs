<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'estimated_minutes',
        'active',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'base_price' => 'decimal:2',
            'estimated_minutes' => 'integer',
        ];
    }

    public function washOrders(): BelongsToMany
    {
        return $this->belongsToMany(WashOrder::class)
            ->withPivot(['service_name', 'price', 'estimated_minutes'])
            ->withTimestamps();
    }
}
