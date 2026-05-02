# Inventário do Projeto — zooloo

> Gerado pelo Reversa Scout em 2026-04-30
> Confiança: 🟢 CONFIRMADO

---

## Visão Geral

| Campo | Valor |
|---|---|
| **Projeto** | zooloo |
| **Linguagem principal** | PHP 8.2 |
| **Framework** | Adianti Framework 8.1 |
| **Tema/UI** | Bootstrap 5 (`adminbs5`) |
| **Banco de dados** | PostgreSQL 15 |
| **Containerização** | Docker Compose 3.9 |
| **Total de arquivos PHP (app/)** | 207 |
| **Total de arquivos PHP (raiz, sem vendor)** | ~38 |

---

## Estrutura de Diretórios

```
zooloo/
├── app/
│   ├── config/             — Configurações da aplicação e banco de dados
│   ├── control/            — Controllers (telas) do Adianti
│   │   ├── AreaComissaoModalidade/
│   │   ├── ExtracaoDescarga/
│   │   ├── admin/          — Administração do sistema (Adianti padrão)
│   │   ├── area/
│   │   ├── area-cotacao/
│   │   ├── area-extracao/
│   │   ├── area-limite/
│   │   ├── communication/  — Mensagens, docs, posts, agenda
│   │   ├── extracao/
│   │   ├── gerente/
│   │   ├── log/            — Logs do sistema
│   │   ├── modalidade/
│   │   ├── palpite-cotado/
│   │   ├── parametros/
│   │   ├── public/
│   │   ├── resultado/
│   │   └── vendedor/
│   ├── database/           — Scripts DDL e arquivos .db (SQLite auxiliar)
│   ├── model/
│   │   ├── admin/          — Models do sistema Adianti
│   │   ├── communication/  — Models de comunicação
│   │   ├── entities/       — 19 Active Records do negócio
│   │   └── log/            — Models de log
│   ├── service/
│   │   ├── auth/           — Autenticação JWT e LDAP
│   │   ├── cli/            — Serviços CLI
│   │   ├── jobs/           — Jobs agendados
│   │   ├── log/            — Serviços de log
│   │   ├── rest/           — REST services do domínio
│   │   └── system/         — Serviços do sistema Adianti
│   ├── templates/
│   │   └── adminbs5/       — Template Bootstrap 5 (CSS, JS, fontes)
│   └── view/               — Views adicionais
├── lib/
│   ├── adianti/            — Núcleo do Adianti Framework 8.1
│   ├── bootstrap/          — Bootstrap CSS/JS
│   ├── independent/        — Assets independentes
│   ├── jquery/             — jQuery e plugins
│   └── math/
├── rest/                   — Scripts auxiliares REST (get-token, request helpers)
├── tests/                  — Suite de testes PHP (4 arquivos de teste)
├── venda-online/           — Subprojeto independente: portal de venda online
├── allsystem/              — Sistema legado Java/JHipster (referência, não modificar)
├── docs/                   — Documentação (mobile-planejamento.md)
├── vendor/                 — Dependências Composer
├── index.php               — Entry point principal (web)
├── rest.php                — Entry point REST API
├── cmd.php                 — Entry point CLI
├── engine.php              — Motor de roteamento Adianti
├── init.php                — Bootstrap da aplicação
├── menu.xml                — Definição do menu principal
├── docker-compose.yml      — Orquestração Docker
└── Dockerfile              — Imagem PHP 8.2 + Apache
```

---

## Módulos do Negócio

| Módulo | Descrição | Controller | Entidade(s) |
|---|---|---|---|
| **area** | Zona geográfica/franquia | AreaList, AreaForm | Area (`cad_area`) |
| **gerente** | Gerente/Coletor da banca | GerenteList, GerenteForm | Gerente (`cad_coletor`) |
| **extracao** | Sorteio agendado | ExtracaoList, ExtracaoForm | Extracao (`cad_extracao`) |
| **modalidade** | Tipo de aposta | ModalidadeList, ModalidadeForm | Modalidade (`cad_modalidade`) |
| **vendedor** | Ponto de venda | VendedorList, VendedorForm | Vendedor (`cad_vendedor`) |
| **area-cotacao** | Multiplicador de prêmio por área | AreaCotacaoList, AreaCotacaoForm | AreaCotacao (`cfg_area_cotacao`) |
| **area-extracao** | Vinculação área-extração | AreaExtracaoList | AreaExtracao (`cfg_area_extracao`) |
| **area-limite** | Limite de aposta por área | AreaLimiteList, AreaLimiteForm | AreaLimite (`cfg_area_limite`) |
| **AreaComissaoModalidade** | Comissão por área/modalidade | AreaComissaoModalidadeList, Form | AreaComissaoModalidade (`cfg_area_comissao_modalidade`) |
| **ExtracaoDescarga** | Limite de descarga por extração | ExtracaoDescargaList, Form | ExtracaoDescarga (`cfg_extracao_descarga`) |
| **palpite-cotado** | Cotação especial para palpites | PalpiteCotadoList, PalpiteCotadoForm | PalpiteCotado (`cfg_palpite_cotado`) |
| **parametros** | Parâmetros gerais da banca | ParametrosList, ParametrosForm | Parametros (`cfg_parametros`) |
| **resultado** | Registro do resultado do sorteio | ResultadoList, ResultadoForm | MovSorteio (`mov_sorteio`) |
| **rest-api** | REST API JWT para app móvel | rest.php + service/rest/ | BilheteRestService, CaixaRestService, ModalidadeRestService, ResultadoRestService, SorteioRestService, TerminalRestService, VendedorRestService |

### Módulos de Infraestrutura (Adianti padrão)

| Módulo | Descrição |
|---|---|
| **admin** | Usuários, grupos, permissões, roles, programas |
| **communication** | Mensagens, notificações, documentos, posts, wiki, agenda |
| **log** | Access log, SQL log, change log, request log, schedule log |

---

## Entidades do Domínio (Active Records)

| Classe | Tabela | Banco |
|---|---|---|
| Area | `cad_area` | applications |
| Gerente | `cad_coletor` | applications |
| Extracao | `cad_extracao` | applications |
| Modalidade | `cad_modalidade` | applications |
| Vendedor | `cad_vendedor` | applications |
| Terminal | `cad_terminal` | applications |
| AreaExtracao | `cfg_area_extracao` | applications |
| AreaCotacao | `cfg_area_cotacao` | applications |
| AreaLimite | `cfg_area_limite` | applications |
| AreaComissaoModalidade | `cfg_area_comissao_modalidade` | applications |
| ExtracaoDescarga | `cfg_extracao_descarga` | applications |
| PalpiteCotado | `cfg_palpite_cotado` | applications |
| Parametros | `cfg_parametros` | applications |
| IntJogo | `int_jogo` | applications |
| IntCalculoSorteio | `int_calculo_sorteio` | applications |
| MovSorteio | `mov_sorteio` | applications |
| MovJb | `mov_jb` | applications |
| MovJbSorteio | `mov_jb_sorteio` | applications |
| MovJbSortPalpite | `mov_jb_sort_palpite` | applications |

---

## Entry Points

| Arquivo | Tipo | Descrição |
|---|---|---|
| `index.php` | Web | Entry point principal — serve o SPA Adianti |
| `rest.php` | REST API | Roteador REST com suporte a Basic e Bearer JWT |
| `cmd.php` | CLI | Execução de serviços via linha de comando |
| `engine.php` | Motor | Roteamento de requisições Adianti |
| `init.php` | Bootstrap | Carrega autoloader, configurações e sessão |

---

## Configurações

| Arquivo | Propósito |
|---|---|
| `app/config/application.php` | Configuração geral (timezone, tema, JWT seed, rest_key) |
| `app/config/permission.php` | Credenciais PostgreSQL (banco `applications`) |
| `app/config/communication.php` | Configuração de comunicação |
| `app/config/log.php` | Configuração de log |
| `app/config/ldap.ini` | Configuração LDAP (opcional) |
| `app/config/unit_a.php` / `unit_b.php` | Multi-banco (unidades) |
| `docker-compose.yml` | Orquestração dos containers |
| `Dockerfile` | Imagem PHP 8.2 + Apache + extensões |

---

## Banco de Dados — Schemas DDL

| Arquivo | Propósito |
|---|---|
| `app/database/permission.sql` | Schema principal (`applications`) — tabelas do sistema |
| `app/database/communication.sql` | Schema de comunicação |
| `app/database/log.sql` | Schema de log |
| `app/database/permission-update.sql` | Migrations incrementais |
| `app/database/communication-update.sql` | Migrations de comunicação |
| `app/database/log-update.sql` | Migrations de log |

> Análise detalhada do banco: **reversa-data-master**

---

## Cobertura de Testes

| Arquivo | Escopo |
|---|---|
| `tests/auth-rest-service.test.php` | Serviço de autenticação JWT |
| `tests/rest-endpoints-contract.test.php` | Contrato dos endpoints REST |
| `tests/rest-services-validation.test.php` | Validação dos REST services |
| `tests/resultado-form.test.php` | Form de resultado |
| `tests/run.php` | Runner da suite |

**Framework de testes:** PHP nativo (runner próprio, `composer test`)

---

## REST API — Serviços

| Service | Métodos identificados |
|---|---|
| `ApplicationAuthenticationRestService` | login, logout, validateToken, refreshToken |
| `BilheteRestService` | registrar, detalhe, recentes, cancelar |
| `CaixaRestService` | resumo |
| `ModalidadeRestService` | lista |
| `ResultadoRestService` | registrar |
| `SorteioRestService` | abertos, disponiveis |
| `TerminalRestService` | me |
| `VendedorRestService` | — |
| `SystemUserRestService` | — |
| `SystemUserGroupRestService` | — |

**Autenticação:** Bearer JWT (HS256, TTL 1h) ou Basic (API key estática)
**Endpoint base:** `http://localhost/rest.php`


---

## Sistema Legado (Referência)

`allsystem/` — Sistema original Java/Spring Boot com JHipster.
Contém `.jhipster/*.json` com definição das entidades originais.
**Não deve ser modificado.** Usado como referência de schema e regras de negócio.

---

## Banco de Dados (Referência)
    'host'  =>  "postgres",
    'port'  =>  "5432",
    'name'  =>  "jb",
    'user'  =>  "postgres",
    'pass'  =>  "postgres",
    'type'  =>  "pgsql",

## Integrações Externas

| Integração | Biblioteca | Status |
|---|---|---|
| E-mail | PHPMailer ^6.0 | Ativo |
| MongoDB | pecl/mongodb | Instalado no Docker |
| LDAP | Nativo PHP | Configuração presente (`ldap.ini`) |
| reCAPTCHA | RecaptchaServices.php | Desabilitado (`enabled: 0`) |
| OTP/2FA | spomky-labs/otphp ^11.0 | Ativo |
