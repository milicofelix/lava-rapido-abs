<?php

namespace Tests\Feature\App;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupCheckCommandTest extends TestCase
{
    public function test_backup_check_passes_with_documented_warnings(): void
    {
        config([
            'backup.storage_path' => null,
            'backup.retention_days' => 30,
        ]);

        $this->artisan('app:backup-check')
            ->expectsOutputToContain('[OK] Conexão de banco definida.')
            ->expectsOutputToContain('[ATENÇÃO] BACKUP_STORAGE_PATH não definido; documente o destino externo dos backups.')
            ->expectsOutputToContain('Plano de backup validado.')
            ->assertExitCode(0);
    }

    public function test_backup_check_strict_mode_fails_on_warnings(): void
    {
        config([
            'backup.storage_path' => null,
            'backup.retention_days' => 30,
        ]);

        $this->artisan('app:backup-check --strict')
            ->expectsOutputToContain('[ATENÇÃO] BACKUP_STORAGE_PATH não definido; documente o destino externo dos backups.')
            ->expectsOutputToContain('Plano de backup ainda não está pronto para produção.')
            ->assertExitCode(1);
    }

    public function test_backup_check_passes_without_warnings_when_backup_path_exists(): void
    {
        $backupPath = storage_path('framework/testing-backups');
        File::ensureDirectoryExists($backupPath);

        config([
            'backup.storage_path' => $backupPath,
            'backup.retention_days' => 30,
        ]);

        $this->artisan('app:backup-check --strict')
            ->expectsOutputToContain('[OK] Destino externo de backup configurado.')
            ->expectsOutputToContain('[OK] Diretório de destino do backup existe neste ambiente.')
            ->expectsOutputToContain('Plano de backup validado.')
            ->assertExitCode(0);
    }

    public function test_backup_check_fails_when_database_connection_is_missing(): void
    {
        config([
            'database.default' => 'missing',
            'database.connections.missing' => null,
            'backup.retention_days' => 30,
        ]);

        $this->artisan('app:backup-check')
            ->expectsOutputToContain('[FALHA] Configuração da conexão de banco não encontrada.')
            ->assertExitCode(1);
    }
}
