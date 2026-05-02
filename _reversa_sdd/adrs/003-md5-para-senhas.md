# ADR-003: MD5 sem salt para hashing de senhas de Gerentes e Vendedores

**Status:** 🔄 Resolvido — migração para bcrypt aprovada (2026-05-01)
**Data:** 2025-09-01 (commit `f0b0032`, adicionado com GerenteForm)
**Confiança:** 🟢 CONFIRMADO

---

## Contexto

Ao criar contas de Gerente e Vendedor, o sistema precisa gerar credenciais de acesso para o SystemUser correspondente. O Adianti Framework usa `$2y$10$...` (bcrypt) para o usuário padrão no SQL de seed. Porém, o código de criação via formulário usa MD5.

## Decisão

Usar `md5($senha)` sem salt para armazenar a senha dos usuários criados automaticamente via `GerenteForm::onSave` e `VendedorForm::onSave`.

## Evidência

```php
// GerenteForm.php
$user->password = md5($param['password'] ?? '');

// VendedorForm.php
$user->password = md5($param['password'] ?? '');
```

## Consequências

- **🔴 Vulnerabilidade:** MD5 sem salt é susceptível a rainbow tables e ataques de dicionário.
- **🔴 Inconsistência:** Usuário `admin` usa bcrypt (via SQL de seed), Gerentes e Vendedores usam MD5.
- **Negativo:** MD5 é considerado criptograficamente quebrado para fins de hashing de senhas.

## Decisão de Correção (2026-05-01)

**Aprovado pelo stakeholder:** migrar para bcrypt imediatamente.

```php
// Antes (vulnerável)
$user->password = md5($param['password'] ?? '');

// Depois (seguro)
$user->password = password_hash($param['password'] ?? '', PASSWORD_BCRYPT);
```

A autenticação em `ApplicationAuthenticationService::authenticate()` deve usar `password_verify()`. O Adianti Framework suporta este fluxo nativamente.

**Arquivos a alterar:**
- `app/control/gerente/GerenteForm.php` — `onSave()`
- `app/control/vendedor/VendedorForm.php` — `onSave()`
- `app/service/auth/ApplicationAuthenticationService.php` — `authenticate()`

**Atenção:** senhas existentes em MD5 precisarão ser resetadas ou re-hashadas em uma migration.
