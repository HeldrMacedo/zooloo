# Binding de Terminal — Backend Zooloo

Documenta o vínculo entre dispositivos de venda (POS, smartphones, coletores) e
`cad_terminal`, usado no login mobile e na emissão de bilhetes.

## Objetivo

Impedir login e vendas em aparelhos não autorizados. O serial do device deve ser
**pré-cadastrado** por um operador no back-office antes do primeiro uso.

## Tabela `cad_terminal`

| Campo | Uso |
| ----- | --- |
| `terminal_id` | PK; enviado pelo app nas apostas |
| `vendedor_id` | Vendedor dono (quando `multi_usuario = N`) |
| `serial` | ID estável do aparelho (até 60 chars) — mesmo valor exibido no app |
| `tipo` | `APP` / `POS` / `COLETOR` |
| `multi_usuario` | `N` = só o dono; `S` = compartilhado |
| `ativo` | `S`/`N` — bloqueio operacional |

## Back-office

Menu: **Cadastros → Terminal**

| Tela | Classe |
| ---- | ------ |
| Listagem | `TerminalList` |
| Formulário | `TerminalForm` |

Valida serial obrigatório, tamanho ≤ 60 e unicidade de serial.

## API REST

### Login (`ApplicationAuthenticationRestService::login`)

Request:

```json
{
  "class": "ApplicationAuthenticationRestService",
  "method": "login",
  "data": {
    "login": "vendedor1",
    "password": "…",
    "serial": "android-id-ou-uuid"
  }
}
```

Fluxo:

1. Rate-limit
2. Autentica usuário/senha (mensagem genérica se falhar)
3. Resolve `cad_vendedor`
4. `TerminalAuthHelper::validateForLogin(serial, vendedor_id)`
5. Emite access + refresh JWT com `terminal_id` e `serial`
6. Resposta inclui objeto `terminal`

### Terminal REST (`TerminalRestService::registrar`)

Confirma serial **já cadastrado** para o vendedor autenticado. **Não cria** registro
(diferente do comportamento antigo de autocadastro).

### Bilhetes (`BilheteRestService`)

`validarTerminal` delega a `TerminalAuthHelper::validateForBilhete`, respeitando
`multi_usuario`.

## Helper compartilhado

`app/service/auth/TerminalAuthHelper.php`

- `findBySerial` / `findById`
- `assertUsable`
- `validateForLogin`
- `validateForBilhete`

## Testes

```bash
php tests/run.php
# ou via Docker:
docker run --rm -v "$PWD":/var/www/html -w /var/www/html php:8.2-cli php tests/run.php
```

Arquivos relevantes:

- `tests/auth-rest-service.test.php` — login com serial / multi_usuario / JWT
- `tests/terminal-auth-helper.test.php` — regras unitárias do helper

## Operação

1. No app, abrir a engrenagem na tela de login e copiar o serial.
2. Cadastrar em **Cadastros → Terminal** com o vendedor correto.
3. Login e vendas liberados apenas naquele device (ou em multi_usuario conforme flag).
4. Para bloquear um aparelho: `ativo = N` na listagem (ação power).
