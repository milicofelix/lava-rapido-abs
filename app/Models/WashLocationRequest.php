<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashLocationRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'responsible_name',
        'email',
        'phone',
        'business_name',
        'zip_code',
        'address',
        'district',
        'city',
        'state',
        'employees_count',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'employees_count' => 'integer',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_REVIEW => 'Pendente de análise',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_REJECTED => 'Rejeitado',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function normalizedPhone(): string
    {
        return preg_replace('/\D+/', '', $this->phone) ?? '';
    }

    public static function hasPendingContact(string $email, string $phone): bool
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return static::query()
            ->where('status', self::STATUS_PENDING_REVIEW)
            ->where(function ($query) use ($email, $digits) {
                $query->where('email', $email);

                if ($digits !== '') {
                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', '') = ?", [$digits]);
                }
            })
            ->exists();
    }
}
