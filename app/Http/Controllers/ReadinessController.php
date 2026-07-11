<?php

namespace App\Http\Controllers;

use App\Support\ReadinessReport;
use Illuminate\Http\JsonResponse;

class ReadinessController
{
    public function __invoke(ReadinessReport $readiness): JsonResponse
    {
        $report = $readiness->check();

        return response()->json([
            'status' => $report['ready'] ? 'ok' : 'unavailable',
            'checked_at' => now()->toIso8601String(),
            'checks' => collect($report['checks'])
                ->map(fn (array $check): array => [
                    'name' => $check['name'],
                    'ok' => $check['ok'],
                ])
                ->values(),
        ], $report['ready'] ? 200 : 503);
    }
}
