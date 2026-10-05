# Dívida técnica

Fila de coisas conhecidas para limpar. Ao resolver um item, remova-o daqui no mesmo PR.

## Crítico

- [ ] **Versionar schema e triggers.** A regra de negócio (`trg_mv_*`) vive só no banco.
      Gerar `docs/generated/schema.sql` com `pg_dump --schema-only` e criar migrations
      para mudanças futuras. Sem isso o agente não enxerga a lógica principal.
- [ ] **Segredos no código:** `rest_key => 'zooloo_api_key_2025'` e `seed` em
      `app/config/application.php`; `REST_KEY = '123'` em `SystemUserRestService`. Mover para
      env e rotacionar (já estão no histórico do git).
- [ ] CORS `['*']` e `debug` — garantir valores de produção por env.

## Verificação mecânica

- [ ] CI (GitHub Actions) com Postgres de serviço rodando `composer check` no banco `teste`.
- [ ] Análise estática (phpstan nível baixo) no `composer check`.
- [ ] `tests/bilhete-palpites-trigger.integration.php` não casa com o glob `*.test.php` e
      nunca roda. Decidir: renomear ou criar `composer test:integration`.
- [ ] Seeds/fixtures para o banco `teste`.

## Docs e código divergentes

- [ ] **TTL do access token:** código usa 24 h (`ACCESS_TTL_SECONDS = 86400`); docblock da
      classe e docs antigas dizem 15 min. Decidir e alinhar código + docs dos dois repos.
- [ ] `.claude/skills/` duplica as skills `reversa-*` de `.agents/skills/` e não tem a
      `adianti-expert`. Escolher uma fonte só.

## Limpeza de repo

- [ ] Configs sobra do template: `unit_a.php`, `unit_b.php`, `sample.php`, `unit_database.php`
      e `app/database/*.db`.
- [ ] Scripts manuais na raiz: `test-login-api.php`, `test-vendasjb-api.php` — virar teste ou
      `scripts/`.
- [ ] `.idea/` versionado.

## TODOs de produto

- [ ] Ao deixar um Gerente inativo, deixar também o usuário do sistema inativo (parcial na list).
- [ ] `ResultadoForm`: verificar o horário limite da extração (data + hora) antes de salvar.
- [ ] Auth pré-produção: `debug='0'`, `cors_allowed_origins` explícito, binding
      `terminal_id`/serial no JWT, auditoria de refresh/logoutAll.
