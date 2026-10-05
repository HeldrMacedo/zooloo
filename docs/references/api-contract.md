# Contrato REST

Este repo é **dono do contrato**. O app (`../app-zooloo`) registra o que consome em
`../app-zooloo/docs/references/backend-zooloo.md` — atualize os dois no mesmo ciclo.

Base: `http://localhost/rest.php` (dev). Roteador chama `Classe::metodo`.
Envelope: `{ "status": "success"|"error", "data": ... }`.

## Autenticação (`app/service/auth/ApplicationAuthenticationRestService.php`)

| Método | Bearer? | Descrição |
|---|---|---|
| `login` | Não | Devolve `token` (access), `refresh_token`, `user`, `vendedor` + `permissoes` |
| `refreshToken` | Não | Rotaciona o par a partir de `refresh_token` (revoga o antigo) |
| `validateToken` | — | Valida access token (checa `jti` não revogado) |
| `logout` | Sim | Revoga access + refresh persistidos |
| `logoutAll` | Sim | Revoga todos os tokens ativos do usuário |
| `getToken` | — | Legado do template |

Tokens:
- **access:** JWT com `jti`, TTL `ACCESS_TTL_SECONDS` (**hoje 86400 s = 24 h** no código;
  docs antigas diziam 15 min — ver tech-debt).
- **refresh:** JWT com `jti`, TTL `REFRESH_TTL_SECONDS` (30 dias), persistido em
  `mob_auth_token` e revogável. Rotação obrigatória; reuso de refresh já rotacionado revoga a
  árvore inteira (replay detection).
- HS256, chave `APPLICATION_NAME + seed` (`app/config/application.php`).
- Login resolve `system_users.id -> cad_vendedor.usuario_id` e devolve as flags do vendedor.
- Erro de login é genérico (evita enumeração de usuário).
- Rate limit (`AuthRateLimiter`, arquivos em `tmp/ratelimit/`): por login 5/5 min com bloqueio
  de 15 min; por IP 20/5 min. Config em `application.php -> rate_limit`.

Segurança em `rest.php`: CORS por `security.cors_allowed_origins` (`['*']` só em dev); sem
`Authorization` -> 401 JSON; erro interno não vaza mensagem com `debug='0'`.

Doc completa do fluxo (diagrama, checklist de produção):
`../app-zooloo/docs/design-docs/autenticacao.md`.

## Serviços de negócio (`app/service/rest/`)

| Classe | Métodos | Usado pelo app? |
|---|---|---|
| `BilheteRestService` | `registrar`, `cancelar`, `detalhe`, `lista` | só `registrar` |
| `SorteioRestService` | `abertos` | sim |
| `ModalidadeRestService` | `listar`, `disponiveis` | só `listar` |
| `ResultadoRestService` | `recentes` | não |
| `CaixaRestService` | `resumo` | não |
| `VendedorRestService` | `me` | não |
| `TerminalRestService` | `registrar` | não |
| `VendasJbRestService` | `relVendasJb`, `getNsu`, `pegarDetalhesVendas`, `consultaVendasJb`, `buscarNsu`, `buscarDetalhes` | não |
| `SystemUserRestService`, `SystemUserGroupRestService` | CRUD do template Adianti | não |

Testes de contrato: `tests/rest-endpoints-contract.test.php`, `tests/rest-services-validation.test.php`.

Última verificação contra o código: 2026-10-05.
