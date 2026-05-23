# Folder Structure

Este documento define uma estrutura de pastas recomendada para projetos SaaS em PHP usando Laravel, com espaço para evolução para DDD/modularização.

> A ideia é servir de **ponto de partida** consistente; ajustes podem ser feitos conforme o domínio cresce.

## Visão geral da raiz do projeto

Estrutura típica de um projeto Laravel base:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
tests/
vendor/
.env
artisan
composer.json
```

Função de cada diretório (resumo):

- **app/**: código principal da aplicação (domínio, controllers, jobs, etc.).
- **bootstrap/**: bootstrapping do framework, cache de performance.
- **config/**: arquivos de configuração.
- **database/**: migrations, seeders, factories e, opcionalmente, bancos SQLite.
- **public/**: ponto de entrada HTTP (`index.php`) e assets públicos.
- **resources/**: views (Blade), assets brutos (CSS/JS), traduções.
- **routes/**: definições de rotas web, API, console, canais.
- **storage/**: logs, cache, arquivos gerados, uploads.
- **tests/**: testes unitários e de feature.
- **vendor/**: dependências instaladas via Composer (não alterar manualmente).

## Organização recomendada em `app/`

Dentro de `app/`, usar uma estrutura que permita crescer para DDD sem perder a familiaridade com Laravel.

### Layout base

```text
app/
  Console/
  Domain/
  Exceptions/
  Http/
    Controllers/
    Middleware/
    Requests/
    Resources/
  Infrastructure/
  Models/
  Providers/
  Rules/
```

- **Console/**: comandos Artisan personalizados.
- **Domain/**: código de negócio puro (entidades, serviços, regras, use cases, etc.).
- **Exceptions/**: exceções específicas do domínio e da aplicação.
- **Http/**:
  - **Controllers/**: controladores HTTP, finos e focados em orquestrar fluxos.
  - **Middleware/**: middlewares HTTP.
  - **Requests/**: Form Requests com validação.
  - **Resources/**: API Resources para serialização.
- **Infrastructure/**: integrações externas, adaptadores, serviços de infraestrutura (pagamentos, fila externa, storage externo, etc.).
- **Models/**: models Eloquent, preferencialmente finos (apenas persistência e relações básicas).
- **Providers/**: service providers.
- **Rules/**: validation rules customizadas.

## Organização do domínio (`app/Domain`)

O domínio pode ser dividido por **subdomínios** ou **módulos** funcionais, por exemplo:

```text
app/Domain/
  Billing/
    Entities/
    Services/
    Repositories/
    Policies/
    DTOs/
    Events/
  Tenancy/
    Entities/
    Services/
    Repositories/
  Users/
    Entities/
    Services/
    Repositories/
    Policies/
```

- **Entities/**: entidades de domínio (podem ou não ser Eloquent, dependendo da abordagem).
- **Services/**: regras de negócio, processos, orquestração de casos de uso.
- **Repositories/**: interfaces e implementações para acesso a dados.
- **DTOs/**: objetos de transporte de dados.
- **Policies/**: policies específicas daquele domínio.
- **Events/**: eventos de domínio.

> Em projetos menores, parte dessas pastas pode ser omitida; o importante é manter o agrupamento por domínio.

## Controllers e rotas

- Controllers ficam em `app/Http/Controllers`.
- Agrupar controllers por contexto funcional, por exemplo:

```text
app/Http/Controllers/
  Api/
    V1/
      Billing/
      Tenancy/
      Users/
  Web/
    Dashboard/
    Settings/
```

- Rotas em `routes/`:
  - `routes/web.php`: rotas web (HTML, Blade).
  - `routes/api.php`: rotas API (JSON, REST).
  - `routes/console.php`: comandos Artisan.

## Migrations, seeders e factories

Em `database/`:

```text
database/
  migrations/
  seeders/
  factories/
```

- Nomear migrations de forma descritiva (Laravel já prefixa com timestamp).
- Manter seeders por domínio/contexto quando fizer sentido.
- Factories para gerar dados de teste e fixtures.

## Tests

Organizar `tests/` refletindo a estrutura da aplicação:

```text
tests/
  Unit/
    Domain/
      Billing/
      Tenancy/
  Feature/
    Http/
      Api/
      Web/
```

- Testes unitários cobrindo domínio e serviços.
- Testes de feature para fluxos de controllers, rotas e integrações principais.

## Assets, views e frontend

- Views Blade em `resources/views/`, organizadas por módulo/contexto.
- Assets (CSS/JS) em `resources/css` e `resources/js` (ou na estrutura escolhida pelo bundler).
- Conteúdo compilado em `public/` via Vite/Mix ou outra ferramenta.

## Arquivos de suporte e documentação interna

É útil ter um diretório para documentação do projeto, por exemplo:

```text
docs/
  code-standards.md
  folder-structure.md
  architecture.md
  decisions/
    0001-initial-architecture.md
```

- `code-standards.md`: padrões de código do projeto.
- `folder-structure.md`: este documento.
- `architecture.md`: visão de alto nível da arquitetura.
- `decisions/`: registro de decisões de arquitetura (ADR – Architecture Decision Records).

## Ajustes por projeto

Dependendo da complexidade do SaaS, podem ser adicionadas outras pastas de primeiro nível, por exemplo:

```text
app/
  Domain/
  Application/   # Casos de uso / orquestração
  Infrastructure/
  Interfaces/    # Adaptadores de entrada (HTTP, CLI, etc.)
```

Ou ainda organizar por **bounded context** maior quando o sistema crescer (ex.: `Backoffice`, `Subscriptions`, `Integrations`), sempre mantendo a mesma filosofia:

- Agrupar o que muda pelo mesmo motivo.
- Facilitar encontrar código de um domínio específico.
- Separar domínio de infraestrutura.

---

Use esta estrutura como base e adapte à realidade do projeto, registrando exceções e convenções adicionais aqui conforme a aplicação evoluir.
