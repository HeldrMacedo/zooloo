# Guia: como programar zooloo + app-zooloo com IA

Como usar a base de conhecimento (formato *harness engineering*) para desenvolver os dois
repositórios com agentes de IA e mantê-la atualizada.

- `zooloo` (este repo) — backend PHP/Adianti. **Dono do contrato REST e das regras de negócio.**
- `../app-zooloo` — app mobile Expo. Consumidor do contrato.

Cada repo tem a mesma estrutura: `AGENTS.md` (mapa), `ARCHITECTURE.md`, `docs/product-specs/`,
`docs/design-docs/`, `docs/exec-plans/`, `docs/references/`, `docs/QUALITY_SCORE.md`.

## Abrindo a sessão

O agente lê o `AGENTS.md` sozinho (o `CLAUDE.md` só faz `@AGENTS.md`).

Feature que mexe só em um repo: abra a sessão nele.

Feature que mexe nos dois: abra a partir do backend, que é o dono do contrato:

```bash
cd ~/Desenvolvimento/zooloo && claude --add-dir ../app-zooloo
```

## Fluxo de uma feature em um repo só

Exemplo: tela de histórico de bilhetes no app.

1. **Plano antes de código.**
   > "Crie um exec-plan em `docs/exec-plans/active/` para a tela de histórico de bilhetes
   > usando `BilheteRestService::lista`."
2. **Revise o plano** — ele fica versionado no repo.
3. **Execute.**
   > "Execute o plano `docs/exec-plans/active/2026-10-xx-historico.md`."

   O agente registra o andamento no **Log de progresso** do próprio plano, a cada sessão.
4. **Feche.**
   - `npm run check` (app) ou `composer check` (backend) verde.
   - `git mv` do plano para `docs/exec-plans/completed/`.
   - No app, mover o card no `docs/exec-plans/roadmap.md`.

## Fluxo de uma feature que envolve os dois repos

Exemplo: cancelar bilhete pelo app. Ordem sempre **backend → app**.

1. **zooloo**
   - Plano em `docs/exec-plans/active/`.
   - Endpoint + teste em `tests/` (`rest-endpoints-contract.test.php` quando for contrato).
   - Atualizar `docs/references/api-contract.md`.
   - `composer check` verde.
2. **app-zooloo**
   - Plano em `docs/exec-plans/active/` com link para o plano do backend.
   - Atualizar `docs/references/backend-zooloo.md` e `types/aposta.ts`.
   - Service → tela → testes em `__tests__/`.
   - `npm run check` verde.

## Bugs

> "Reproduza e corrija o bug X usando o skill `systematic-debugging`."

Se o bug revelou uma regra que faltava ("nunca faça X"), registre em
`docs/design-docs/golden-principles.md` ou em `docs/exec-plans/tech-debt.md` para virar
teste/lint depois.

## Decisões de arquitetura

Crie uma ADR em `docs/design-docs/adr/ADR-NNN-assunto.md` (formato em
`../app-zooloo/templates/nova-decisao-arquitetural.md`: Contexto, Decisão, Consequências,
Alternativas). ADR aceita não é editada — se mudar, crie outra e marque a antiga como substituída.

ADRs pendentes hoje (ver `tech-debt.md`): TTL do access token; limite entre "total estimado para
UX" e "valor oficial" no app.

## Quando atualizar cada arquivo

| Mudou... | Atualize |
|---|---|
| Pasta, camada, fluxo de dados | `ARCHITECTURE.md` |
| Regra de jogo, modalidade, formato de `palpites` | `docs/product-specs/` |
| Classe/método REST, payload, resposta | `docs/references/api-contract.md` (zooloo) **e** `docs/references/backend-zooloo.md` (app) |
| Tabela ou trigger | migration em `app/migrations/` + `docs/references/schema-banco.md` |
| Nova invariante ("nunca faça X") | `docs/design-docs/golden-principles.md`, dizendo como é verificada |
| Algo ficou para depois | `docs/exec-plans/tech-debt.md` |
| Fechou um plano ou uma área | nota em `docs/QUALITY_SCORE.md` |
| Doc novo importante | uma linha na tabela do `AGENTS.md` (só o ponteiro) |

## Regras para a base não apodrecer

- **`AGENTS.md` e `CLAUDE.md` só apontam.** Detalhe vai no doc de destino. O `AGENTS.md` tem
  limite de 150 linhas, verificado pelo `docs:check`.
- **Doc que contradiz o código é bug.** Corrija no mesmo commit. Hábito útil: terminar todo
  pedido ao agente com "atualize os docs afetados".
- **O `check` é o gate.** Hoje ele verifica sintaxe/lint, testes, links quebrados e tamanho do
  `AGENTS.md`. O resto ainda depende de revisão — cada regra que virar teste ou lint sai dessa
  lista de "manual" em `golden-principles.md`.
- **Specs e planos ficam nas pastas do harness.** Skills que sugerem `docs/superpowers/...`
  devem usar `docs/design-docs/` (specs) e `docs/exec-plans/active/` (planos).

## Limpeza recorrente (doc-gardening)

Uma vez por semana (manual ou agendado com `/schedule`):

> "Faça doc-gardening: compare `ARCHITECTURE.md`, `api-contract.md`/`backend-zooloo.md` e
> `tech-debt.md` com o código atual dos dois repos, corrija o que divergir e atualize o
> `QUALITY_SCORE.md`."

## Comandos de verificação

```bash
# backend (não há PHP no host)
docker exec -i applications_www sh -lc "cd /var/www/html && composer check"

# app
cd ../app-zooloo && npm run check
```

## Próximo item crítico

Versionar o schema e as triggers do zooloo (`pg_dump --schema-only` em
`docs/generated/schema.sql` + migrations). Hoje a regra de negócio só existe no banco vivo —
é o maior ponto cego para a IA.
