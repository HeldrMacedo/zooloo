# Area — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Area** gerencia as zonas geográficas/franquias da banca de Jogo do Bicho. Cada área agrupa vendedores, coletores e configura suas próprias extrações ativas, cotações, limites e comissões. É o nó central de todo o modelo de negócio — praticamente todos os outros módulos dependem de `area_id` como chave de contexto.

---

## Responsabilidades

- Criar e atualizar áreas (`AreaForm`) 🟢
- Listar áreas com filtro por id, descrição e status ativo (`AreaList`) 🟢
- Ativar e desativar áreas via toggle (campo `ativo`: `S`/`N`) 🟢
- Exportar listagem em CSV, PDF e XML (`TStandardList` herdado) 🟢
- Servir como chave de contexto (FK) para todos os módulos dependentes 🟢

---

## Interface

### AreaForm

| Campo | Tipo Adianti | Tabela.Coluna | Obrigatório | Padrão | Notas |
|---|---|---|---|---|---|
| `area_id` | `TEntry` | `cad_area.area_id` | — | auto (MAX+1) | Não editável após criação |
| `descricao` | `TEntry` | `cad_area.descricao` | Sim | — | Validação `TRequiredValidator` |
| `ativo` | `TCombo` | `cad_area.ativo` | Não | `S` | Valores: `S`=Sim / `N`=Não |

### AreaList — Filtros de busca

| Filtro | Operador | Campo |
|---|---|---|
| `area_id` | `=` | `cad_area.area_id` |
| `descricao` | `LIKE` | `cad_area.descricao` |
| `ativo` | `=` | `cad_area.ativo` |

Ordenação padrão: `descricao ASC` 🟢

Paginação: sessão `AreaList_limit` (padrão 10; opções 10/20/50/100/1000) 🟢

---

## Regras de Negócio

- **RN-AR-01** `descricao` é obrigatório — validação no cliente e no servidor via `TRequiredValidator` 🟢
- **RN-AR-02** `ativo` padrão é `S` no formulário de nova área 🟢
- **RN-AR-03** O `area_id` é gerado pelo padrão `IDPOLICY='max'` — `MAX(area_id) + 1` na tabela `cad_area` 🟢
- **RN-AR-04** Ao salvar, o formulário é repopulado com o `area_id` gerado via `TForm::sendData` 🟢
- **RN-AR-05** Toggle ativo inverte o valor atual (`S`→`N` ou `N`→`S`) e recarrega a lista na mesma requisição 🟢
- **RN-AR-06** Desativar uma área **não** desativa automaticamente os vendedores, gerentes ou configurações vinculadas — sem cascata implementada 🟡
- **RN-AR-07** A exclusão de área via botão "Delete" é um hard delete — não há verificação de integridade referencial em PHP antes da exclusão 🟡
- **RN-AR-08** Filtros de busca são persistidos na sessão (`TSession`) entre recarregamentos 🟢

---

## Fluxo Principal — Criar/Editar Área

1. Usuário abre `AreaList` → clica em "+" (novo) ou "Editar" em uma linha existente
2. `AreaForm` abre no painel direito (`adianti_right_panel`)
3. Se edição: `onEdit($param['key'])` → `TTransaction::open('permission')` → `Area::load(area_id)` → `form->setData()`
4. Usuário preenche/altera `descricao` e `ativo`
5. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - `new Area()` → `fromArray($data)` → `store()` (INSERT se novo, UPDATE se existente)
   - `TForm::sendData('form_area', {area_id})` para repopular o campo id
   - `TTransaction::close()`
   - Dispara `AreaList::onReload` como pós-ação da mensagem de sucesso
6. Em caso de exceção: `TTransaction::rollback()` + `TMessage('error', ...)`

---

## Fluxo Alternativo — Toggle Ativo

1. Usuário clica no ícone de power na linha da lista
2. `onTurnOnOff($param['area_id'])`:
   - `TTransaction::open('permission')`
   - `Area::find(area_id)` → inverte `ativo` → `store()`
   - `TTransaction::close()`
   - `onReload($param)` para refletir mudança na grid

---

## Fluxo Alternativo — Excluir Área

1. Usuário clica em "Delete" na linha da lista
2. `onDelete($param['area_id'])` (herdado de `TStandardList`):
   - `TTransaction::open('permission')`
   - `Area::load(area_id)` → `delete()`
   - `TTransaction::close()`
   - **Risco:** banco rejeitará o DELETE se existirem FKs ativas em `cad_vendedor`, `cfg_area_extracao`, `cfg_area_cotacao`, `cfg_area_limite`, `cfg_area_comissao_modalidade`, `mov_jb` — erro retornado pelo PostgreSQL, tratado como exceção 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `Area` (Active Record) | Entidade principal — `cad_area` |
| `TStandardList` | Herança — fornece onReload, onDelete, onSearch, exportação |
| `BootstrapFormBuilder` | Widget de formulário |
| `BootstrapDatagridWrapper` | Widget de lista |
| `TTransaction` (banco `permission`) | Todas as operações de I/O |
| `GerenteForm`, `VendedorForm` | Referenciam `area_id` como FK |
| `AreaExtracaoList` | Configura extrações por área |
| `AreaCotacaoForm/List` | Configura cotações por área |
| `AreaLimiteForm/List` | Configura limites por área |
| `AreaComissaoModalidadeForm/List` | Configura comissões por área |
| `BilheteRestService` | Filtra sorteios e validações por `area_id` |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Segurança | Acesso restrito a perfis autorizados pelo sistema Adianti (menu.xml + SystemGroup) | `app/config/menu.xml` + Adianti ACL | 🟡 |
| Consistência | Todas as escritas dentro de `TTransaction` com rollback em exceção | `AreaForm.php:72-92`, `AreaList.php:241-259` | 🟢 |
| Usabilidade | Filtros persistidos na sessão; quick-search inline na header da lista | `AreaList.php:68`, `AreaList.php:138-143` | 🟢 |
| Exportação | CSV, PDF e XML disponíveis via dropdown | `AreaList.php:149-155` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar área
Dado que o usuário está autenticado e na tela AreaList
Quando clica em "+" e preenche descricao="Norte" e ativo="S" e salva
Então a área é persistida em cad_area com area_id gerado por MAX+1
E o formulário é repopulado com o area_id atribuído
E a lista é recarregada exibindo a nova área

# Happy path — editar área
Dado que existe uma área com area_id=5 e descricao="Sul"
Quando o usuário clica em "Editar" nessa linha e altera descricao="Sul Expandido" e salva
Então o registro area_id=5 é atualizado em cad_area
E a lista é recarregada com o novo valor

# Happy path — toggle ativo
Dado que a área area_id=3 tem ativo="S"
Quando o usuário clica no ícone de power dessa linha
Então ativo é alterado para "N" e a lista é recarregada mostrando o badge vermelho "Não"

# Falha — descrição vazia
Dado que o usuário está no formulário de nova área
Quando submete sem preencher o campo descricao
Então a validação client-side impede o submit
E uma mensagem de campo obrigatório é exibida

# Falha — exclusão com FK
Dado que a área area_id=1 possui vendedores vinculados
Quando o usuário clica em "Delete" nessa linha
Então o PostgreSQL rejeita o DELETE por violação de FK
E uma TMessage de erro é exibida com a mensagem da exceção
E nenhum dado é alterado (rollback)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar área | Must | Base de todo o modelo de negócio — sem área nada funciona |
| Listar e filtrar áreas | Must | Navegação primária do módulo |
| Toggle ativo | Must | Controla quais áreas estão operacionais |
| Exportar CSV/PDF/XML | Should | Útil para relatórios administrativos |
| Exclusão física | Could | Raramente usada; FK do banco protege integridade |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/area/AreaForm.php` | `AreaForm::__construct` | 🟢 |
| `app/control/area/AreaForm.php` | `AreaForm::onSave` | 🟢 |
| `app/control/area/AreaForm.php` | `AreaForm::onEdit` | 🟢 |
| `app/control/area/AreaForm.php` | `AreaForm::onReload` | 🟢 |
| `app/control/area/AreaForm.php` | `AreaForm::onClose` | 🟢 |
| `app/control/area/AreaList.php` | `AreaList::__construct` | 🟢 |
| `app/control/area/AreaList.php` | `AreaList::onTurnOnOff` | 🟢 |
| `app/control/area/AreaList.php` | `AreaList::onAfterSearch` | 🟢 |
| `app/control/area/AreaList.php` | `AreaList::onShowCurtainFilters` | 🟢 |
| `app/model/entities/Area.php` | `Area` (TRecord) | 🟢 |
| `_reversa_sdd/flowcharts/area.md` | Fluxogramas Mermaid | 🟢 |
