# ARCHITECTURE.md — Zooloo

## Stack

| Componente | Tecnologia |
|---|---|
| Framework | Adianti Framework 8.1 (`lib/VERSION`), tema Bootstrap 5 (`adminbs5`) |
| Linguagem | PHP 8.2 |
| Banco | PostgreSQL 15 |
| Auth REST | JWT HS256 (`firebase/php-jwt`) |
| PDF | DomPDF + Adianti PDF Designer |
| Ambiente | Docker Compose |

Containers: `applications_www` (Apache/PHP, portas 80/443/8000), `applications_db`
(Postgres, 5432), `zooloo-php-1` (PHP CLI).

## Bancos e conexões

Conexões Adianti em `app/config/`. `permission.php`, `communication.php` e `log.php` apontam
para o mesmo Postgres; o nome do banco vem de `DATABASE_NAME`, senão `teste` quando
`APP_ENV=test`, senão `applications`.

| Banco | Uso |
|---|---|
| `applications` | banco de dev do zooloo (negócio + tabelas `system_*` do Adianti) |
| `teste` | testes automatizados (`tests/bootstrap.php` define `APP_ENV=test`) |
| `jb` | banco do legado allsystem — **só referência** de schema/dados; nenhum código usa |

`permission_test.php` e `teste.php` são configs fixas no banco `teste`. `unit_a.php`,
`unit_b.php`, `sample.php` e `unit_database.php` são sobras do template Adianti.

Toda transação de negócio usa `TTransaction::open('permission')`.

## Pastas

```
app/
  config/            conexões e application.php (tema, JWT seed, CORS, rate limit)
  control/           telas (TPage) por assunto: area/, extracao/, modalidade/, vendedor/,
                     terminal/, resultado/, premiacao/, descarrego/, relatorio/, lotinha/,
                     quininha/, seninha/, grade-comissao/, ... (admin/, communication/, log/ = Adianti)
  model/entities/    Active Records do negócio (TRecord)
  model/admin/       models do Adianti (usuários, grupos)
  service/auth/      ApplicationAuthenticationRestService (JWT), AuthRateLimiter
  service/rest/      serviços REST de negócio, expostos por rest.php
  service/{cli,jobs,log,system}/
  migrations/        SQL versionado (hoje só etapa2_auth.sql)
  view/
rest.php             roteador REST (Classe::metodo)
rest/                scripts de baixo nível do roteador (jwt.php, get-token.php...)
menu.xml             menu do painel — fonte da verdade da navegação (não copie para docs)
tests/               harness próprio; *.test.php rodam no composer test
```

## Padrões Adianti

```php
class XxxForm extends TPage {          // formulário
    public function __construct() {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');
        $this->form = new BootstrapFormBuilder('form_xxx');
    }
    public static function onSave($param) { /* TTransaction::open('permission'); */ }
    public static function onEdit($param) { }
    public static function onDelete($param) { }
}

class XxxList extends TPage {          // listagem
    public function __construct() {
        parent::__construct();
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    }
    public static function onReload($param) { /* TTransaction + TFilter */ }
}

class Xxx extends TRecord {            // model
    const TABLENAME  = 'cad_xxx';
    const PRIMARYKEY = 'xxx_id';
    const IDPOLICY   = 'serial';       // ou 'max'
}
```

## REST

`rest.php` chama `Classe::metodo`. `login` e `refreshToken` são públicos; o resto exige
`Authorization: Bearer <jwt>` (ou Basic com `rest_key`, legado). Resposta:
`{ "status": "success"|"error", "data": ... }`. Detalhes em `docs/references/api-contract.md`.

## Onde mora a regra de negócio

A maior parte do cálculo (criação de sorteios, ganhadores, prêmios) está em **triggers
PostgreSQL** — ver `docs/references/schema-banco.md`. Elas ainda **não estão versionadas** neste
repo (dívida técnica nº 1).
