# Roteiro de Homologacao Manual - AutoFlow

Use este roteiro para validar o produto em homologacao antes de liberar uma versao para uso real.

O objetivo nao e substituir a suite automatizada. A ideia e provar, no navegador, que os fluxos criticos continuam coerentes para cada perfil de usuario, com dados proximos da operacao real de um lava-rapido.

## 1. Identificacao da rodada

Preencha antes de iniciar:

- Ambiente:
- URL:
- Branch/tag:
- Data:
- Responsavel:
- Banco usado:
- Seeders executados:
- Observacoes:

Resultado final:

- [ ] Aprovado.
- [ ] Aprovado com ressalvas.
- [ ] Reprovado.

## 2. Preparacao

### 2.1 Comandos recomendados

Execute antes da navegacao manual:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan app:readiness-check
php artisan app:backup-check
php artisan mercado-pago:diagnose
npm run build
php artisan test
```

Se estiver usando Docker no ambiente local:

```bash
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan app:readiness-check
docker compose exec -T app php artisan app:backup-check
docker compose exec -T app php artisan mercado-pago:diagnose
docker compose exec -T app npm run build
docker compose exec -T app php artisan test
```

### 2.2 Contas de apoio

As contas abaixo podem existir quando os seeders de demo forem executados. Confirme no banco antes de usar:

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Super Admin | `milicofelix@gmail.com` | `password` |
| Admin/Dono demo | `admin@lavaabs.test` | `password` |
| Atendente | `ana@lavaabs.test` | `password` |
| Atendente/Caixa | `diego@lavaabs.test` | `password` |
| Operador | `bruno@lavaabs.test` | `password` |
| Operador | `carla@lavaabs.test` | `password` |

Se a base real de homologacao usar outros usuarios, registre aqui:

| Perfil | E-mail | Observacao |
| --- | --- | --- |
| Super Admin |  |  |
| Dono |  |  |
| Admin |  |  |
| Atendente |  |  |
| Operador |  |  |

## 3. Cenarios obrigatorios

Marque cada item com:

- `OK`: funcionou como esperado.
- `ERRO`: precisa correcao antes de liberar.
- `N/A`: nao se aplica ao ambiente testado.

### 3.1 Login, menu e permissoes

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Super Admin acessa Admin Produto. |  |  |
| Dono acessa Dashboard, Lavagens, Kanban, Clientes, Veiculos, Servicos e Configuracoes. |  |  |
| Admin acessa telas operacionais permitidas. |  |  |
| Atendente cria cliente, veiculo e lavagem. |  |  |
| Operador acessa Kanban. |  |  |
| Operador nao ve Financeiro, Assinatura, Auditoria e Configuracoes. |  |  |
| Operador nao consegue abrir URL proibida diretamente. |  |  |
| Botao de sair aparece para Operador no Kanban. |  |  |
| Menu recolhe e expande corretamente no desktop. |  |  |
| Navegacao inferior aparece no mobile. |  |  |

### 3.2 Cadastro publico e aprovacao de lava-rapido

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Visitante abre `/cadastro-lava-rapido`. |  |  |
| Formulario publica solicitacao com dados reais. |  |  |
| ViaCEP preenche endereco ao informar CEP. |  |  |
| Super Admin ve alerta no sininho para solicitacao pendente. |  |  |
| Super Admin abre detalhes da solicitacao. |  |  |
| Botao de geocodificacao busca latitude/longitude. |  |  |
| Aprovar solicitacao cria unidade, dono e trial. |  |  |
| Rejeitar solicitacao exige motivo. |  |  |
| Unidade aprovada aparece no mapa publico. |  |  |
| Detalhe publico da unidade abre por slug. |  |  |

### 3.3 Unidade, configuracoes e funcionamento

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Dono altera nome, WhatsApp, CNPJ, razao social e endereco. |  |  |
| Dono envia logo valido. |  |  |
| Logo aparece nas telas internas, exceto login. |  |  |
| Upload invalido e bloqueado. |  |  |
| Horario de funcionamento por dia da semana e salvo. |  |  |
| Mapa publico mostra Aberto/Fechado conforme horario. |  |  |
| Modulo Agenda pode ser habilitado/desabilitado. |  |  |
| Modulo Caixa pode ser habilitado/desabilitado. |  |  |
| Modulo Fiado pode ser habilitado/desabilitado. |  |  |
| Permissoes do Operador podem ser alteradas. |  |  |

### 3.4 Clientes e veiculos

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Atendente cadastra cliente com telefone e CPF formatados. |  |  |
| Atendente edita cliente. |  |  |
| Veiculo lista marcas em combo. |  |  |
| Modelos mudam conforme marca selecionada. |  |  |
| Sistema bloqueia modelo que nao pertence a marca. |  |  |
| Placa repetida e permitida em outra unidade. |  |  |
| Placa repetida e bloqueada na mesma unidade. |  |  |
| Ao criar lavagem, cliente filtra somente seus veiculos. |  |  |
| Cliente com mais de um veiculo permite escolher o correto. |  |  |

### 3.5 Lavagem, equipe e Kanban

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Atendente abre lavagem com mais de um servico. |  |  |
| Atendente seleciona varios responsaveis pela equipe. |  |  |
| Kanban abre filtrado para hoje. |  |  |
| Lavagens antigas nao aparecem por padrao. |  |  |
| Filtros do Kanban consultam data anterior. |  |  |
| Badge de fora do dia aparece em periodos amplos. |  |  |
| Operador da equipe move status permitido. |  |  |
| Operador fora da equipe nao move status. |  |  |
| Status incompatível com servico nao aparece. |  |  |
| Sistema bloqueia Entregue sem pagamento identificado. |  |  |
| Depois do pagamento, Entregue fica permitido. |  |  |
| Dashboard mostra Kanban somente do dia atual. |  |  |

### 3.6 Acompanhamento do cliente

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Tela publica de acompanhamento abre sem login. |  |  |
| Codigo publico funciona. |  |  |
| Status atual aparece corretamente. |  |  |
| Historico da lavagem aparece. |  |  |
| Progresso de fidelidade aparece quando habilitado. |  |  |
| Link do cliente abre compartilhamento manual por WhatsApp. |  |  |
| Notificacao manual gera texto de lavagem iniciada. |  |  |
| Notificacao manual gera texto de lavagem concluida. |  |  |
| Notificacao manual gera texto de promocao. |  |  |

### 3.7 Financeiro, caixa e fiado

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Pagamento em dinheiro e registrado. |  |  |
| Pagamento Pix e registrado. |  |  |
| Pagamento cartao e registrado. |  |  |
| Cortesia zera valor recebido. |  |  |
| Fiado aparece na fila de recebiveis quando habilitado. |  |  |
| Baixa de fiado remove da fila. |  |  |
| Dashboard reflete faturamento do dia. |  |  |
| Relatorio financeiro filtra periodo valido. |  |  |
| Relatorio financeiro rejeita data inicial maior que final. |  |  |
| Exportacao CSV financeiro baixa arquivo correto. |  |  |
| Caixa abre, registra sangria/reforco e fecha. |  |  |

### 3.8 Programa de fidelidade

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Dono habilita programa de fidelidade. |  |  |
| Formulario nao exige premio quando tipo nao precisa. |  |  |
| Meta de lavagens e salva. |  |  |
| Escopo por qualquer lavagem funciona. |  |  |
| Escopo por categoria funciona. |  |  |
| Escopo por servico especifico funciona. |  |  |
| Cupom e gerado ao atingir meta. |  |  |
| Cupom abre em tela personalizada. |  |  |
| Cupom compartilha por WhatsApp manual. |  |  |
| Cupom aplicavel aparece na lavagem compativel. |  |  |
| Cupom incompativel explica o motivo. |  |  |
| Aplicar cupom baixa status para usado. |  |  |
| Remover cupom antes do pagamento reabre valor correto. |  |  |
| Cupom vencido aparece como expirado. |  |  |
| Relatorio de fidelidade mostra metricas e CSV. |  |  |

### 3.9 Assinatura e planos em sandbox

Nao use pagamento real nesta rodada.

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Super Admin cria plano. |  |  |
| Super Admin edita plano. |  |  |
| Super Admin desativa plano. |  |  |
| Dono acessa Configuracoes > Assinatura. |  |  |
| Plano atual aparece destacado no card correto. |  |  |
| Plano inativo nao aparece para escolha. |  |  |
| Checkout sandbox abre com credencial de teste. |  |  |
| Retorno aprovado mostra mensagem correta. |  |  |
| Webhook ativa assinatura em sandbox. |  |  |
| Cobranca real permanece bloqueada sem `MERCADO_PAGO_LIVE_ENABLED=true`. |  |  |

### 3.10 Auditoria e notificacoes

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Alteracao de status cria auditoria. |  |  |
| Edicao de cliente cria auditoria, quando aplicavel. |  |  |
| Pagamento cria auditoria. |  |  |
| Fidelidade cria auditoria de cupom. |  |  |
| Filtro da auditoria abre com data atual. |  |  |
| Auditoria rejeita inicio maior que fim. |  |  |
| Sininho mostra trial vencendo. |  |  |
| Sininho mostra assinatura pendente/vencida. |  |  |
| Sininho mostra lavagem em andamento/atrasada. |  |  |
| Sininho mostra solicitacao pendente para Super Admin. |  |  |
| Sininho mostra cupom vencendo/vencido. |  |  |

### 3.11 Relatorios executivos

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Dashboard executivo mostra lavagens do mes. |  |  |
| Dashboard executivo mostra receita do mes. |  |  |
| Dashboard executivo mostra ticket medio. |  |  |
| Dashboard executivo mostra top servicos. |  |  |
| Dashboard executivo mostra clientes recorrentes. |  |  |
| Relatorio executivo filtra periodo valido. |  |  |
| Relatorio executivo rejeita datas invertidas. |  |  |
| Relatorio executivo rejeita data futura. |  |  |
| Comparativos por periodo fazem sentido com dados reais. |  |  |

### 3.12 Mobile e responsividade

Teste em largura aproximada de celular e desktop.

| Item | Resultado | Evidencia/observacao |
| --- | --- | --- |
| Login nao quebra em mobile. |  |  |
| Header interno fica alinhado. |  |  |
| Navegacao inferior mobile aparece e nao cobre conteudo. |  |  |
| Kanban pode ser usado em mobile com rolagem horizontal. |  |  |
| Tela de lavagem abre em mobile. |  |  |
| Tela publica de acompanhamento abre em mobile. |  |  |
| Mapa publico funciona em mobile. |  |  |

## 4. Evidencias minimas

Anexe ou registre:

- Screenshot do Dashboard do Dono.
- Screenshot do Kanban de hoje.
- Screenshot da tela de acompanhamento publico.
- Screenshot do cupom de fidelidade.
- Screenshot da assinatura com plano atual.
- Screenshot do mapa publico.
- Screenshot do sininho com notificacoes.
- CSV financeiro exportado.
- CSV de fidelidade exportado.
- Saida final de `php artisan test`.

## 5. Criterios de aprovacao

A rodada so deve ser aprovada quando:

- Todos os cenarios criticos estiverem `OK`.
- Nao houver erro 500.
- Nao houver tela branca.
- Operador nao enxergar area financeira/administrativa.
- Entrega sem pagamento continuar bloqueada.
- Cupom aplicado nao distorcer financeiro.
- Dados de outra unidade nao vazarem entre perfis.
- Portal publico abrir sem autenticacao.
- Suite automatizada estiver passando.
- Build Vite estiver gerado.

## 6. Registro de pendencias

| Severidade | Area | Descricao | Responsavel | Status |
| --- | --- | --- | --- | --- |
| Alta |  |  |  |  |
| Media |  |  |  |  |
| Baixa |  |  |  |  |

Se houver pendencia de severidade alta, a release deve ser reprovada.

