# CLAUDE.md — Projeto Zooloo

## Visão Geral

**Zooloo** é uma reescrita em PHP do sistema **allsystem** (originalmente Java/Spring Boot com JHipster). Trata-se de um sistema de gestão de banca de loteria — especificamente **Jogo do Bicho** e modalidades derivadas (Bilhetinho, Quininha, Seninha, Lotinha, etc.).

O sistema original fica em `C:/desenvolvimento/allsystem/jballsystem/allsystem/` (Java/JHipster). O novo sistema zooloo usa o **Adianti Framework 8.1** com PHP e mantém compatibilidade total com o banco de dados do sistema original.

---

## Stack e Ambiente

| Componente | Tecnologia |
|---|---|
| Framework PHP | Adianti Framework 8.1 |
| Tema | Bootstrap 5 (`adminbs5`) |
| Banco de dados | PostgreSQL 15 |
| Auth REST | JWT (`firebase/php-jwt`) |
| PDF | DomPDF + Adianti PDF Designer |
| Containerização | Docker Compose |

### Containers Docker

```
applications_www   — Apache/PHP web server  (ports 80, 443, 8000)
applications_db    — PostgreSQL 15          (port 5432)
zooloo-php-1       — PHP CLI container
```

### Bancos de Dados

| Banco | Uso |
|---|---|
| `applications` | Banco do zooloo (PHP) — tabelas do sistema e do negócio |
| `jb` | Banco legado do allsystem (Java) — mesmo schema de negócio |

Credenciais em `app/config/permission.php`:
- host: `postgres` (dentro do Docker), `localhost` (externamente)
- port: `5432`, user: `postgres`, pass: `postgres`

---

## Domínio do Negócio

Sistema de **banca de Jogo do Bicho** com os seguintes conceitos centrais:

| Termo | Significado |
|---|---|
| **Área** | Zona geográfica/franquia da banca |
| **Extração** | Sorteio agendado (define dias da semana e hora limite para apostas) |
| **Modalidade** | Tipo de aposta (Milhar, Centena, Dezena, Grupo, Duque, Terno, etc.) |
| **Vendedor** | Ponto de venda de bilhetes, vinculado a uma Área |
| **Coletor** | Supervisor/gerente de vendedores, vinculado a uma Área |
| **Bilhete/JB** | Registro de aposta (`mov_jb` = jogo do bicho, `mov_bilhetinho` = bilhetinho) |
| **Palpite** | Número apostado em um bilhete |
| **Sorteio** | Ocorrência de uma extração em uma data específica (`mov_sorteio`) |
| **Resultado** | Números sorteados registrados no `mov_sorteio.numeros_sorteados` |
| **Cotação** | Multiplicador do prêmio por área/modalidade (`cfg_area_cotacao`) |
| **Comissão** | Percentual do vendedor sobre as vendas |
| **Descarga** | Limite de apostas por número para controle de risco (`cfg_extracao_descarga`) |

### Tipos de Jogo (`int_jogo`)

Os jogos são seeded na tabela `int_jogo`. Exemplos:
- `BIL` = Bilhetinho
- `MBP` = Milhar Brinde Progressiva
- `QUI` = Quininha
- `SEN` = Seninha
- `LOT` = Lotinha
- `DD3Li` = Duque de Dezena 3 na Linha
- `TD3Li` = Terno de Dezena 3 na Linha

---

## Arquitetura do Projeto PHP

```
app/
├── config/
│   ├── application.php   — config geral (timezone, tema, permissões, JWT seed)
│   └── permission.php    — credenciais do banco de dados
├── control/              — controllers (telas)
│   ├── area/
│   ├── area-cotacao/
│   ├── area-extracao/
│   ├── area-limite/
│   ├── AreaComissaoModalidade/
│   ├── communication/    — mensagens, notificações, posts (Adianti padrão)
│   ├── extracao/
│   ├── ExtracaoDescarga/
│   ├── gerente/
│   ├── log/
│   ├── modalidade/
│   ├── palpite-cotado/
│   ├── parametros/
│   ├── resultado/
│   └── vendedor/
├── model/
│   ├── entities/         — Active Records do negócio
│   │   ├── Area.php
│   │   ├── AreaComissaoModalidade.php
│   │   ├── AreaCotacao.php
│   │   ├── AreaExtracao.php
│   │   ├── AreaLimite.php
│   │   ├── Extracao.php
│   │   ├── ExtracaoDescarga.php
│   │   ├── Gerente.php
│   │   ├── IntCalculoSorteio.php
│   │   ├── IntJogo.php
│   │   ├── Modalidade.php
│   │   ├── MovSorteio.php
│   │   ├── PalpiteCotado.php
│   │   ├── Parametros.php
│   │   └── Vendedor.php
│   └── admin/            — models do sistema Adianti (users, groups, etc.)
├── service/
│   ├── auth/
│   │   └── ApplicationAuthenticationRestService.php  — JWT login/logout/refresh
│   ├── cli/
│   ├── jobs/
│   ├── log/
│   ├── rest/
│   └── system/
└── view/
```

---

## Schema do Banco (Tabelas de Negócio)

### Prefixos de Tabela

| Prefixo | Categoria |
|---|---|
| `cad_*` | Cadastro (dados mestre) |
| `cfg_*` | Configuração |
| `mov_*` | Movimento (transações) |
| `int_*` | Interno/sistema |
| `data_*` | Dados auxiliares |

### Tabelas Principais

**Cadastro:**
- `cad_area` — Áreas (area_id, descricao, complemento, ativo)
- `cad_vendedor` — Vendedores (vinculado a area + coletor, com limites e permissões)
- `cad_coletor` — Coletores/Gerentes (vinculado a area, pode ter acesso_web)
- `cad_extracao` — Extrações com dias da semana, hora_limite, premiacao_maxima
- `cad_modalidade` — Modalidades de jogo (vinculado a int_jogo, com multiplicadores)
- `cad_terminal` — Terminais de venda

**Configuração:**
- `cfg_area_extracao` — Quais extrações estão ativas por área
- `cfg_area_cotacao` — Multiplicador de premiação por área/extração/modalidade
- `cfg_area_limite` — Limite de aposta por área/modalidade
- `cfg_area_comissao_modalidade` — Comissão por área/modalidade
- `cfg_palpite_cotado` — Cotação especial para palpites específicos
- `cfg_extracao_descarga` — Limite de descarga por extração/modalidade
- `cfg_parametros` — Parâmetros gerais da banca (nome_banca, features habilitadas)
- `cfg_grade_comissao` / `cfg_grade_comissao_itens` — Grade de comissão

**Movimento:**
- `mov_sorteio` — Sorteios (situacao: A=Aberto, F=Fechado; numeros_sorteados)
- `mov_jb` — Bilhetes de Jogo do Bicho
- `mov_jb_sorteio` / `mov_jb_sort_palpite` — Detalhes do JB por sorteio
- `mov_bilhetinho` — Bilhetes de Bilhetinho
- `mov_bilhetinho_sorteio` — Detalhes do Bilhetinho por sorteio
- `mov_caixa` / `mov_caixa_lancamentos` — Caixa do vendedor

**Triggers importantes no banco:**
- `trg_mv_cad_extracao_cria_sorteios` — Ao inserir/atualizar extração, cria sorteios automaticamente
- `trg_mv_sorteio_verifica_ganhadores` — Ao registrar resultado, calcula premiados
- `trg_mv_sorteio_verifica_ganhadores_lotinha` — Versão para Lotinha
- `trg_mv_sorteio_verifica_ganhadores_qui_sen` — Versão para Quininha/Seninha

---

## Menu do Sistema

```
Cadastros
├── Área           → AreaList / AreaForm
├── Gerente        → GerenteList / GerenteForm
├── Extração       → ExtracaoList / ExtracaoForm
├── Modalidade     → ModalidadeList / ModalidadeForm
└── Vendedor       → VendedorList / VendedorForm

Configurações
├── Área Extração              → AreaExtracaoList
├── Área Cotação               → AreaCotacaoList / AreaCotacaoForm
├── Área Limite                → AreaLimiteList / AreaLimiteForm
├── Área Comissão Modalidade   → AreaComissaoModalidadeList / AreaComissaoModalidadeForm
├── Palpite Cotado             → PalpiteCotadoList / PalpiteCotadoForm
├── Extração Descarga          → ExtracaoDescargaList / ExtracaoDescargaForm
└── Parâmetros                 → ParametrosList / ParametrosForm

Operacional
└── Resultado      → ResultadoList / ResultadoForm
```

---

## Padrões de Código (Adianti Framework)

### Controller (Form)
```php
class XxxForm extends TPage {
    public function __construct() {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');
        $this->form = new BootstrapFormBuilder('form_xxx');
        // campos, validação, botões
    }
    public static function onSave($param) { /* TTransaction::open('permission'); */ }
    public static function onEdit($param) { /* carrega registro */ }
    public static function onDelete($param) { /* apaga registro */ }
}
```

### Controller (List)
```php
class XxxList extends TPage {
    public function __construct() {
        parent::__construct();
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        // colunas, filtros, paginação
    }
    public static function onReload($param) { /* TTransaction + TFilter */ }
}
```

### Model (Active Record)
```php
class Xxx extends TRecord {
    const TABLENAME = 'cad_xxx';
    const PRIMARYKEY = 'xxx_id';
    const IDPOLICY = 'serial'; // ou 'max'
}
```

### Transações
- Sempre usar `TTransaction::open('permission')` e `TTransaction::close()` em bloco try/catch.
- O nome da conexão é `'permission'` (aponta para o banco `applications`).

---

## REST API

Endpoint base: `http://localhost/rest.php`. Router em `rest.php` (raiz).
Envelope de resposta: `{ "status": "success"|"error", "data": ... }`.

### Autenticação móvel (revisada nas Etapas 1–4 — consumida pelo app `app-zooloo`)

| Método | Bearer? | Descrição |
|---|---|---|
| `ApplicationAuthenticationRestService::login` | Não | Login; devolve `token` (access), `refresh_token`, `user`, **`vendedor` + `permissoes`** |
| `ApplicationAuthenticationRestService::refreshToken` | Não | Rotaciona o par a partir de `refresh_token` (revoga o antigo) |
| `ApplicationAuthenticationRestService::validateToken` | — | Valida access-token (checa `jti` não-revogado) |
| `ApplicationAuthenticationRestService::logout` | Sim | Revoga access + refresh persistidos |
| `ApplicationAuthenticationRestService::logoutAll` | Sim | Revoga todos os tokens ativos do usuário |

**Modelo de tokens (não mais "1 hora simples"):**
- **access-token**: JWT curto (**15 min**), com `jti`, usado em `Authorization: Bearer`.
- **refresh-token**: JWT longo (**30 dias**), com `jti`, persistido e revogável.
- Rotação obrigatória no refresh; **reuso de refresh já rotacionado revoga toda a árvore** (replay detection).
- Algoritmo `HS256`; chave `APPLICATION_NAME + seed` (`app/config/application.php`).
- Login resolve `system_users.id → cad_vendedor.usuario_id` e devolve flags do vendedor.
- Mensagens de erro de login são **genéricas** (mitiga user enumeration).

**Estruturas criadas na Etapa 2:**
- `app/model/entities/MobAuthToken.php` — TRecord do token revogável (tabela `mob_auth_token`).
- `app/migrations/etapa2_auth.sql` — cria `mob_auth_token` (rodar antes de usar; idempotente).
- `app/service/auth/AuthRateLimiter.php` — rate-limit file-based em `tmp/ratelimit/`
  (por login: 5/5min, lockout 15min; por IP: 20/5min). Config em `application.php → rate_limit`.

**Segurança em `rest.php`:**
- CORS restrito por `application.php → security.cors_allowed_origins` (`['*']` só em dev).
- Sem `Authorization` → **401 + JSON** (antes lançava Exception → 500).
- Erros internos não vazam mensagem quando `debug='0'`.
- `rest_key` global (Basic auth legada): `zooloo_api_key_2025`.

> Documentação operacional completa da auth (diagrama, checklist de produção,
> troubleshooting): `C:/desenvolvimento/app-zooloo/README-AUTH.md`.

---

## Testes

Harness próprio (não-PHPUnit) em `tests/` — `test(nome, fn)` + `assert*` de `tests/bootstrap.php`.

```bash
composer test    # = php tests/run.php (roda todos os tests/*.test.php)
# via Docker:
docker exec -i applications_www sh -lc "cd /var/www/html && composer test"
```

Cobertura de auth: `tests/auth-rest-service.test.php` (login/refresh/logout/rotação/replay/
vendedor) e `tests/auth-rate-limiter.test.php`. **Convenção obrigatória:** ao declarar
mocks de classes Adianti em arquivos de teste, sempre guardar com
`if (!class_exists(__NAMESPACE__ . '\\X', false))` — vários arquivos compartilham o mesmo
processo e declarações sem guarda causam fatal de redeclaração.

---

## TODOs Conhecidos

- Ao deixar um Gerente inativo, também deixar o usuário do sistema inativo.
- `ResultadoForm`: verificar o horário limite da extração antes de permitir salvar o resultado.
- **Auth:** trocar `seed` e `rest_key` antes de produção; `debug='0'`; `cors_allowed_origins`
  com hosts explícitos; binding `terminal_id`/serial no JWT; auditoria de refresh/logoutAll.

---

## Executar Localmente

```bash
# Subir containers
docker compose up -d

# Acessar banco jb (legado)
docker exec applications_db psql -U postgres -d jb

# Acessar banco applications (zooloo)
docker exec applications_db psql -U postgres -d applications

# Sistema PHP disponível em
http://localhost
```

---

## Sistema Original (Referência)

O sistema Java de referência está localizado no diretório irmão `allsystem`, acessível pelo caminho relativo: `../jballsystem/allsystem/` (assumindo que ambos os projetos estão sob a pasta base `desenvolvimento`, seja no Windows ou Linux).
- As entidades do domínio estão em `src/main/java/br/com/allsystem/app/domain/`
- Os repositorios estao em `src/main/java/br/com/allsystem/app/repository/`
- As classes de servico estao em `src/main/java/br/com/allsystem/app/service/`
- Os controllers estao em `src/main/java/br/com/allsystem/app/web/rest/`
- As class de configuracao de servicos estao em `src/main/java/br/com/allsystem/app/config/`
- Font end, arquivos de gerenciamento de usuários estão em `src\main\webapp\app\account`
- Arquivos front end admin estão em `src\main\webapp\app\admin`
- Front end, entidades estão em `src\main\webapp\app\entities`
- Front end, model estão em `src\main\webapp\app\shared\model`
- Usa o mesmo banco `jb` como referência de schema e dados
- Sempre consultar o sistema original quando precisar entender regras de negócio não documentadas


---
