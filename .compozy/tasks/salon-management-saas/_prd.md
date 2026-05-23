# PRD – Neezor (Salon Management SaaS)

## Visão Geral

Neezor é um SaaS multi-tenant para gestão de salões de beleza, estética e barbearias, voltado para o mercado brasileiro. A proposta central é tirar o salão do caos de planilhas e caderninhos, oferecendo visão clara de agenda, caixa, comissões e lucro real — com o mínimo de esforço operacional diário.

O MVP é direcionado para salões pequenos (1-3 profissionais), com assinatura fixa de R$49/mês (até 3 profissionais) e precificação adicional por profissional acima desse limite. A meta de curto prazo é MRR (Receita Recorrente Mensal).

Diferencial: enquanto concorrentes vendem "agenda" ou "comissão" isoladamente, Neezor entrega controle financeiro completo no lançamento — permitindo que o dono enxergue o lucro real do salão.

## Objetivos

- **Meta de MRR**: Receita recorrente mensal como indicador principal de sucesso nos primeiros 6 meses.
- **Proposta de valor**: "O salão que lucra de verdade" — agenda + controle financeiro completo integrado.
- **Simplicidade**: Usável por um salão pequeno em poucos dias, com onboarding guiado.
- **Escalabilidade**: Base sólida para crescer para operações maiores com múltiplos profissionais e unidades.

## Personas e User Stories

### Dono(a) do Salão (Persona Primária)

- Como dono(a), quero ver o lucro real do salão por mês para tomar decisões informadas sobre preços e despesas.
- Como dono(a), quero gerenciar profissionais e suas comissões automaticamente para evitar disputas e planilhas manuais.
- Como dono(a), quero registrar despesas mensais e ter visão clara do DRE para entender a rentabilidade real.
- Como dono(a), quero configurar pacotes de serviços flexíveis para oferecer opções de consumo aos clientes.
- Como dono(a), quero uma visão consolidada de agenda e financeiro para ter controle total em um único lugar.

### Profissional/Comissionado

- Como profissional, quero ver minha agenda do dia e da semana para saber quem vou atender.
- Como profissional, quero acompanhar minhas comissões acumuladas para ter transparência sobre meus ganhos.
- Como profissional, quero registrar o consumo de sessões de pacotes dos clientes para manter o controle atualizado.

### Recepção/Atendente

- Como recepcionista, quero agendar clientes com profissionais específicos ou alocar conforme disponibilidade para otimizar a agenda.
- Como recepcionista, quero ver todos os agendamentos do dia em uma tela para gerenciar o fluxo do salão.
- Como recepcionista, quero registrar lançamentos de caixa (recebimentos e despesas) de forma rápida para fechar o dia sem erros.

### Cliente Final

- Como cliente, quero agendar online (via link compartilhável) para evitar chamadas e esperar.
- Como cliente, quero receber lembretes de agendamento via WhatsApp para não esquecer do compromisso.
- Como cliente, quero ver o histórico de serviços realizados e sessões restantes de pacotes.

## Funcionalidades Principais

### F1: Agenda e Horários

- **Agenda por profissional**: Cada profissional vê sua agenda individual.
- **Visão do salão**: Visão consolidada de todos os profissionais, com filtro por profissional.
- **Slots de disponibilidade**: Configuração de horários disponíveis por profissional e serviço.
- **Agendamento online**: Link compartilhável para cliente ver horários e agendar via web (sem app nativo).
- **Gestão de conflitos**: Impedir sobreposição de horários; permitir buffers entre atendimentos.
- **Status de agendamento**: Agendado, confirmado, em atendimento, concluído, cancelado, no-show.

### F2: Serviços e Pacotes

- **Catálogo de serviços**: Cadastro de serviços com nome, duração, preço, profissional habilitado.
- **Pacotes flexíveis**: Pacotes com múltiplos serviços diferentes (ex: 3 depilação + 2 limpeza de pele).
- **Controle de sessões**: Débito de sessão por serviço dentro do pacote. Sessoes restantes por serviço.
- **Expiração de pacotes**: Data de validade por pacote (ex: 6 meses após compra).
- **Precificação**: Preço do pacote vs. soma dos serviços avulsos (desconto por pacote).

### F3: Clientes

- **Cadastro de clientes**: Nome, telefone (WhatsApp), e-mail, observações.
- **Histórico de serviços**: Lista de serviços realizados com data, profissional, valor.
- **Pacotes ativos**: Pacotes comprados com sessões restantes e data de validade.
- **Clientes inativos**: Visão de clientes sem atendimento nos últimos 60+ dias (para ação de retenção).
- **Preferências**: Observações sobre preferências do cliente (ex: "prefere atendente Ana").

### F4: Caixa Diário

- **Abertura de caixa**: Registrar saldo inicial no início do dia.
- **Lançamentos**: Recebimentos vinculados a agendamento (dinheiro, cartão, PIX, etc.).
- **Despesas**: Lançamentos de saída (produtos, insumos, contas) com categoria.
- **Fechamento de caixa**: Reconciliar total do dia com saldo em caixa. Diferenças devem ser rastreáveis.
- **Métodos de pagamento**: Dinheiro, cartão de crédito, cartão de débito, PIX, transferência.

### F5: Comissões

- **Configuração por profissional**: Percentual de comissão por profissional (ex: 40% do valor do serviço).
- **Regras variáveis**: Possibilidade de comissão diferente por serviço (ex: cabelo 50%, unhas 40%).
- **Rateio por atendimento**: Cada atendimento gera comissão proporcional ao profissional que executou.
- **Histórico de comissões**: Visão de comissões acumuladas por período (semana, mês).
- **Pagamento de comissão**: Registro de quando a comissão foi paga ao profissional (com data e observações).
- **Ajustes manuais**: Comissões Manually ajustadas precisam de motivo + responsável rastreável.

### F6: Despesas e DRE

- **Cadastro de despesas fixas**: Aluguel, energia, água, folha de pagamento, insumos recorrentes.
- **Cadastro de despesas variáveis**: Produtos, materiais, serviços pontuais.
- **Categorização**: Categorias de despesa para análise (ex: operacional, marketing,RH).
- **DRE mensal**: Demonstrativo de Resultado mensal — Receitas - Comissões - Despesas = Lucro.
- **Visão de margem**: Percentual de margem de lucro por período.

### F7: Notificações via WhatsApp

- **Lembrete de agendamento**: Envio automático de lembrete 24h antes do horário.
- **Confirmação de agendamento**: Mensagem pedindo confirmação (com reply option).
- **Aviso de cancelamento**: Notificação ao cliente e profissional em caso de cancelamento.
- **Lembrete de pacote expirando**: Alerta quando restam 1-2 sessões ou pacote está próxima da validade.
- **Canal**: WhatsApp Business API.

## Experiência do Usuário

### Jornada Principal (Dono + Profissional)

1. **Onboarding**: Cadastro do salão → Configurar profissionais → Cadastrar serviços → Configurar pacotes (opcional) → Pronto para usar.
2. **Gestão diária**: Agenda do dia → Atender cliente → Registrar recebimento → Ver comissões acumuladas.
3. **Fechamento semanal**: Fechar caixa semanal → Ver relatório de comissões → Pagar profissionais.
4. **Visão mensal**: Ver DRE mensal → Analisar lucro → Ajustar preços ou despesas se necessário.

### Jornada do Cliente

1. **Primeiro contato**: Recebe link de agendamento via WhatsApp ou indicação.
2. **Auto-agendamento**: Abre link, seleciona serviço, escolhe profissional (ou "melhor disponibilidade"), escolhe horário, confirma.
3. **Lembrete**: Recebe WhatsApp 24h antes confirmando horário.
4. **Atendimento**: Vai ao salão, é atendido.
5. **Pós-atendimento**: Recebe confirmação de conclusão e convite para próximo agendamento.

### Considerações de UX

- **Mobile-first**: Interface otimizada para celular, já que muito do uso é no balcão ou durante o atendimento.
- **One-stop-shop**: Dono faz tudo em uma ferramenta; sem necessidade deplanilha complementar.
- **Onboarding guiado**: Setup em 3 telas: (1) Cadastrar profissionais, (2) Cadastrar serviços, (3) Configurar agenda padrão.
- **Feedback visual**: Indicadores claros de agenda cheia/vazia, saldo positivo/negativo, comissão paga/pendente.
- **Acessibilidade**: Contraste adequado, fontes legíveis, navegação por teclado.

## Restrições Técnicas de Alto Nível

- **Stack**: PHP 8.x + Laravel (multi-tenant) + Jetstream/Inertia + Vue 3 + Tailwind CSS.
- **Multi-tenant**: Todo dado filtrado por `tenant_id`. Proibido vazamento de dados entre salões.
- **Banco de dados**: PostgreSQL ou MySQL.
- **Ambiente**: Docker para desenvolvimento e deploy.
- **Segurança**: Nunca logar dados sensíveis (senhas, tokens, dados bancários). Validação sempre no backend.
- **Arquitetura**: Organização por domínios de negócio (`Domain/Scheduling`, `Domain/Cashbox`, etc.).

## Fora de Escopo (MVP)

- **Multi-unidades**: Um salão por conta. Matriz-filial fica para Fase 2.
- **App nativo para cliente**: Apenas auto-agendamento web (link compartilhável). Sem app iOS/Android.
- **E-mail e SMS**: Apenas WhatsApp como canal de comunicação com cliente no MVP.
- **Gestão de produtos/estoque**: Venda de produtos avulsos fica para Fase 2.
- **Gift cards / créditos pré-pagos**: Fica para Fase 3.
- **Integrações contábeis**: Exportação para software contábil fica para Fase 3.
- **Relatórios avançados**: Análise preditiva, benchmarking de mercado fica para Fase 3.

## Plano de Rollout em Fases

### MVP (Fase 1) — ~3-4 meses de desenvolvimento

**Incluído:**
- Cadastro de salão, profissionais e serviços
- Agenda por profissional + visão do salão
- Pacotes flexíveis com controle de sessões
- Cadastro de clientes com histórico
- Auto-agendamento web (link compartilhável)
- Caixa diário (abertura, lançamentos, fechamento)
- Comissões por profissional (percentual por serviço)
- Notificações via WhatsApp (lembrete, confirmação)
- DRE mensal básico

**Critério de sucesso para Fase 2:**
- 20+ salões ativos usando sistema diariamente
- Taxa de retenção >80% após 3 meses
- Feedback positivo sobre controle financeiro
- Nenhum bug crítico em financeiro (caixa, comissões)

### Fase 2 — Recursos adicionais

- Multi-unidades (matriz-filial)
- Módulo de despesas recorrentes e categorização avançada
- Dashboard de métricas (ARPU, churn, ocupabilidade)
- Integração com gateway de pagamento (pagar antes via link)
- App do cliente (Android/iOS — ou PWA)

### Fase 3 — Escala e ecossistema

- Gift cards e créditos pré-pagos
- Integrações contábeis (exportação)
- Programa de fidelidade
- Relatórios avançados e benchmarking
- API pública para integrações de terceiros

## Métricas de Sucesso

### Métricas de Negócio

| Métrica | Definição | Meta (6 meses) |
|---|---|---|
| Salões ativos | Salões com pelo menos 1 agendamento/semana | 100 salões |
| MRR | Receita recorrente mensal em R$ | R$ 10.000/mês |
| ARPU | Receita média por salão ativo | R$ 100/salão/mês |
| Churn mensal | % de salões que cancelam por mês | <5% |
| Taxa de retenção | % de salões que permanecem após 3 meses | >80% |
| Tempo de onboarding | Dias até primeiro agendamento feito no sistema | <3 dias |

### Métricas de Produto

| Métrica | Definição | Meta |
|---|---|---|
| NPS | Net Promoter Score de donos de salão | >40 |
| Ticket médio | Valor médio por atendimento | Monitorar |
| Taxa de uso de pacote | % de atendimentos que consomem sessões de pacote | Monitorar |
| Agendamentos online | % de agendamentos feitos pelo link web vs. via recepção | >30% via web |
| Dívida técnica | Bugs abertos, tempo de resposta da API | Reduzir progressivamente |

### Métricas de Qualidade

- **Uptime**: >99% disponibilidade.
- **Tempo de carregamento**: <3 segundos para qualquer tela em conexões móveis.
- **Zero vazamento de dados**: Violação de multi-tenant = falha crítica.
- **Cobertura de testes**: Regra de negócio financeira (comissão, caixa, DRE) com 100% de cobertura de testes unitários.

## Riscos e Mitigações

### Riscos de Adoção

- **Risco**: Donos de salão pequeno acham "complexo demais" e abandonam antes de ver valor.
  - **Mitigação**: UX simples, onboarding guiado, default de configurações funcionais. "Tudo funcionando na primeira tela."
- **Risco**: Resistência de profissionais a registrar comissões no sistema.
  - **Mitigação**: Interface clara de comissões, transparência, relatórios que beneficiam o profissional também.

### Riscos Competitivos

- **Risco**: Concorrentes estabelecidos (ex: Mindie, GoHigh Level) adicionam funcionalidades financeiras.
  - **Mitigação**: Foco early-stage em profundidade financeira (DRE, margens) vs. amplitude. Produto "feito por gente que entende salão."

### Riscos de Timeline

- **Risco**: Escopo de financeiro completo demanda mais tempo que estimado.
  - **Mitigação**: Dividir MVP em duas iterações internas: (1) Agenda + Pacotes + Caixa + Comissões; (2) Despesas + DRE. Priorizar qualidade over velocidade.

### Riscos de Modelo de Preço

- **Risco**: Salões pequenos não convertem para plano pago.
  - **Mitigação**: Garantir valor óbvio no tier pago. Trial gratuito (14-30 dias) com cartão de crédito para reduzir churn. Preço competitivo para 1-3 profissionais.

## Registros de Decisões de Arquitetura

- [ADR-001: Escopo MVP — Abordagem "Financeiro Completo"](adrs/adr-001.md) — Decisão de incluir financeiro completo (caixa + comissões + despesas + DRE) no MVP em vez de land-and-expand.

## Perguntas Abertas

- **Q1**: Qual valor exato de R$ por profissional adicional acima de 3 profissionais? (Ex: R$20/prof/mês ou R$30?)
- **Q2**: Qual o período de trial gratuito? (14 dias ou 30 dias?)
- **Q3**: A WhatsApp Business API será própria (conta do Neezor com chaves) ou o salão fornece sua própria conta Business?
- **Q4**: Como será a gestão de profissionais: CLT, autônomos (PJ), ou ambos? Afeta cálculo de comissão e documentação.
- **Q5**: O DRE mensal deve incluir projeção de despesas fixas (aluguel, energia) mesmo antes de lançamentos reais?
- **Q6**: Qual formato de pagamento (mensal, trimestral, anual) e existência de desconto por pagamento antecipado?
- **Q7**: O sistema deve suportar múltiplas moedas ou apenas BRL? (Assumindo BRL por ora.)
- **Q8**: Qual a estratégia de onboarding? Assistente guiado, vídeos, documentação? Precisa de recursos específicos?