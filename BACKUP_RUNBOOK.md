# Runbook de Backup e Restore - AutoFlow

Use este runbook em homologacao e producao. Backup bom e backup que foi restaurado pelo menos uma vez em ambiente seguro.

## O que deve ser salvo

- Banco de dados completo.
- `storage/app/public`, principalmente logos das unidades e arquivos publicos.
- `.env` guardado em cofre de senhas, nunca dentro do repositorio.
- Versao do codigo publicada, tag ou commit.

## Frequencia minima

- Banco: diario.
- Arquivos publicos: diario.
- Antes de deploy com migracao: backup manual imediato.
- Retencao minima: 30 dias.

## Variaveis recomendadas

```env
BACKUP_STORAGE_PATH=/backups/autoflow
BACKUP_RETENTION_DAYS=30
```

## Checagem antes do deploy

```bash
php artisan app:backup-check
```

Para bloquear qualquer aviso:

```bash
php artisan app:backup-check --strict
```

## Banco MySQL ou MariaDB

Backup:

```bash
mysqldump --single-transaction --routines --triggers -u "$DB_USERNAME" -p "$DB_DATABASE" > "$BACKUP_STORAGE_PATH/autoflow-$(date +%F-%H%M%S).sql"
```

Restore em ambiente seguro:

```bash
mysql -u "$DB_USERNAME" -p "$DB_DATABASE" < caminho/do/backup.sql
```

## Banco PostgreSQL

Backup:

```bash
pg_dump "$DB_DATABASE" > "$BACKUP_STORAGE_PATH/autoflow-$(date +%F-%H%M%S).sql"
```

Restore em ambiente seguro:

```bash
psql "$DB_DATABASE" < caminho/do/backup.sql
```

## Banco SQLite

Backup:

```bash
sqlite3 database/database.sqlite ".backup '$BACKUP_STORAGE_PATH/database-$(date +%F-%H%M%S).sqlite'"
```

Restore em ambiente seguro:

```bash
cp caminho/do/backup.sqlite database/database.sqlite
```

## Arquivos publicos

Backup:

```bash
tar -czf "$BACKUP_STORAGE_PATH/storage-public-$(date +%F-%H%M%S).tar.gz" storage/app/public
```

Restore em ambiente seguro:

```bash
tar -xzf caminho/do/storage-public.tar.gz -C /caminho/do/projeto
php artisan storage:link
```

## Validacao de restore

Depois de restaurar em homologacao:

```bash
php artisan migrate:status
php artisan app:readiness-check
php artisan test --filter=AuthenticationTest
php artisan test --filter=WashKanbanTest
```

Conferir manualmente:

- Login como Dono.
- Dashboard abre.
- Kanban abre.
- Cliente e veiculo aparecem.
- Logo da unidade aparece.
- Link publico de acompanhamento abre.

## Retencao

Remover backups antigos somente depois de confirmar que existe pelo menos um backup recente restauravel.

Exemplo para arquivos locais:

```bash
find "$BACKUP_STORAGE_PATH" -type f -mtime +30 -delete
```

## Rollback com backup

1. Tirar o sistema do ar ou colocar em manutencao.
2. Restaurar codigo da release anterior.
3. Restaurar banco apenas se indispensavel.
4. Restaurar `storage/app/public`.
5. Rodar `php artisan optimize:clear`.
6. Rodar `php artisan app:readiness-check`.
7. Testar login, dashboard, Kanban e portal publico.
