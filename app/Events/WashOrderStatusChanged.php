<?php

namespace App\Events;

use App\Models\WashOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WashOrderStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WashOrder $washOrder,
        public readonly ?string $fromStatus,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('wash-orders'),
            new Channel('wash-order.'.$this->washOrder->id),
            new Channel('wash-order.'.$this->washOrder->code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'WashOrderStatusChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->washOrder->id,
            'code' => $this->washOrder->code,
            'from_status' => $this->fromStatus,
            'status' => $this->washOrder->status,
            'status_label' => $this->washOrder->statusLabel(),
            'completed_at' => $this->washOrder->completed_at?->toIso8601String(),
            'estimated_completion_at' => $this->washOrder->estimated_completion_at?->toIso8601String(),
        ];
    }
}
