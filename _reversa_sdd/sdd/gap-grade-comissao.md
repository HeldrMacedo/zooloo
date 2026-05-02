# GAP: Grade de Comissão — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — módulo presente no sistema Java (allsystem) mas **não implementado** no Zooloo PHP

---

## Visão Geral

A **Grade de Comissão** é um modelo pré-definido de comissões por modalidade. Em vez de configurar percentuais individualmente por área/modalidade (como faz `cfg_area_comissao_modalidade`), o usuário cria uma "grade" com nome descritivo e define o percentual de comissão para cada modalidade dentro dela. A grade pode então ser reutilizada como template.

O Java (allsystem) tem CRUD completo para `GradeComissao` e `GradeComissaoItens`. O Zooloo **não tem entidade PHP, nem controller, nem tela** para estes dados — as tabelas existem no banco (mencionadas no `CLAUDE.md`) mas só são acessíveis via banco direto.

> **Lacuna crítica:** A relação entre `cfg_grade_comissao` e o cadastro de vendedores ou áreas não está implementada em nenhum lado. Não há FK de `cad_vendedor` nem de `cfg_area_comissao_modalidade` apontando para `grade_comissao_id`. A grade existe como entidade isolada sem consumidor ativo. 🔴

---

## Estrutura de Dados (referência Java/banco)

### `cfg_grade_comissao`

| Coluna | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `grade_comissao_id` | `bigint` PK | Sim | Sequência PostgreSQL `cfg_grade_comissao_grade_comissao_id_seq1` |
| `descricao` | `varchar(255)` | Sim | Nome da grade; forçado uppercase na UI Java |

### `cfg_grade_comissao_itens`

| Coluna | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `grade_comissao_itens_id` | `bigint` PK | Sim | Sequência PostgreSQL `cfg_grade_comissao_itens_grade_comissao_itens_id_seq1` |
| `comissao` | `double` | Sim | Percentual de comissão para a modalidade |
| `grade_comissao_id` | `bigint` FK | Sim | Referência à grade pai |
| `modalidade_id` | `bigint` FK | Sim | Referência à `cad_modalidade` |

> **Discrepância de nomenclatura:** O `@Table(name = "CFG_GRADE_COMISSAO")` Java usa prefixo `cfg_`, mas os arquivos Liquibase criam tabelas sem prefixo (`grade_comissao`, `grade_comissao_itens`). O `CLAUDE.md` lista `cfg_grade_comissao` e `cfg_grade_comissao_itens` como tabelas existentes no banco PostgreSQL. Assumir `cfg_` como nome canônico. 🟡

---

## Interface no Sistema Java (allsystem — referência)

### GradeComissao — Lista

| Coluna | Campo |
|---|---|
| Descrição | `descricao` (exibida em uppercase) |
| Ações | Editar, Excluir |

Permissão de criação protegida por `PERM_GRADE_COMISSAO`.

### GradeComissao — Form

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `descricao` | `text` | Sim | Convertido para uppercase via `maiusculo($event)` |

### GradeComissaoItens — Form

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `gradeComissao` | `select` (FK) | Sim | Seleciona a grade pai |
| `modalidade` | `select` (FK) | Sim | Exibe `modalidade.intJogo.descricao` |
| `comissao` | `number` | Sim | Percentual de comissão (sem validação de intervalo no Java) |

---

## Responsabilidades (a implementar no Zooloo)

- Criar e editar grades de comissão com nome descritivo 🔴
- Criar, editar e excluir itens de comissão por modalidade dentro de uma grade 🔴
- Listar grades existentes com seus itens 🔴
- Validar que `comissao` está entre 0 e 100 (lacuna no Java — ausente) 🔴
- Validar unicidade de `(grade_comissao_id, modalidade_id)` por item 🔴
- Associar grade a vendedor ou área (relação não implementada em nenhum sistema) 🔴

---

## Regras de Negócio

- **RN-GC-01** `descricao` é obrigatório em `cfg_grade_comissao` 🟢
- **RN-GC-02** `comissao`, `grade_comissao_id` e `modalidade_id` são obrigatórios em `cfg_grade_comissao_itens` 🟢
- **RN-GC-03** A combinação (`grade_comissao_id`, `modalidade_id`) deve ser única por item — não enforçado no Java 🔴
- **RN-GC-04** `comissao` deve estar entre 0 e 100 — não enforçado no Java (apenas campo `required`) 🔴
- **RN-GC-05** A relação entre `GradeComissao` e `Vendedor`/`Area` não está definida — como as grades são aplicadas é desconhecido 🔴
- **RN-GC-06** Uma grade pode ter N itens (um por modalidade) 🟡

---

## Fluxo Principal (a implementar — proposta)

### Criar Grade

1. Usuário acessa `GradeComissaoList` → clica em "+"
2. `GradeComissaoForm` abre com campo `descricao`
3. Salva em `cfg_grade_comissao`

### Gerenciar Itens

1. Usuário seleciona uma grade → abre `GradeComissaoItensForm`
2. Seleciona modalidade e preenche `comissao` (0–100)
3. Valida unicidade de `(grade_comissao_id, modalidade_id)`
4. Salva em `cfg_grade_comissao_itens`

---

## Dependências

| Componente | Relação |
|---|---|
| `cfg_grade_comissao` | Tabela cabeçalho — existe no banco |
| `cfg_grade_comissao_itens` | Tabela itens — existe no banco |
| `cad_modalidade` | FK `modalidade_id` — grade define comissão por modalidade |
| `cfg_area_comissao_modalidade` | Módulo alternativo de comissão já implementado no Zooloo — relação entre os dois não está definida 🔴 |
| `cad_vendedor` | Potencial consumidor — sem FK definida 🔴 |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Entidade GradeComissao | `GradeComissao.java` | Ausente | 🔴 |
| Entidade GradeComissaoItens | `GradeComissaoItens.java` | Ausente | 🔴 |
| CRUD GradeComissao | Angular + Spring REST | Ausente | 🔴 |
| CRUD GradeComissaoItens | Angular + Spring REST | Ausente | 🔴 |
| Tabelas no banco | `cfg_grade_comissao` (confirmado no CLAUDE.md) | Existem | 🟢 |
| Relação Grade → Vendedor/Área | Não implementado | Não implementado | 🔴 |
| Validação comissão 0–100 | Ausente | N/A | 🔴 |
| Unicidade modalidade por grade | Ausente | N/A | 🔴 |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Confiança |
|---|---|---|
| Integridade | Unicidade (grade_comissao_id + modalidade_id) a enforçar | 🔴 |
| Integridade | Comissão 0–100 a validar | 🔴 |
| Usabilidade | Exibição dos itens inline ao selecionar a grade | 🟡 |
| Rastreabilidade | Definir FK grade_comissao_id → cad_vendedor ou cfg_area_comissao | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — criar grade
Dado que nenhuma grade "GRADE A" existe
Quando o usuário preenche descricao="GRADE A" e salva
Então um registro é criado em cfg_grade_comissao com descricao="GRADE A"

# Happy path — adicionar item
Dado que existe grade_comissao_id=1 e modalidade_id=3 sem item
Quando o usuário seleciona grade=1, modalidade=3, comissao=5.00 e salva
Então um registro é criado em cfg_grade_comissao_itens

# Falha — duplicidade de item
Dado que já existe item para grade=1, modalidade=3
Quando tenta criar outro item com a mesma combinação
Então exceção "Já existe comissão para esta modalidade nesta grade" é lançada

# Falha — comissão fora do intervalo
Dado que o usuário preenche comissao=150
Quando tenta salvar
Então exceção "Comissão deve estar entre 0% e 100%" é lançada

# Lacuna — aplicação da grade
Dado que grade_comissao_id=1 existe com itens definidos
Quando a banca tenta aplicar esta grade a um vendedor
Então não há mecanismo implementado (lacuna crítica)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar entidade PHP `GradeComissao` | Must | Base para o CRUD |
| Criar entidade PHP `GradeComissaoItens` | Must | Base para o CRUD |
| CRUD de GradeComissao (Form + List) | Should | Permite gerenciar templates de comissão |
| CRUD de GradeComissaoItens | Should | Define os percentuais por modalidade |
| Definir e implementar relação Grade → Vendedor/Área | Must | Sem isso a grade não tem efeito prático |
| Validação comissão 0–100 | Must | Dado de negócio |
| Validação unicidade modalidade por grade | Must | Integridade dos dados |
| Migração de dados existentes (Java → Zooloo) | Should | Dados históricos no banco |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Classe / Função | Sistema |
|---|---|---|
| `allsystem/src/main/java/.../domain/GradeComissao.java` | `GradeComissao` (JPA → `CFG_GRADE_COMISSAO`) | Java |
| `allsystem/src/main/java/.../domain/GradeComissaoItens.java` | `GradeComissaoItens` (JPA → `CFG_GRADE_COMISSAO_ITENS`) | Java |
| `allsystem/src/main/java/.../web/rest/GradeComissaoResource.java` | REST CRUD | Java |
| `allsystem/src/main/java/.../web/rest/GradeComissaoItensResource.java` | REST CRUD | Java |
| `allsystem/src/main/webapp/app/entities/grade-comissao/` | Angular CRUD | Java |
| `allsystem/src/main/webapp/app/entities/grade-comissao-itens/` | Angular CRUD | Java |
| `allsystem/.jhipster/GradeComissao.json` | Schema JHipster | Java |
| `allsystem/.jhipster/GradeComissaoItens.json` | Schema JHipster | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/model/entities/GradeComissao.php` | TRecord → `cfg_grade_comissao` |
| `app/model/entities/GradeComissaoItens.php` | TRecord → `cfg_grade_comissao_itens` |
| `app/control/grade-comissao/GradeComissaoForm.php` | Form |
| `app/control/grade-comissao/GradeComissaoList.php` | List com TStandardList |
| `app/control/grade-comissao/GradeComissaoItensForm.php` | Form de itens |
