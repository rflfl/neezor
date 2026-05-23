# Coding Standards

Este documento define os padrões de código recomendados para projetos SaaS em PHP, com foco em legibilidade, consistência e facilidade de manutenção.

> A ideia é ser **base comum** para todos os projetos, mas você pode especializar por projeto quando necessário.

## Linguagem, versão e modo estrito

- PHP 8.2+ (idealmente 8.3 em novos projetos).
- Todos os arquivos PHP devem declarar `strict_types` no topo.

```php
<?php

declare(strict_types=1);
```

- Abrir arquivos PHP **sempre** com `<?php` e **não** fechar com `?>` em arquivos somente PHP.

## PSR e estilo de código

- Seguir:
  - **PSR-1** – Basic Coding Standard.
  - **PSR-12** – Extended Coding Style Guide.
  - **PSR-4** – Autoloading.
- Usar uma ferramenta automática para garantir o estilo:
  - **Laravel Pint** ou **PHP-CS-Fixer** com preset PSR-12.
  - Rodar o formatter como parte do CI.

### Regras principais (resumo PSR-12)

- Encoding UTF-8 sem BOM.
- Uma classe por arquivo.
- `namespace` na primeira linha após `declare(strict_types=1)`.
- `use` agrupados e ordenados alfabeticamente.
- Chaves `{}` na **mesma linha** de classes, métodos e funções.
- Indentação com 4 espaços (sem tab).
- Linhas preferencialmente até 120 colunas.
- Uma declaração por linha (variáveis, `use`, etc.).

## Organização de classes e nomes

- Namespaces seguindo PSR-4.
- Classes, interfaces, traits e enums em arquivos separados.
- Convenções de nomes:
  - Classes: `PascalCase` (`UserService`, `WebhookController`).
  - Métodos: `camelCase` (`createOrder`, `handleWebhook`).
  - Propriedades: `camelCase`.
  - Constantes: `UPPER_SNAKE_CASE`.

### Sufixos e prefixos recomendados

- Controllers: `*Controller` (ex.: `SalesReportController`).
- Requests (Form Requests): `*Request`.
- Resources (API Resources): `*Resource`.
- Actions/UseCases (quando usados): `*Action` ou `*UseCase`.
- Services de domínio: `*Service`.
- Jobs: `*Job`.
- Listeners: `*Listener`.
- Policies: `*Policy`.
- Rules (validation rules): `*Rule`.

## Tipagem e nullability

- Usar **tipos nativos** em parâmetros, propriedades e retornos sempre que possível.
- Evitar `mixed` e `array` genérico; preferir objetos/DTOs ou coleções tipadas.
- Nullability explícita:
  - Prefira `?Tipo` apenas quando o `null` fizer sentido semântico.
  - Em vez de `?string` para “talvez string”, considere **Value Objects** ou estados mais explícitos.

## Erros, exceções e controle de fluxo

- Preferir **exceções** a códigos de erro silenciosos.
- Não engolir exceções sem log ou tratamento adequado.
- Usar exceções específicas por domínio (ex.: `InsufficientBalanceException`).
- Nunca usar `die`, `exit`, `var_dump`, `dd` em código de produção.

## Funções, métodos e responsabilidade

- Métodos devem ter responsabilidade única ou pelo menos muito clara.
- Tamanho recomendado de método: pequeno o suficiente para caber na tela e ser entendido sem rolagem extensa.
- Evitar funções com muitos parâmetros (4+). Preferir DTO/Value Object.
- Evitar métodos estáticos para lógica de domínio; preferir objetos e injeção de dependência.

## Injeção de dependências

- Preferir **injeção de dependência via construtor** ou método dedicado.
- Evitar acoplamento forte com facades estáticas, exceto onde fizer sentido (ex.: `Log`, `Cache` pontuais).
- Para serviços complexos, registrar no container e tipar via interface.

## Banco de dados e Eloquent

- Regra geral: lógica de domínio não deve ficar dispersa em controllers.
- Para Laravel:
  - Usar **Eloquent Models** apenas para persistência, relações e atributos básicos.
  - Lógica de negócio complexa deve ir para Services, Actions ou Domain classes.
  - Evitar queries mágicas em controllers; preferir Repositories ou Query Objects.
- Sempre validar entrada antes de persistir.
- Ao escrever migrations:
  - Ser explícito em constraints, índices e tipos.
  - Evitar mudanças destrutivas sem plano de migração seguro.

## Autenticação, autorização e multi-tenant

- Nunca confiar apenas em validação no frontend.
- Autorização sempre checada no backend:
  - Policies, Gates ou middlewares apropriados.
- Em cenários multi-tenant:
  - Qualquer query ou operação sensível deve estar claramente escopada por tenant.
  - Evitar uso de `tenant_id` direto hardcoded; centralizar lógica de scoping.

## Logging e observabilidade

- Usar logs estruturados para eventos importantes (ex.: billing, autenticação, erros de integração).
- Nunca registrar dados sensíveis em logs (senhas, tokens, dados bancários, PII além do necessário).
- Em exceções, registrar contexto relevante (tenant, usuário, endpoint) sem expor segredos.

## Testes

- Preferir **Pest** ou **PHPUnit** com estrutura organizada.
- Testes unitários para domínio, serviços e regras críticas.
- Testes de feature/integrados para fluxos principais (ex.: criação de pedido, fluxo de pagamento).
- Testes sempre que alterar regras de negócio sensíveis.
- Para bugs corrigidos, criar teste que falha antes da correção e passa depois.

## Estilo de commits e PRs (opcional, se usar)

- Commits pequenos e semânticos.
- Mensagens de commit claras (ex.: `fix: corrige cálculo de comissão` em vez de `ajustes`).
- Pull requests focadas em um conjunto pequeno de mudanças, com descrição objetiva.

---

Este documento pode ser estendido por projeto para definir regras adicionais de domínio, convenções de nomenclatura específicas e exceções justificadas.
