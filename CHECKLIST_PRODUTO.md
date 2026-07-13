# Checklist do Produto - AutoFlow

Documento de acompanhamento do que ja foi construido e do que ainda falta para deixar o produto pronto para operacao comercial.

## Status geral

- [x] Base Laravel com autenticacao.
- [x] Layout principal com sidebar, cabecalho, logo, tema e notificacoes.
- [x] Multiunidade por lava-rapido.
- [x] Controle de perfis e permissoes.
- [x] Fluxo operacional de lavagens.
- [x] Kanban operacional.
- [x] Financeiro inicial.
- [x] Assinaturas e planos com Mercado Pago em sandbox.
- [x] Portal publico de lava-rapidos.
- [x] Programa de fidelidade.
- [ ] Pagamento real em producao.
- [ ] WhatsApp oficial/API.
- [ ] Hardening final para producao.

## Identidade visual e UX

- [x] Logo AutoFlow aplicado no login.
- [x] Logo menor nas telas internas.
- [x] Favicon customizado.
- [x] Layout premium inspirado no mock visual.
- [x] Sidebar recolhivel.
- [x] Ajustes de cabecalho, alinhamento de icones e cards.
- [x] Tela de login mais semantica e fundo claro.
- [x] Tela publica de acompanhamento com visual claro.
- [x] Substituicao de textos tecnicos como `Owner` por termos amigaveis.
- [x] Revisao visual final responsiva em todas as telas.
- [x] Navegacao principal mobile fixa para rotas mais usadas.
- [x] Padronizar todos os textos acentuados em pt-BR.

## Usuarios, ACL e permissoes

- [x] Perfis principais: Super Admin, Dono, Admin, Atendente e Operador.
- [x] ACL por permissao.
- [x] Operador com acesso restrito ao Kanban/status.
- [x] Itens de menu ocultos conforme permissao.
- [x] Permissoes configuraveis para operador em alguns acessos.
- [x] Operador sem acesso a financeiro, assinatura e areas administrativas.
- [x] Testes para bloqueios de acesso.
- [x] Tela mais completa para configurar permissoes por perfil.
- [x] Auditoria visual de permissoes por usuario.

## Lava-rapidos e unidades

- [x] Cadastro publico de lava-rapido.
- [x] Aprovacao pelo Super Admin.
- [x] Cadastro de dados da unidade.
- [x] Campos de perfil: nome, WhatsApp, tema, logo, CNPJ, razao social, endereco, cidade, UF e horario.
- [x] Horario de funcionamento estruturado por dia da semana.
- [x] Status publico Aberto/Fechado calculado pelo expediente.
- [x] Upload de logo com ajuste de limite de payload.
- [x] Upload de logo restrito a JPG, PNG e WebP com limite de tamanho e dimensoes.
- [x] Logo da unidade replicado nas telas internas.
- [x] Geocodificacao de endereco.
- [x] Integracao ViaCEP.
- [x] Mascara de CPF, CNPJ, CEP e telefone.
- [x] Mapa publico com lava-rapidos.
- [x] Seeder de lava-rapidos reais em Sao Paulo, com foco na Zona Leste.
- [x] Melhorar qualidade da geocodificacao em massa.
- [x] Criar rotina de reprocessamento de coordenadas pendentes.

## Clientes e veiculos

- [x] Cadastro de clientes.
- [x] Cadastro de veiculos vinculados ao cliente.
- [x] Ao criar lavagem, veiculos sao filtrados pelo cliente escolhido.
- [x] Suporte a cliente com mais de um veiculo.
- [x] Placa pode repetir em unidades diferentes.
- [x] Marca e modelo de veiculo em combos dependentes.
- [x] Factory/catalogo de veiculos com marcas e modelos coerentes.
- [x] Seeder de clientes e lavagens em massa.
- [ ] Melhorar importacao em lote de clientes/veiculos.
- [x] Historico consolidado por cliente com indicadores mais ricos.

## Lavagens e Kanban

- [x] Cadastro de lavagem.
- [x] Equipe com multiplos funcionarios responsaveis.
- [x] Kanban por data, abrindo em hoje.
- [x] Entregues de dias anteriores ocultos por padrao.
- [x] Filtros para consultar datas anteriores.
- [x] Badge para itens fora do dia em filtros amplos.
- [x] Dashboard com Kanban do dia atual.
- [x] Coluna `Entregue` no Kanban e dashboard.
- [x] Cards do Kanban mais compactos.
- [x] Operador so altera status se tiver permissao/equipe conforme regra.
- [x] Restricao para mover para `Entregue` sem pagamento identificado.
- [x] Bloqueio de abertura de lavagem fora do horario de funcionamento da unidade.
- [x] Status incompatíveis com servicos removidos do fluxo, exemplo: cera sem servico de cera.
- [x] Tela publica de acompanhamento do cliente.
- [x] Link do cliente com compartilhamento por WhatsApp manual.
- [ ] Melhorar notificacoes automaticas por evento.
- [x] Melhorar UX mobile do Kanban.

## Financeiro

- [x] Registro de pagamentos.
- [x] Status de pagamento em lavagem.
- [x] Card de faturamento no dashboard.
- [x] Caixa habilitavel por configuracao.
- [x] Fiado habilitavel por configuracao.
- [x] Bloqueio de entrega sem pagamento identificado.
- [x] Cupom de fidelidade impactando valor a receber.
- [x] Relatorio executivo financeiro com comparativo por periodo.
- [ ] Fechamento de caixa mais completo.
- [x] Relatorios financeiros avancados.
- [ ] Estorno e conciliacao mais robustos.

## Assinaturas, planos e Mercado Pago

- [x] Tabela de planos.
- [x] Starter, Professional e Enterprise.
- [x] Area do Dono: Configuracoes > Assinatura.
- [x] Area do Super Admin: planos.
- [x] Criar, editar e desativar planos.
- [x] Trial, expiracao e assinatura ativa.
- [x] Checkout Mercado Pago em sandbox.
- [x] Webhook Mercado Pago.
- [x] Bloqueio de cobranca real sem flag explicita.
- [x] Diagnostico de Mercado Pago.
- [x] Indicador correto de plano atual.
- [ ] Pagamento real em producao.
- [ ] Renovacao recorrente automatica.
- [ ] Cancelamento via provedor.
- [ ] Multi-gateway, exemplo: PagSeguro, Itau, Stripe.

## Programa de fidelidade

- [x] Configuracao por unidade.
- [x] Ativar/desativar programa.
- [x] Meta configuravel de lavagens.
- [x] Contagem por qualquer lavagem, categoria ou servico especifico.
- [x] Premio por servico definido, mesmo servico, desconto fixo ou percentual.
- [x] Validade configuravel do cupom.
- [x] Geracao de cupom ao atingir a meta.
- [x] Processamento manual de cupons pendentes.
- [x] Cupom personalizado com dados do cliente, beneficio e validade.
- [x] Compartilhamento manual via WhatsApp.
- [x] Aplicar cupom na lavagem.
- [x] Baixa do cupom ao aplicar.
- [x] Remover cupom antes de pagamento real ou entrega.
- [x] Cancelamento manual de cupom ativo.
- [x] Auditoria de aplicacao, remocao, expiracao, cancelamento e processamento.
- [x] Relatorio gerencial de fidelidade.
- [x] Cards de cupons ativos, usados, expirados e descontos concedidos.
- [x] Clientes proximos de ganhar cupom.
- [x] Busca por codigo, nome, telefone e CPF.
- [x] Exportacao CSV.
- [x] Alerta de cupons vencendo e vencidos no sininho.
- [x] Status efetivo: cupom ativo vencido aparece como expirado.
- [x] Tracking publico mostra progresso de fidelidade do cliente.
- [ ] Tela dedicada para campanhas/promocoes de fidelidade.
- [ ] Relatorio grafico de retencao e recorrencia.
- [ ] Notificacao automatica de cupom gerado/vencendo.

## Dashboard e relatorios

- [x] Dashboard operacional.
- [x] Cards do dia.
- [x] Kanban no dashboard filtrado para hoje.
- [x] Dashboard executivo com lavagens do mes, receita, ticket medio, top servicos e clientes recorrentes.
- [x] Historico operacional avancado.
- [x] Auditoria operacional.
- [x] Exportacao CSV financeiro.
- [x] Exportacao CSV fidelidade.
- [x] Relatorio executivo avancado.
- [x] Graficos mais refinados para diretoria.
- [x] Comparativos por periodo.
- [ ] Exportacao PDF.

## Agenda e modulos opcionais

- [x] Agenda criada.
- [x] Agenda habilitavel por configuracao.
- [x] Caixa habilitavel por configuracao.
- [x] Fiado habilitavel por configuracao.
- [x] Melhorar fluxo de agendamento para unidades que usam horario marcado.
- [ ] Disponibilidade por funcionario/box.
- [ ] Reagendamento e cancelamento formal.

## Notificacoes e WhatsApp

- [x] Central de notificacoes no cabecalho.
- [x] Trial vencendo.
- [x] Assinatura expirada/vencendo.
- [x] Caixa aberto.
- [x] Lavagens em andamento/atrasadas.
- [x] Solicitacoes pendentes para Super Admin.
- [x] Cupons vencendo/vencidos.
- [x] Templates manuais: lavagem iniciada, concluida e promocao.
- [x] Compartilhamento manual do link do cliente.
- [x] Compartilhamento manual de cupom.
- [ ] WhatsApp oficial/API.
- [ ] Webhooks de entrega/leitura.
- [ ] Opt-in do cliente.
- [ ] Disparo automatico por evento.

## Portal publico e mapa

- [x] Listagem publica de lava-rapidos.
- [x] Detalhe publico da unidade.
- [x] Mapa publico.
- [x] Filtros basicos.
- [x] Cadastro publico de lava-rapido.
- [x] Ajuste de rotas publicas por slug.
- [ ] SEO.
- [ ] Pagina publica mais completa da unidade.
- [ ] Avaliacoes/depoimentos.
- [ ] Horarios em tempo real.

## Seeders e dados de teste

- [x] Seeder de servicos padrao.
- [x] Seeder de lavagens de janeiro ate a data atual.
- [x] Seeder coerente de marcas/modelos de veiculos.
- [x] Seeder para clientes e lavagens das unidades existentes.
- [x] Seeder de lava-rapidos reais em Sao Paulo.
- [ ] Seeder especifico para cenarios de assinatura.
- [ ] Seeder especifico para cenarios de fidelidade completos.

## Infraestrutura e qualidade

- [x] Testes de features principais.
- [x] Testes de ACL.
- [x] Testes de fidelidade.
- [x] Testes de Mercado Pago sandbox.
- [x] Testes de auditoria.
- [x] Configuracao pt_BR.
- [x] Comandos agendados: assinatura e expiracao de cupons.
- [x] Endpoint de saude `/up` validado por teste.
- [x] Endpoint de readiness `/ready` para monitoramento de banco, cache e escrita.
- [x] Headers basicos de seguranca aplicados por middleware.
- [x] `X-Request-Id` em todas as respostas para rastreabilidade de logs.
- [x] Comando `app:production-check` para validar configuracoes antes do deploy.
- [x] Comando `app:readiness-check` para validacao operacional em runtime.
- [x] Comando `app:backup-check` para validar requisitos de backup.
- [x] Runbook de backup e restore documentado.
- [x] Suite completa validada no pacote de hardening atual: 273 testes e 1390 assercoes.
- [ ] Rodar suite completa antes de release.
- [x] Configurar CI/CD.
- [x] GitHub Actions com validacao Composer, testes PHP e build Vite.
- [x] Configurar backup.
- [x] Revisar logs e monitoramento.
- [x] Revisar seguranca de upload.
- [x] Revisar indices de banco para escala.
- [x] Checklist de deploy e homologacao documentado.
- [x] Roteiro dedicado de homologacao manual com dados reais documentado.

## Antes de voltar para pagamento real e WhatsApp oficial

- [x] Finalizar ciclo base de fidelidade.
- [x] Garantir que operador nao veja financeiro.
- [x] Garantir que cupom nao distorca financeiro.
- [x] Garantir que vencidos nao aparecam como ativos.
- [x] Revisao geral de UX mobile.
- [x] Suite completa de testes.
- [x] Checklist de deploy.
- [ ] Homologacao manual com dados reais.

## Prioridade sugerida daqui para frente

1. Revisao visual final e responsiva.
2. Hardening de producao: logs, backup, indices e uploads.
3. Pagamento real Mercado Pago.
4. WhatsApp oficial/API.
5. Multi-gateway.
