<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use App\Models\WashOrder;
use App\Models\WashLocationRequest;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $metadata = [], ?User $user = null): AuditLog
    {
        $user ??= TenantContext::user();

        return AuditLog::query()->create([
            'wash_location_id' => self::locationId($subject),
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => self::subjectLabel($subject),
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private static function locationId(?Model $subject): ?int
    {
        if ($subject && isset($subject->wash_location_id)) {
            return $subject->wash_location_id ? (int) $subject->wash_location_id : null;
        }

        return TenantContext::currentLocationId();
    }

    private static function subjectLabel(?Model $subject): ?string
    {
        return match (true) {
            $subject instanceof WashOrder => $subject->code,
            $subject instanceof Customer => $subject->name,
            $subject instanceof User => $subject->name,
            $subject instanceof WashLocationRequest => $subject->business_name,
            $subject !== null && isset($subject->name) => (string) $subject->name,
            $subject !== null && isset($subject->plate) => (string) $subject->plate,
            default => null,
        };
    }
}
