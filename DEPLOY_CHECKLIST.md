# Checklist de Deploy e Homologacao - AutoFlow

Use este documento antes de colocar uma nova versao em homologacao ou producao.

## 1. Antes do deploy

- [ ] Confirmar branch/tag que sera publicada.
- [ ] Confirmar que nao existem migracoes pendentes sem revisao.
- [ ] Confirmar que o `CHECKLIST_PRODUTO.md` foi atualizado.
- [ ] Confirmar se a entrega mexe em pagamento, assinatura, WhatsApp, permissao ou dados financeiros.
- [ ] Fazer backup do banco antes de deploy com migracoes.
- [ ] Fazer backup de `storage/app/public`, principalmente logos de unidades.
- [ ] Registrar horario previsto de deploy e responsavel.

## 2. Variaveis obrigatorias de ambiente

### Aplicacao

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` configurado.
- [ ] `APP_URL` com dominio final HTTPS.
- [ ] `APP_LOCALE=pt_BR`
- [ ] `APP_FALLBACK_LOCALE=pt_BR`
- [ ] `APP_FAKER_LOCALE=pt_BR`

### Banco, cache, sessao e fila

- [ ] `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` configurados.
- [ ] `SESSION_DRIVER=database` ou driver definido para producao.
- [ ] `CACHE_STORE=database` ou Redis, conforme infraestrutura.
- [ ] `QUEUE_CONNECTION=database` ou Redis, conforme infraestrutura.
- [ ] Tabelas de cache, sessoes e jobs migradas.

### Arquivos e uploads

- [ ] `FILESYSTEM_DISK` definido.
- [ ] `php artisan storage:link` executado.
- [ ] Permissao de escrita em `storage` e `bootstrap/cache`.
- [ ] Limite de upload validado no PHP/Nginx para logo da unidade.

### Logs

- [ ] `LOG_CHANNEL` definido.
- [ ] `LOG_LEVEL=warning` ou superior em producao.
- [ ] Rotacao de logs configurada.
- [ ] Monitoramento de erro definido, se houver.

### Mercado Pago

- [ ] `MERCADO_PAGO_ENVIRONMENT=sandbox` em homologacao.
- [ ] `MERCADO_PAGO_ENVIRONMENT=production` somente em producao real.
- [ ] `MERCADO_PAGO_ACCESS_TOKEN` configurado.
- [ ] `MERCADO_PAGO_PUBLIC_KEY` configurado.
- [ ] `MERCADO_PAGO_WEBHOOK_SECRET` configurado.
- [ ] `MERCADO_PAGO_NOTIFICATION_URL` apontando para `/webhooks/mercado-pago`.
- [ ] `MERCADO_PAGO_SUCCESS_URL`, `MERCADO_PAGO_FAILURE_URL`, `MERCADO_PAGO_PENDING_URL` configurados.
- [ ] `MERCADO_PAGO_LIVE_ENABLED=false` ate liberar cobranca real.

## 3. Build e comandos de deploy

Executar em ambiente de deploy:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Se precisar limpar cache apos ajuste emergencial:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Scheduler e filas

### Scheduler

- [ ] Configurar cron do Laravel:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Confirmar tarefas:

```bash
php artisan schedule:list
```

Tarefas esperadas:

- [ ] `subscriptions:expire` rodando de hora em hora.
- [ ] `loyalty:expire-coupons` rodando diariamente.

### Queue worker

- [ ] Configurar worker com supervisor/systemd se `QUEUE_CONNECTION` nao for `sync`.
- [ ] Comando base:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

- [ ] Garantir restart do worker apos deploy:

```bash
php artisan queue:restart
```

## 5. Banco de dados

- [ ] Backup realizado antes do deploy.
- [ ] Migracoes executadas com sucesso.
- [ ] Sem erro em `migrations`.
- [ ] Indices revisados para tabelas grandes:
  - [x] `wash_orders`
  - [x] `payments`
  - [x] `loyalty_coupons`
  - [x] `audit_logs`
  - [x] `customers`
  - [x] `vehicles`
- [ ] Rodar smoke test de leitura e escrita.

## 6. Smoke test tecnico

Executar apos deploy:

```bash
curl -I https://seudominio.com/up
curl -I https://seudominio.com/ready
php artisan about
php artisan app:production-check
php artisan app:readiness-check
php artisan route:list
php artisan schedule:list
php artisan mercado-pago:diagnose
```

Confirmar no retorno HTTP:

- [ ] Status `200 OK` no endpoint `/up`.
- [ ] Status `200 OK` no endpoint `/ready`.
- [ ] Header `X-Frame-Options: SAMEORIGIN`.
- [ ] Header `X-Content-Type-Options: nosniff`.
- [ ] Header `Referrer-Policy: strict-origin-when-cross-origin`.
- [ ] Header `Strict-Transport-Security` quando estiver em HTTPS.
- [ ] `php artisan app:production-check` sem falhas criticas.
- [ ] `php artisan app:readiness-check` aprovado.

Se estiver em homologacao sandbox com API liberada:

```bash
php artisan mercado-pago:diagnose --api
```

Se o objetivo for bloquear qualquer aviso antes de producao real:

```bash
php artisan app:production-check --strict
```

## 7. Homologacao manual obrigatoria

### Login e permissoes

- [ ] Acessar como Super Admin.
- [ ] Acessar como Dono.
- [ ] Acessar como Admin.
- [ ] Acessar como Atendente.
- [ ] Acessar como Operador.
- [ ] Confirmar que Operador nao ve financeiro, assinatura, auditoria e configuracoes.

### Unidade e portal publico

- [ ] Cadastrar solicitacao publica de lava-rapido.
- [ ] Aprovar solicitacao no Super Admin.
- [ ] Confirmar trial criado.
- [ ] Abrir pagina publica da unidade.
- [ ] Confirmar mapa e endereco.

### Operacao

- [ ] Cadastrar cliente.
- [ ] Cadastrar veiculo.
- [ ] Abrir lavagem.
- [ ] Confirmar que veiculo listado pertence ao cliente.
- [ ] Mover no Kanban.
- [ ] Confirmar bloqueio de `Entregue` sem pagamento.
- [ ] Registrar pagamento.
- [ ] Mover para `Entregue`.
- [ ] Abrir link publico de acompanhamento.

### Financeiro

- [ ] Registrar pagamento em dinheiro.
- [ ] Registrar pagamento em Pix.
- [ ] Validar faturamento no dashboard.
- [ ] Validar caixa, se modulo estiver habilitado.
- [ ] Validar fiado, se modulo estiver habilitado.

### Fidelidade

- [ ] Ativar programa em Configuracoes.
- [ ] Criar lavagens suficientes para atingir meta.
- [ ] Processar cupons pendentes.
- [ ] Abrir cupom personalizado.
- [ ] Aplicar cupom em lavagem compativel.
- [ ] Confirmar baixa do cupom.
- [ ] Confirmar desconto no pagamento.
- [ ] Cancelar cupom ativo.
- [ ] Confirmar cupom vencido aparecendo como expirado.

### Assinatura

- [ ] Criar/editar plano no Super Admin.
- [ ] Acessar assinatura como Dono.
- [ ] Selecionar plano em sandbox.
- [ ] Confirmar retorno de pagamento aprovado.
- [ ] Confirmar webhook ou ativacao conforme fluxo configurado.
- [ ] Confirmar bloqueio de cobranca real se `MERCADO_PAGO_LIVE_ENABLED=false`.

### Auditoria e notificacoes

- [ ] Confirmar auditoria de cliente.
- [ ] Confirmar auditoria de lavagem.
- [ ] Confirmar auditoria de pagamento.
- [ ] Confirmar auditoria de fidelidade.
- [ ] Confirmar sininho para trial/assinatura.
- [ ] Confirmar sininho para solicitacao pendente no Super Admin.
- [ ] Confirmar sininho para cupom vencendo/vencido.

## 8. Seguranca

- [ ] `APP_DEBUG=false` confirmado.
- [ ] `.env` fora do versionamento.
- [ ] Permissoes de arquivo revisadas.
- [ ] Upload de logo aceita apenas imagem.
- [ ] Limite de upload definido.
- [ ] Rotas administrativas protegidas por perfil/permissao.
- [ ] Webhook Mercado Pago com validacao de origem/segredo.
- [ ] HTTPS ativo.
- [ ] Cookies seguros em producao, se aplicavel:
  - [ ] `SESSION_SECURE_COOKIE=true`
  - [ ] `SESSION_HTTP_ONLY=true`
  - [ ] `SESSION_SAME_SITE=lax` ou politica definida.

## 9. Rollback

- [ ] Ter release anterior disponivel.
- [ ] Ter backup do banco anterior ao deploy.
- [ ] Ter backup dos arquivos de upload.
- [ ] Saber quais migracoes foram executadas.
- [ ] Planejar rollback de codigo.
- [ ] Planejar rollback de banco apenas se indispensavel.
- [ ] Rodar smoke test apos rollback.

## 10. Criterio para liberar producao

- [ ] Checklist tecnico concluido.
- [ ] Homologacao manual concluida.
- [ ] Suite de testes principal passando.
- [ ] Sem erro critico em log.
- [ ] Backup validado.
- [ ] Pagamento real conscientemente habilitado, se aplicavel.
- [ ] Responsavel aprovou a liberacao.
