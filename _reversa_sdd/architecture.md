# Arquitetura do Sistema — zooloo

> Gerado pelo Reversa Architect em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

**Zooloo** é uma reescrita PHP do sistema Java/JHipster `allsystem`, um sistema de gestão de banca de Jogo do Bicho. Usa o **Adianti Framework 8.1** como base MVC, com interface web administrativa (Bootstrap 5) e uma REST API para aplicativo móvel (React Native planejado).

O sistema mantém compatibilidade total com o banco de dados legado (`jb`) e usa um banco separado (`applications`) para dados do sistema Adianti.

---

## Stack Tecnológica

| Camada | Tecnologia | Versão |
|---|---|---|
| Linguagem | PHP | 8.2 |
| Framework | Adianti Framework | 8.1 |
| UI/Tema | Bootstrap | 5 (`adminbs5`) |
| Banco principal | PostgreSQL | 15 (`applications`) |
| Banco legado | PostgreSQL | 15 (`jb`) |
| Auth JWT | firebase/php-jwt | ^6.0 |
| PDF | DomPDF | — |
| E-mail | PHPMailer | ^6.0 |
| 2FA | spomky-labs/otphp | ^11.0 |
| Containerização | Docker Compose | 3.9 |

---

## Diagrama de Contexto (C4 Nível 1)

Ver detalhes em [`c4-context.md`](c4-context.md).

```
[Admin/Gerente] ──HTTPS──► [Zooloo Web]
[Vendedor App]  ──JWT/REST─► [Zooloo REST API]  ◄──TCP──► [PostgreSQL 15]
[App React Native] ──JWT──► [Zooloo REST API]
                              [AllSystem Java] ──────────► [PostgreSQL 15 / banco jb]
```

---

## Containers (C4 Nível 2)

Ver detalhes em [`c4-containers.md`](c4-containers.md).

| Container | Tecnologia | Entry Point | Função |
|---|---|---|---|
| **Web App** | PHP 8.2 + Apache | `index.php → engine.php` | Interface administrativa Adianti SPA |
| **REST API** | PHP 8.2 + Apache | `rest.php` | API para app móvel, JWT |
| **CLI/Jobs** | PHP 8.2 CLI | `cmd.php` | Jobs agendados e utilitários |
| **PostgreSQL applications** | PostgreSQL 15 | TCP 5432 | Dados do sistema + negócio |
| **PostgreSQL jb** | PostgreSQL 15 | TCP 5432 | Schema legado (compartilhado) |

---

## Componentes (C4 Nível 3)

Ver detalhes em [`c4-components.md`](c4-components.md).

### Web App — Módulos de Negócio

| Módulo | Controllers | Entidades |
|---|---|---|
| Cadastros | AreaForm/List, GerenteForm/List, ExtracaoForm/List, ModalidadeForm/List, VendedorForm/List | Area, Gerente, Extracao, Modalidade, Vendedor |
| Configurações | AreaCotacaoForm/List, AreaExtracaoList, AreaLimiteForm/List, AreaComissaoModalidadeForm/List, PalpiteCotadoForm/List, ExtracaoDescargaForm/List, ParametrosForm | AreaCotacao, AreaExtracao, AreaLimite, AreaComissaoModalidade, PalpiteCotado, ExtracaoDescarga, Parametros |
| Operacional | ResultadoForm/List | MovSorteio |

### REST API — Services

| Service | Endpoints principais |
|---|---|
| ApplicationAuthenticationRestService | login, logout, validateToken, refreshToken |
| BilheteRestService | registrar, cancelar, recentes, detalhe, reimprimir |
| SorteioRestService | abertos, disponiveis |
| ModalidadeRestService | disponiveis |
| CaixaRestService | resumo |
| ResultadoRestService | registrar |
| TerminalRestService | me |
| VendedorRestService | me |

---

## ERD (Resumo)

Ver ERD completo em [`erd-complete.md`](erd-complete.md).

**19 entidades** organizadas em 4 prefixos:

| Prefixo | Categoria | Tabelas |
|---|---|---|
| `cad_*` | Cadastros mestres | area, coletor, extracao, modalidade, vendedor, terminal |
| `cfg_*` | Configurações | area_extracao, area_cotacao, area_limite, area_comissao_modalidade, extracao_descarga, palpite_cotado, parametros |
| `mov_*` | Movimentações | sorteio, jb, jb_sorteio, jb_sort_palpite |
| `int_*` | Internos/seed | jogo, calculo_sorteio |

---

## Padrões Arquiteturais

### Padrão de Controller (Adianti)

```
TPage (base)
├── __construct() — monta UI (form + datagrid)
├── static onSave($param) — valida + persiste (TTransaction)
├── static onEdit($param) — carrega dados no form
├── static onDelete($param) — remove registro
├── static onReload($param) — recarrega lista com filtros
└── static onTurnOnOff($param) — toggle ativo S↔N
```

### Padrão de Entidade (Active Record)

```php
class Xxx extends TRecord {
    const TABLENAME = 'prefixo_xxx';
    const PRIMARYKEY = 'xxx_id';
    const IDPOLICY = 'max'; // MAX(pk)+1 — usado em TODAS as entidades
}
```

> **Decisão arquitetural:** `IDPOLICY='max'` em vez de `serial` em todas as entidades — compatibilidade com banco legado que não usa sequences PostgreSQL.

### Padrão de Transação

```php
TTransaction::open('permission'); // nome da conexão com banco 'applications'
try {
    // operações
    TTransaction::close();
} catch (Exception $e) {
    TTransaction::rollback();
    // error handling
}
```

### Padrão Dual-Entity (Gerente/Vendedor)

Uma transação única cria dois registros:
1. `SystemUser` (sistema Adianti) com login + MD5(senha)
2. Entidade de domínio (`Gerente`/`Vendedor`) com `usuario_id` vinculado

---

## Integrações Externas

| Sistema | Protocolo | Status | Módulo |
|---|---|---|---|
| PostgreSQL 15 (`applications`) | TCP 5432 | 🟢 Ativo | Todo o sistema |
| PostgreSQL 15 (`jb`) | TCP 5432 | 🟢 Ativo (legado) | AllSystem Java |
| SMTP (PHPMailer) | SMTP | 🟢 Instalado | `communication/` |
| LDAP | LDAP | 🟡 Configurado, uso incerto | `app/config/ldap.ini` |
| MongoDB | TCP 27017 | 🔴 Lacuna — instalado, sem uso confirmado | Docker |
| App React Native | HTTPS/JWT | 🟡 Em desenvolvimento | REST API |

---

## Dívidas Técnicas Identificadas

| Prioridade | Tipo | Descrição | Localização |
|---|---|---|---|
| 🔴 Alta | Segurança | MD5 sem salt para senhas de Gerentes e Vendedores | GerenteForm.php, VendedorForm.php |
| 🔴 Alta | Lacuna | `cfg_extracao_modalidade` sem Active Record | ModalidadeRestService.php |
| 🟡 Média | Bug | Typo `'form_ferente'` impede atualização correta do form após salvar Gerente | GerenteForm.php |
| 🟡 Média | Bug | `$this->form->cleat()` — método inexistente | ExtracaoForm.php |
| 🟡 Média | Lógica | Verificação de hora_limite antes de salvar resultado (TODO documentado) | ResultadoForm.php |
| 🟡 Média | Consistência | AreaExtracao usa hard delete; todos os outros módulos usam soft delete (campo ativo) | AreaExtracaoList.php |
| 🟡 Média | Auditoria | Hard delete em AreaExtracao perde histórico de ativações | AreaExtracaoList.php |
| 🟢 Baixa | Segurança | API key estática `zooloo_api_key_2025` em configuração | application.php |
| 🟢 Baixa | Qualidade | Email fabricado `login@zooloo.com` para Vendedores | VendedorForm.php |
| 🟢 Baixa | Lacuna | Triggers de cálculo de prêmios não estão nos arquivos SQL do projeto | banco `jb` |
| 🟢 Baixa | Inconsistência | Sincronização Gerente↔SystemUser implementada no List, mas não documentada no Form | GerenteList.php |
