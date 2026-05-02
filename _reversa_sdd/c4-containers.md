# C4 — Nível 2: Containers

> Gerado pelo Reversa Architect em 2026-04-30
> Confiança: 🟢 CONFIRMADO

```mermaid
C4Container
    title Diagrama de Containers — Zooloo

    Person(admin, "Administrador / Gerente", "Interface web")
    Person(vendedor, "Vendedor", "App móvel")

    System_Boundary(zooloo_sys, "Zooloo System") {
        Container(web_app, "Aplicação Web (Adianti SPA)", "PHP 8.2 + Apache", "Interface administrativa. Entry point: index.php. Serve o SPA Adianti com Bootstrap 5. Roteamento via engine.php")
        Container(rest_api, "REST API", "PHP 8.2 + Apache", "API para o app móvel. Entry point: rest.php. Autenticação JWT HS256. Roteamento direto por classe+método")
        Container(cli, "CLI / Jobs", "PHP 8.2 CLI", "Serviços de linha de comando e jobs agendados. Entry point: cmd.php")
    }

    System_Boundary(db_sys, "Banco de Dados") {
        ContainerDb(db_applications, "Banco applications", "PostgreSQL 15", "Dados do sistema: usuários, grupos, permissões, logs, configuração do Adianti. E também dados de negócio: cad_*, cfg_*, mov_*, int_*")
        ContainerDb(db_jb, "Banco jb (legado)", "PostgreSQL 15", "Schema original do AllSystem Java. Mesmo conteúdo de negócio. Referência de compatibilidade")
    }

    Rel(admin, web_app, "Usa", "HTTPS")
    Rel(vendedor, rest_api, "Usa", "HTTPS / JWT Bearer")

    Rel(web_app, db_applications, "Lê e grava", "TCP 5432 / conexão 'permission'")
    Rel(rest_api, db_applications, "Lê e grava", "TCP 5432 / conexão 'permission'")
    Rel(cli, db_applications, "Lê e grava", "TCP 5432")

    Rel(db_applications, db_jb, "Mirrors/compartilha schema de negócio", "Mesmo servidor PostgreSQL")
```

## Detalhes dos Containers

### Aplicação Web (index.php → engine.php)

| Aspecto | Detalhe |
|---|---|
| **Tecnologia** | PHP 8.2 + Apache + Adianti Framework 8.1 |
| **Tema** | Bootstrap 5 (`adminbs5`) |
| **Roteamento** | `engine.php` — parâmetro `?class=NomeController` |
| **Sessão** | TSession (PHP Session + Adianti wrapper) |
| **Padrão de tela** | TPage com BootstrapFormBuilder / BootstrapDatagridWrapper |

### REST API (rest.php)

| Aspecto | Detalhe |
|---|---|
| **Tecnologia** | PHP 8.2 + Apache |
| **Roteamento** | `?class=NomeService&method=nomeMetodo` |
| **Autenticação** | Bearer JWT (HS256, TTL 1h) ou Basic (API key) |
| **Formato** | JSON (request e response) |
| **Base URL** | `http://localhost/rest.php` |

### CLI (cmd.php)

| Aspecto | Detalhe |
|---|---|
| **Tecnologia** | PHP 8.2 CLI |
| **Uso** | Jobs agendados, utilitários de manutenção |
| **Serviços** | `app/service/cli/` e `app/service/jobs/` |

### Banco applications

| Aspecto | Detalhe |
|---|---|
| **Prefixo system_*** | Tabelas do Adianti (usuários, grupos, permissões, programas, logs) |
| **Prefixo cad_*** | Cadastros de negócio |
| **Prefixo cfg_*** | Configurações de negócio |
| **Prefixo mov_*** | Movimentações (bilhetes, sorteios) |
| **Prefixo int_*** | Dados internos/seed (jogos, cálculos) |
| **Conexão PHP** | Nome `'permission'` em TTransaction::open() |
