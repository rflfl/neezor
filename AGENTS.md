# Neezor — Salon Management SaaS

## Estado atual

**Projeto não-scaffoldado.** Não existe `composer.json`, `package.json`, migrations, nem estrutura Laravel/php. Antes de implementar qualquer feature, o projeto precisa ser scaffoldado com `composer create-project laravel/laravel`.

---

## Stack pretendida

- **Backend:** PHP 8.x · Laravel (multi-tenant) · Jetstream/Inertia
- **Frontend:** Vue 3 · Inertia.js · shadcn-vue · Tailwind CSS
- **Testes:** Pest ou PHPUnit · PHPStan
- **DB:** PostgreSQL ou MySQL
- **Ambiente:** Docker

---

## Setup (após scaffold)

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
docker compose up -d
php artisan migrate
php artisan db:seed   # se existirem seeders
php artisan serve && npm run dev
```

---

## Verificação pós-implementação

```bash
php artisan test          # ou ./vendor/bin/pest
phpstan analyse           # se configurado
npm run lint              # se configurado
```

---

## Arquitetura (regras fixas, independente de scaffold)

- **Organização por domínios de negócio** (não por camadas técnicas):
  - `Domain/Scheduling` — agenda e horários
  - `Domain/Cashbox` — caixa diário, lançamentos, fechamento
  - `Domain/Commission` — regras de comissão e rateio
  - `Domain/Customers` — clientes
  - `Domain/Staff` — profissionais/prestadores
  - `Domain/Services` — serviços/procedimentos

- **Regra de negócio vive no domínio.** Controllers/commands/jobs apenas orquestram.
- **Frontend:** `resources/js/Pages/**` para páginas Inertia, `resources/js/Components/**` para componentes.
- **Design:** ler `.DESIGN.md` para tokens, componentes e layouts (criar quando necessário).
- **Autenticação:** Jetstream/Inertia — não alterar lógica core.

---

## Multi-tenancy (regra inegociável)

- Toda query/lançamento deve ser filtrado por `tenant_id` (salão).
- Proibido acessar ou retornar dados de outro tenant.
- Dados de um salão jamais devem vazar para outro, mesmo em dev.

---

## Regras de negócio financeiras (se aplicadas)

- Qualquer alteração em cálculo de comissão exige:
  1. Atualizar spec em `docs/sdd/*`
  2. Adicionar/cobertura de testes
- Abertura/fechamento de caixa: todo lançamento deve ter referência a atendimento/profissional/origem.
- Ajustes manuais devem ser rastreáveis (motivo + responsável).

---

## Fontes de verdade

| Arquivo | Conteúdo |
|---|---|
| `.agents/rules/rules.md` | Regras fixas de arquitetura, multi-tenancy, segurança |
| `.agents/rules/code-standards.md` | Padrões PHP/PSR-12 |
| `.agents/rules/folder-structure.md` | Estrutura de pastas recomendada |
| `.docs/PRD.md` | Visão de produto, personas, funcionalidades MVP |
| `.DESIGN.md` | Sistema de design (tokens, componentes, layout) |
| `.opencode.json` | Configuração de agentes (build, plan, code-reviewer) |
| `.agents/skills/*` | Workflows de agentes especializados (create-prd, execute-task, review-round, etc.) |

---

## Proibido

- Criar lógica de negócio fora de `Domain/*`
- Introduzir stack frontend adicional (React, Alpine, etc.)
- Logar dados sensíveis (senhas, tokens, dados bancários, documentos)
- Manter código morto ou comentado "para uso futuro"
- Alterar arquivos de CI/CD sem autorização explícita