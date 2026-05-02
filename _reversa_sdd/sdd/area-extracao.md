# Área Extração — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Área Extração** configura quais extrações estão ativas para cada área da banca. Diferente de todos os outros módulos que usam um campo `ativo` (`S`/`N`) para controle de status, este módulo usa **presença/ausência de registro** na tabela `cfg_area_extracao`: ativar uma extração para uma área cria um registro; desativar remove o registro via `delete()`. Não existe um `AreaExtracaoForm` — a tela é exclusivamente uma lista com toggles inline. Gerentes só visualizam e configuram a própria área.

---

## Responsabilidades

- Exibir todas as extrações ativas (`cad_extracao.ativo='S'`) com status de ativação por área 🟢
- Ativar uma extração para uma área (INSERT em `cfg_area_extracao`) 🟢
- Desativar uma extração para uma área (DELETE em `cfg_area_extracao`) 🟢
- Filtrar visualização por área quando o usuário autenticado é Gerente 🟢
- Persistir a área selecionada na sessão para recarregamento após toggle 🟢

---

## Interface

### AreaExtracaoList — Filtros

| Elemento | Tipo | Notas |
|---|---|---|
| `area_id` | `TDBCombo` | Filtra `ativo='S'`; com busca; dispara `onChangeArea` ao mudar; restrito à área do gerente autenticado |

### AreaExtracaoList — Grid

| Coluna | Fonte | Notas |
|---|---|---|
| Extração | `cad_extracao.descricao` | Texto da extração |
| Ativo | Toggle inline (botão) | Verde "Ativado" se registro existe; Vermelho "Desativado" se não existe |

**Não há formulário de criação/edição** — toda interação ocorre via botões toggle na própria grid. 🟢

---

## Regras de Negócio

- **RN-AE-01** O estado de ativação é determinado pela **presença de registro** em `cfg_area_extracao` — não por um campo `ativo` no registro 🟢
- **RN-AE-02** Ativar: se não existe registro → `new AreaExtracao` com `area_id + extracao_id + ativo=true` → `store()` 🟢
- **RN-AE-03** Ativar: se já existe registro com `ativo=false` → `existing->ativo = true` → `store()` (atualiza) 🟢
- **RN-AE-04** Desativar: `existing->delete()` — hard delete, sem soft delete 🟢
- **RN-AE-05** A grid exibe **todas** as extrações com `ativo='S'` em `cad_extracao`, independentemente de terem registro em `cfg_area_extracao` (LEFT JOIN) 🟢
- **RN-AE-06** Gerentes autenticados têm o combo de área restrito à própria área e a lista é carregada automaticamente 🟢
- **RN-AE-07** A área selecionada é salva em `TSession` (`area_extracao_area_id`) para ser reutilizada no `onReload` 🟢
- **RN-AE-08** `onChangeArea` dispara `onLoadAreaExtracoes` via chamada AJAX inline (JavaScript) ao mudar o combo 🟢
- **RN-AE-09** Desativar uma extração para uma área **não impede** a exibição de sorteios já abertos — apenas impede que novos bilhetes sejam registrados por aquela área 🟡
- **RN-AE-10** O `BilheteRestService` valida se a extração está ativa na área antes de aceitar um bilhete (`cfg_area_extracao`) 🟡
- **RN-AE-11** Inconsistência arquitetural: todos os outros módulos usam soft delete (`ativo=S/N`); AreaExtracao usa hard delete — perda de histórico de ativações 🟢

---

## Fluxo Principal — Carregar Grid

1. Usuário acessa `AreaExtracaoList`
2. `show()` chama `checkUserPermissions()`:
   - `TTransaction::open('permission')`
   - Busca `Gerente` onde `usuario_id = TSession('userid')`
   - Se encontrar: restringe combo ao `area_id` do gerente; auto-seleciona; chama `onLoadAreaExtracoes`
3. Usuário seleciona uma área (se Administrador) → `onChangeArea` via AJAX → `onLoadAreaExtracoes`
4. `onLoadAreaExtracoes($param['area_id'])`:
   - Salva `area_id` em `TSession('area_extracao_area_id')`
   - Executa SQL raw com `LEFT JOIN`:
     ```sql
     SELECT e.extracao_id, e.descricao as extracao,
            CASE WHEN ae.ativo IS true THEN ae.ativo ELSE false END as ativo
     FROM cad_extracao e
     LEFT JOIN cfg_area_extracao ae
       ON ae.extracao_id = e.extracao_id AND ae.area_id = :area_id
     WHERE e.ativo = 'S'
     ORDER BY e.descricao
     ```
   - Renderiza a grid com cada extração e seu estado de ativação
5. Grid exibe botão "Ativado" (verde) ou "Desativado" (vermelho) para cada linha

---

## Fluxo Principal — Toggle Ativar Extração

1. Usuário clica em "Desativado" (vermelho) em uma linha
2. Botão dispara `__adianti_ajax_exec` com `method=onToggleStatus&area_id=X&extracao_id=Y&ativo=S`
3. `onToggleStatus($param)`:
   - `TTransaction::open('permission')`
   - Busca `AreaExtracao` onde `area_id=X AND extracao_id=Y`
   - Se não existe: `new AreaExtracao` → `area_id=X`, `extracao_id=Y`, `ativo=true` → `store()`
   - Se existe com `ativo=false`: `existing->ativo = true` → `store()`
   - `TTransaction::close()`
   - Recarrega `AreaExtracaoList::onLoadAreaExtracoes` com `area_id=X`

---

## Fluxo Principal — Toggle Desativar Extração

1. Usuário clica em "Ativado" (verde) em uma linha
2. Botão dispara `__adianti_ajax_exec` com `method=onToggleStatus&area_id=X&extracao_id=Y&ativo=N`
3. `onToggleStatus($param)`:
   - `TTransaction::open('permission')`
   - Busca `AreaExtracao` onde `area_id=X AND extracao_id=Y`
   - Se existe: `existing->delete()` — **hard delete sem histórico**
   - `TTransaction::close()`
   - Recarrega grid

---

## SQL Usado (Raw Query)

```sql
SELECT
    ae.area_extracao_id,
    ae.area_id,
    e.extracao_id,
    e.descricao as extracao,
    CASE
        WHEN ae.ativo IS true THEN ae.ativo
        ELSE false
    END as ativo
FROM cad_extracao e
LEFT JOIN cfg_area_extracao ae
    ON ae.extracao_id = e.extracao_id AND ae.area_id = :area_id
WHERE e.ativo = 'S'
ORDER BY e.descricao
```

> Uso de PDO direto via `TTransaction::get()->prepare()` — foge do padrão TRecord/Active Record do projeto. 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `AreaExtracao` (Active Record) | Entidade principal — `cfg_area_extracao` |
| `Area` | Filtro de área — combo e permissões de gerente |
| `Extracao` | Fonte das extrações exibidas na grid (`cad_extracao`) |
| `Gerente` | Verificação de permissões — restringe área ao gerente autenticado |
| `BilheteRestService` | Valida presença em `cfg_area_extracao` antes de aceitar bilhete 🟡 |
| `SorteioRestService` | Retorna sorteios apenas de extrações ativas na área do vendedor 🟡 |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Segurança | Gerentes só veem/editam a própria área | `AreaExtracaoList::checkUserPermissions` | 🟢 |
| Consistência | Transação com rollback em toggle | `AreaExtracaoList::onToggleStatus:199,239` | 🟢 |
| Auditoria | Hard delete perde histórico de ativações — dívida técnica documentada | `ADR-005` | 🟢 |
| Performance | SQL raw com PDO direto — fora do ORM padrão | `AreaExtracaoList.php:159` | 🟡 |

---

## Critérios de Aceitação

```gherkin
# Happy path — ativar extração para área
Dado que não existe registro em cfg_area_extracao para area_id=2, extracao_id=3
Quando o usuário clica no botão "Desativado" dessa linha
Então onToggleStatus cria registro: area_id=2, extracao_id=3, ativo=true
E a linha exibe o botão verde "Ativado"

# Happy path — desativar extração para área
Dado que existe registro em cfg_area_extracao para area_id=2, extracao_id=3
Quando o usuário clica no botão "Ativado" dessa linha
Então onToggleStatus executa delete() no registro
E a linha exibe o botão vermelho "Desativado"

# Happy path — Gerente vê apenas sua área
Dado que o usuário autenticado é Gerente da area_id=5
Quando acessa AreaExtracaoList
Então o combo de área exibe apenas a área 5
E a grid é carregada automaticamente com as extrações dessa área

# Happy path — grid com LEFT JOIN
Dado que existem 5 extrações ativas em cad_extracao
E apenas 2 estão em cfg_area_extracao para a área selecionada
Quando a grid é carregada
Então todas as 5 extrações são exibidas
E 2 têm botão verde "Ativado" e 3 têm botão vermelho "Desativado"

# Falha — área não selecionada
Dado que nenhuma área foi selecionada no combo
Quando o usuário clica em "Buscar"
Então onLoadAreaExtracoes limpa o datagrid sem carregar dados
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Toggle ativar extração por área | Must | Controla quais sorteios são disponíveis para apostas em cada área |
| Toggle desativar extração por área | Must | Idem — controle operacional crítico |
| Grid com LEFT JOIN (todas extrações) | Must | Sem isso, extrações inativas ficam invisíveis |
| Restrição de área para Gerente | Must | Segurança multi-área — gerente não pode alterar outras áreas |
| Correção: migrar de hard delete para soft delete | Should | Histórico de ativações perdido — dívida técnica (ADR-005) |
| Persistência de área na sessão | Should | UX — evita perda de contexto ao recarregar |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/area-extracao/AreaExtracaoList.php` | `AreaExtracaoList::__construct` | 🟢 |
| `app/control/area-extracao/AreaExtracaoList.php` | `AreaExtracaoList::onLoadAreaExtracoes` | 🟢 |
| `app/control/area-extracao/AreaExtracaoList.php` | `AreaExtracaoList::onToggleStatus` | 🟢 |
| `app/control/area-extracao/AreaExtracaoList.php` | `AreaExtracaoList::onChangeArea` | 🟢 |
| `app/control/area-extracao/AreaExtracaoList.php` | `AreaExtracaoList::checkUserPermissions` | 🟢 |
| `app/control/area-extracao/AreaExtracaoList.php` | `AreaExtracaoList::onReload` | 🟢 |
| `app/model/entities/AreaExtracao.php` | `AreaExtracao` (TRecord → `cfg_area_extracao`) | 🟢 |
| `_reversa_sdd/flowcharts/area-extracao.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/adrs/005-insert-delete-para-area-extracao.md` | ADR padrão INSERT/DELETE | 🟢 |
