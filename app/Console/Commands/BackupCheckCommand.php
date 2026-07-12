<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupCheckCommand extends Command
{
    protected $signature = 'app:backup-check {--strict : Trata avisos como falha para release}';

    protected $description = 'Valida requisitos basicos do plano de backup e restore.';

    public function handle(): int
    {
        $checks = $this->checks();
        $hasFailures = false;
        $hasWarnings = false;

        foreach ($checks as $check) {
            if ($check['level'] === 'ok') {
                $this->info('[OK] '.$check['message']);
                continue;
            }

            if ($check['level'] === 'warning') {
                $hasWarnings = true;
                $this->warn('[ATENCAO] '.$check['message']);
                continue;
            }

            $hasFailures = true;
            $this->error('[FALHA] '.$check['message']);
        }

        if ($hasFailures || ($this->option('strict') && $hasWarnings)) {
            $this->newLine();
            $this->error('Plano de backup ainda nao esta pronto para producao.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Plano de backup validado.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{level: string, message: string}>
     */
    private function checks(): array
    {
        $backupPath = config('backup.storage_path');
        $retentionDays = (int) config('backup.retention_days');
        $defaultConnection = config('database.default');

        $checks = [
            $this->check(filled($defaultConnection), 'Conexao de banco definida.', 'Conexao de banco nao definida.'),
            $this->check(
                filled(config("database.connections.{$defaultConnection}")),
                'Configuracao da conexao de banco encontrada.',
                'Configuracao da conexao de banco nao encontrada.',
            ),
            $this->check(is_dir(storage_path('app/public')), 'Diretorio de uploads publicos encontrado.', 'Diretorio storage/app/public nao encontrado.'),
            $this->check(is_readable(storage_path('app/public')), 'Diretorio de uploads publicos legivel.', 'Diretorio storage/app/public precisa estar legivel para backup.'),
            $this->check(is_dir(storage_path('logs')), 'Diretorio de logs encontrado.', 'Diretorio storage/logs nao encontrado.'),
            $this->check($retentionDays >= 7, 'Retencao minima de backup configurada.', 'BACKUP_RETENTION_DAYS deve ser pelo menos 7.'),
            $this->warnIf(blank($backupPath), 'BACKUP_STORAGE_PATH nao definido; documente o destino externo dos backups.', 'Destino externo de backup configurado.'),
        ];

        if (filled($backupPath)) {
            $checks[] = $this->warnIf(! is_dir((string) $backupPath), 'BACKUP_STORAGE_PATH aponta para um diretorio inexistente neste ambiente.', 'Diretorio de destino do backup existe neste ambiente.');
        }

        return $checks;
    }

    /**
     * @return array{level: string, message: string}
     */
    private function check(bool $condition, string $okMessage, string $failureMessage): array
    {
        return [
            'level' => $condition ? 'ok' : 'failure',
            'message' => $condition ? $okMessage : $failureMessage,
        ];
    }

    /**
     * @return array{level: string, message: string}
     */
    private function warnIf(bool $condition, string $warningMessage, string $okMessage): array
    {
        return [
            'level' => $condition ? 'warning' : 'ok',
            'message' => $condition ? $warningMessage : $okMessage,
        ];
    }
}
