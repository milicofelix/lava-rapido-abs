<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReadinessReport
{
    /**
     * @return array{ready: bool, checks: array<int, array{name: string, ok: bool, message: string}>}
     */
    public function check(): array
    {
        $checks = [
            $this->database(),
            $this->cache(),
            $this->writablePath('storage', storage_path()),
            $this->writablePath('bootstrap_cache', base_path('bootstrap/cache')),
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['ok']),
            'checks' => $checks,
        ];
    }

    /**
     * @return array{name: string, ok: bool, message: string}
     */
    private function database(): array
    {
        try {
            DB::select('select 1 as ready');

            return $this->ok('database', 'Banco de dados acessivel.');
        } catch (Throwable) {
            return $this->failure('database', 'Banco de dados indisponivel.');
        }
    }

    /**
     * @return array{name: string, ok: bool, message: string}
     */
    private function cache(): array
    {
        $key = 'readiness:'.str()->uuid()->toString();

        try {
            Cache::put($key, 'ok', now()->addMinute());
            $value = Cache::get($key);
            Cache::forget($key);

            return $value === 'ok'
                ? $this->ok('cache', 'Cache acessivel.')
                : $this->failure('cache', 'Cache nao confirmou leitura e escrita.');
        } catch (Throwable) {
            return $this->failure('cache', 'Cache indisponivel.');
        }
    }

    /**
     * @return array{name: string, ok: bool, message: string}
     */
    private function writablePath(string $name, string $path): array
    {
        if (is_dir($path) && is_writable($path)) {
            return $this->ok($name, "Diretorio {$name} gravavel.");
        }

        return $this->failure($name, "Diretorio {$name} sem permissao de escrita.");
    }

    /**
     * @return array{name: string, ok: bool, message: string}
     */
    private function ok(string $name, string $message): array
    {
        return ['name' => $name, 'ok' => true, 'message' => $message];
    }

    /**
     * @return array{name: string, ok: bool, message: string}
     */
    private function failure(string $name, string $message): array
    {
        return ['name' => $name, 'ok' => false, 'message' => $message];
    }
}
