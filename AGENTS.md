# AGENTS.md — Zooloo (backend)

Mapa para agentes de IA (Claude, Codex, Gemini, Kilo...). Curto de propósito: aponta para onde
o conhecimento mora. Não copie detalhes aqui — atualize o doc de destino.

Reescrita em PHP 8.2 / **Adianti Framework 8.1** / PostgreSQL 15 do sistema legado
**allsystem** (JHipster/Java, em `../jballsystem/allsystem`). Gestão de banca de Jogo do Bicho e
derivados (Bilhetinho, Quininha, Seninha, Lotinha). Atende o painel web e o app mobile
`../app-zooloo` via REST + JWT.

## Regra de ouro

Este repo (PHP + triggers PostgreSQL) é **a única fonte** de cálculo de prêmio, comissão,
limite, descarga e verificação de ganhador. O app mobile só envia dados brutos e relê o que
devolvemos. Mudou regra de negócio? Muda aqui, com teste.
Detalhes: [docs/design-docs/golden-principles.md](docs/design-docs/golden-principles.md).

## Onde está cada coisa

| Preciso de... | Leia |
|---|---|
| Camadas, pastas, conexões de banco, padrões Adianti | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Invariantes (o que nunca fazer) | [docs/design-docs/golden-principles.md](docs/design-docs/golden-principles.md) |
| Glossário do negócio, tipos de jogo | [docs/product-specs/dominio.md](docs/product-specs/dominio.md) |
| Paridade com o allsystem (backlog de telas) | [docs/references/comparativo-allsystem-zooloo.md](docs/references/comparativo-allsystem-zooloo.md) |
| Contrato REST (classes, métodos, auth, envelope) | [docs/references/api-contract.md](docs/references/api-contract.md) |
| Tabelas, prefixos, triggers | [docs/references/schema-banco.md](docs/references/schema-banco.md) |
| Onde achar cada coisa no legado Java | [docs/references/legacy-allsystem.md](docs/references/legacy-allsystem.md) |
| Binding de terminal/serial no login mobile | [docs/design-docs/terminal-binding.md](docs/design-docs/terminal-binding.md) |
| Decisões de arquitetura (ADRs) | [docs/design-docs/adr/](docs/design-docs/adr/) |
| O que está em andamento / feito | [docs/exec-plans/README.md](docs/exec-plans/README.md) |
| Dívida técnica e TODOs | [docs/exec-plans/tech-debt.md](docs/exec-plans/tech-debt.md) |
| Nota de qualidade por área | [docs/QUALITY_SCORE.md](docs/QUALITY_SCORE.md) |
| Como usar o Adianti (fontes, busca) | [.agents/rules/adianti.md](.agents/rules/adianti.md) |

Regra de negócio não documentada? Consulte o legado (`legacy-allsystem.md`) antes de inventar.

## Comandos

Não há PHP no host — tudo roda nos containers.

```bash
docker compose up -d                                               # sobe www, postgres, php
docker exec -i applications_www sh -lc "cd /var/www/html && composer check"   # lint + testes + docs
docker exec -i applications_www sh -lc "cd /var/www/html && composer test"    # só testes
docker exec -i applications_www sh -lc "cd /var/www/html && php -r \"require 'tests/bootstrap.php'; require 'tests/NOME.test.php'; exit(runTests());\""
docker exec applications_db psql -U postgres -d applications       # banco de dev
docker exec applications_db psql -U postgres -d teste              # banco dos testes
docker exec applications_db psql -U postgres -d jb                 # legado (só leitura/referência)
```

Painel web: `http://localhost`. REST: `http://localhost/rest.php`.

## Como trabalhar aqui

1. **Antes de codar:** ache o exec-plan ativo em `docs/exec-plans/active/`. Trabalho com mais
   de um passo ganha um plano novo ali (`AAAA-MM-DD-assunto.md`).
2. **Specs/designs** em `docs/design-docs/`; **planos** em `docs/exec-plans/active/` — também
   quando uma skill sugerir `docs/superpowers/...`.
3. **Mudou contrato REST** (classe, método, payload, resposta)? Atualize
   `docs/references/api-contract.md` e avise o app: `../app-zooloo/docs/references/backend-zooloo.md`.
4. **Mudou schema/trigger?** Crie migration em `app/migrations/` e registre em `schema-banco.md`.
5. **Ao terminar:** `composer check` verde, plano movido para `completed/`, `tech-debt.md`
   atualizado. Doc que contradiz o código é bug — corrija no mesmo PR.

## Convenções rápidas

- Projeto roda **Adianti 8.1** (`lib/VERSION`). Exemplos 8.4 servem de referência; confirme que
  a API existe em `lib/adianti/` antes de usar.
- Transação: `TTransaction::open('permission')` + `close()`/`rollback()` em `try/catch`.
- Testes: harness próprio (`tests/bootstrap.php`), arquivos `tests/*.test.php`. Mocks de classes
  Adianti **sempre** com guarda `if (!class_exists(__NAMESPACE__ . '\\X', false))`.
- Testes automatizados usam o banco `teste` (`APP_ENV=test`), nunca `applications`.
- Datas de `system_*` voltam como string.
- Não confundir `app/service/rest/` (serviços de negócio) com `rest/` na raiz (roteador baixo nível).

## Repositórios relacionados

- `../app-zooloo` — app mobile Expo (consumidor do contrato). Mapa: `../app-zooloo/AGENTS.md`.
- `../jballsystem/allsystem` — legado Java. Só referência; não roda.
- `/home/helder/Desenvolvimento/Adianti8.4` — tutor e framework 8.4 para consulta.

## Artefatos gerados (não editar à mão)

`docs/generated/`, `tmp/`, `vendor/`.
