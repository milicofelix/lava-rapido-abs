<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusHistory extends Model
{
    protected $fillable = [
        'wash_order_id',
        'user_id',
        'from_status',
        'to_status',
        'notes',
    ];

    public function washOrder(): BelongsTo
    {
        return $this->belongsTo(WashOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
