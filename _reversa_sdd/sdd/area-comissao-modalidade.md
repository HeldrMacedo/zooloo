# Área Comissão Modalidade — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Área Comissão Modalidade** configura o percentual de comissão que um vendedor recebe sobre apostas de uma modalidade específica em uma área. Permite granularidade fina: uma mesma área pode ter comissões diferentes para Milhar, Centena, Dezena, etc. A comissão é restrita ao intervalo 0%–100% com validação server-side. A combinação (area_id + modalidade_id) deve ser única.

---

## Responsabilidades

- Criar e editar percentuais de comissão por combinação área/modalidade 🟢
- Validar unicidade da combinação (area_id + modalidade_id) antes de persistir 🟢
- Validar que o percentual de comissão está entre 0 e 100 🟢
- Listar configurações de comissão com filtro por área e modalidade 🟢

---

## Interface

### AreaComissaoModalidadeForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `area_comissao_modalidade_id` | `TEntry` | `cfg_area_comissao_modalidade.area_comissao_modalidade_id` | — | Não editável; auto MAX+1 |
| `area_id` | `TDBCombo` | `cfg_area_comissao_modalidade.area_id` | Sim | Lista todas as áreas sem filtro ativo |
| `modalidade_id` | `TDBCombo` | `cfg_area_comissao_modalidade.modalidade_id` | Sim | Lista todas as modalidades sem filtro ativo |
| `comissao` | `TEntry` | `cfg_area_comissao_modalidade.comissao` | Sim | Máscara `99,99`; numérica 2 casas; `TRequiredValidator` |

> **Observação:** O campo `comissao` usa `TEntry` com máscara numérica, não `TNumeric` como outros módulos de configuração financeira. 🟡

### AreaComissaoModalidadeList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `area_id` | `=` | `cfg_area_comissao_modalidade.area_id` |
| `modalidade_id` | `=` | `cfg_area_comissao_modalidade.modalidade_id` |

---

## Regras de Negócio

- **RN-CM-01** `area_id`, `modalidade_id` e `comissao` são obrigatórios 🟢
- **RN-CM-02** A combinação (`area_id`, `modalidade_id`) deve ser única — verificada por query antes do INSERT 🟢
- **RN-CM-03** A validação de unicidade ocorre apenas na criação (`area_comissao_modalidade_id` vazio) — edição não re-verifica 🟢
- **RN-CM-04** `comissao` deve ser `>= 0` e `<= 100` — validado server-side com exceção explícita 🟢
- **RN-CM-05** `area_comissao_modalidade_id` usa `IDPOLICY='max'` — `MAX(id) + 1` 🟢
- **RN-CM-06** Os combos de área e modalidade não filtram por `ativo='S'` — áreas e modalidades inativas aparecem nas opções 🟡
- **RN-CM-07** A comissão configurada aqui é por modalidade; o campo `comissao` em `cad_vendedor` é a comissão geral do vendedor — relação entre os dois não está implementada no PHP atual 🔴

---

## Fluxo Principal — Criar Configuração de Comissão

1. Usuário abre `AreaComissaoModalidadeList` → clica em "+"
2. `AreaComissaoModalidadeForm` abre no painel direito
3. Usuário seleciona área, modalidade e preenche percentual de comissão
4. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - Se `area_comissao_modalidade_id` vazio:
     - `TCriteria` com `area_id + modalidade_id`
     - `AreaComissaoModalidade::getObjects($criteria)` → se existir: lança exceção de duplicidade
   - Validação de intervalo: `comissao < 0 || comissao > 100` → exceção
   - `new AreaComissaoModalidade()` → `fromArray($data)` → `store()`
   - `TForm::sendData('form_area_comissao_modalidade', {id})`
   - `TTransaction::close()`
   - Dispara `AreaComissaoModalidadeList::onReload`

---

## Fluxo Alternativo — Editar Comissão

1. Usuário clica em "Editar" → `onEdit(['key' => $id])`:
   - `TTransaction::open('permission')`
   - `new AreaComissaoModalidade($key)` → `form->setData($object)`
   - `TTransaction::close()`
2. Usuário altera `comissao` e salva
3. `onSave`: `area_comissao_modalidade_id` presente → pula verificação de unicidade → valida intervalo → `store()`

---

## Dependências

| Componente | Relação |
|---|---|
| `AreaComissaoModalidade` (Active Record) | Entidade principal — `cfg_area_comissao_modalidade` |
| `Area` | FK `area_id` |
| `Modalidade` | FK `modalidade_id` |
| `Vendedor` | Campo `comissao` em `cad_vendedor` — comissão geral (relação com esta tabela não implementada) 🔴 |
| `GeralComissaoResource` (Java ref.) | Sistema Java tem endpoint que consulta comissão por área/modalidade/vendedor 🔴 |
| `TStandardList` | Herança em AreaComissaoModalidadeList |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Unicidade (area+modalidade) enforçada em PHP na criação | `AreaComissaoModalidadeForm.php:83-93` | 🟢 |
| Integridade | Comissão restrita a 0–100% com exceção explícita | `AreaComissaoModalidadeForm.php:97-100` | 🟢 |
| Consistência | Transação com rollback em operações de escrita | `AreaComissaoModalidadeForm.php:78-119` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar comissão
Dado que não existe comissão para area_id=1, modalidade_id=3
Quando o usuário preenche area=1, modalidade=3, comissao=5.50 e salva
Então um registro é criado em cfg_area_comissao_modalidade com comissao=5.50
E a lista é recarregada com o novo registro

# Falha — duplicidade
Dado que já existe comissão para area_id=1, modalidade_id=3
Quando o usuário tenta criar outra para a mesma combinação
Então exceção "Já existe uma comissão cadastrada para esta área e modalidade" é lançada
E nenhum registro é criado

# Falha — comissão fora do intervalo
Dado que o usuário preenche comissao=150
Quando tenta salvar
Então exceção "A comissão deve estar entre 0% e 100%" é lançada

# Falha — comissão negativa
Dado que o usuário preenche comissao=-5
Quando tenta salvar
Então exceção de intervalo inválido é lançada

# Happy path — editar comissão
Dado que existe area_comissao_modalidade_id=4 com comissao=5.00
Quando o usuário edita e altera para comissao=7.50 e salva
Então o registro é atualizado para 7.50 sem re-verificar unicidade

# Falha — campos obrigatórios
Dado que o usuário não preenche comissao
Quando tenta salvar
Então TRequiredValidator impede o submit
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar comissão por área/modalidade | Must | Define quanto o vendedor ganha por tipo de aposta |
| Validação unicidade (area+modalidade) | Must | Sem isso, múltiplas comissões conflitantes para a mesma combinação |
| Validação de intervalo 0–100% | Must | Dado de negócio — percentual não pode ser inválido |
| Listar e filtrar comissões | Must | Navegação e gestão operacional |
| Integração com relatório de comissão (gap) | Should | Sistema Java tem GeralComissaoResource — não implementado no Zooloo |
| Filtro ativo='S' nos combos | Should | Consistência com outros módulos de configuração |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php` | `AreaComissaoModalidadeForm::__construct` | 🟢 |
| `app/control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php` | `AreaComissaoModalidadeForm::onSave` | 🟢 |
| `app/control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php` | `AreaComissaoModalidadeForm::onEdit` | 🟢 |
| `app/control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php` | `AreaComissaoModalidadeForm::onClear` | 🟢 |
| `app/model/entities/AreaComissaoModalidade.php` | `AreaComissaoModalidade` (TRecord → `cfg_area_comissao_modalidade`) | 🟢 |
| `_reversa_sdd/flowcharts/area-comissao-modalidade.md` | Fluxogramas Mermaid | 🟢 |
