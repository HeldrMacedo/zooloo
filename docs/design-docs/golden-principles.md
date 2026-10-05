# Princípios de ouro

Invariantes do backend. Cada item diz **como é verificado**. "Manual" = ainda sem checagem
automática; candidato a teste ou lint (ver `docs/exec-plans/tech-debt.md`).

## 1. Regra de negócio mora aqui, não no cliente

Prêmio, comissão, cotação, limite, descarga e ganhador são calculados no PHP ou nas triggers
PostgreSQL. A resposta REST devolve os valores calculados para o app exibir.

- **Verificação:** testes em `tests/` (ex.: `bilhete-premio-colocacao-10.test.php`, resultados
  de quininha/seninha/lotinha).

## 2. Testes nunca tocam o banco de dev

Testes rodam com `APP_ENV=test` -> banco `teste`. O banco `jb` nunca é escrito.

- **Verificação:** `tests/bootstrap.php` força `APP_ENV=test` e `DATABASE_NAME=teste`.

## 3. Contrato REST tem dono e registro

Classe/método/payload novo ou alterado entra em `docs/references/api-contract.md` no mesmo PR,
com teste em `tests/rest-endpoints-contract.test.php`.

- **Verificação:** teste de contrato (parcial) + revisão.

## 4. Schema muda por migration

Alteração de tabela ou trigger vira arquivo em `app/migrations/`. Nada de mudar só no banco vivo.

- **Verificação:** manual. **Hoje violado**: triggers de negócio não estão versionadas.

## 5. Transações sempre fechadas

`TTransaction::open('permission')` dentro de `try/catch`, com `close()` no sucesso e
`rollback()` no erro.

- **Verificação:** manual.

## 6. Adianti 8.1

Não usar API que só existe na 8.4. Confirmar em `lib/adianti/`.

- **Verificação:** `php -l` não pega isso; revisão + teste da tela.

## 7. Sem segredo no código

Chaves, senhas e seeds vêm de variáveis de ambiente.

- **Verificação:** manual. **Hoje violado** (`rest_key`, `seed` em `application.php`;
  `REST_KEY` em `SystemUserRestService`).

## 8. Documentação é código

Links da base resolvem; `AGENTS.md` fica curto.

- **Verificação:** `composer docs:check`.
