# Área Limite — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Área Limite** configura o valor máximo de aposta por palpite para uma combinação de área e modalidade. Quando um vendedor tenta registrar um bilhete via app móvel, o `BilheteRestService` consulta este limite e aplica a lógica `COALESCE(cfg_area_limite.limite_palpite, cfg_parametros.limite_global)` — o limite específico da área tem precedência sobre o limite global dos parâmetros. Sem registro em `cfg_area_limite`, o `limite_global` de `cfg_parametros` é o fallback.

> **Atenção:** O campo no banco e na entidade é `limite_palpite`, mas o flowchart e o fluxograma referenciam `limite_valor`. O código PHP usa `limite_palpite`. 🟢

---

## Responsabilidades

- Criar e editar limites de aposta por combinação área/modalidade 🟢
- Validar unicidade da combinação (area_id + modalidade_id) antes de persistir 🟢
- Listar limites com filtro por área, modalidade e faixa de valor 🟢
- Servir como fonte de limite de palpite para `BilheteRestService` via `COALESCE` 🟢

---

## Interface

### AreaLimiteForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `area_limite_id` | `TEntry` | `cfg_area_limite.area_limite_id` | — | Não editável; auto MAX+1 |
| `area_id` | `TDBCombo` | `cfg_area_limite.area_id` | Sim | Lista todas as áreas (sem filtro ativo) |
| `modalidade_id` | `TDBCombo` | `cfg_area_limite.modalidade_id` | Sim | Lista todas as modalidades (sem filtro ativo) |
| `limite_palpite` | `TNumeric` | `cfg_area_limite.limite_palpite` | Sim | Decimal 2 casas; `TRequiredValidator` |

> **Observação:** Os combos de área e modalidade não filtram por `ativo='S'` neste form — diferente de `AreaCotacaoForm`. 🟡

### AreaLimiteList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `area_limite_id` | `=` | `cfg_area_limite.area_limite_id` |
| `area_id` | `=` | `cfg_area_limite.area_id` |
| `modalidade_id` | `=` | `cfg_area_limite.modalidade_id` |
| `limite_palpite_min` | `>=` | `cfg_area_limite.limite_palpite` |
| `limite_palpite_max` | `<=` | `cfg_area_limite.limite_palpite` |

Ordenação padrão: `area_limite_id ASC` 🟢

---

## Regras de Negócio

- **RN-AL-01** `area_id`, `modalidade_id` e `limite_palpite` são obrigatórios 🟢
- **RN-AL-02** A combinação (`area_id`, `modalidade_id`) deve ser única — validada por query antes do INSERT 🟢
- **RN-AL-03** A validação de unicidade é feita **apenas na criação** (`area_limite_id` vazio) — na edição não há re-verificação 🟢
- **RN-AL-04** `area_limite_id` usa `IDPOLICY='max'` — `MAX(area_limite_id) + 1` 🟢
- **RN-AL-05** `BilheteRestService` usa `COALESCE(cfg_area_limite.limite_palpite, cfg_parametros.limite_global)` para determinar o limite efetivo de aposta — o limite da área tem precedência 🟡
- **RN-AL-06** Sem registro em `cfg_area_limite` para a combinação área+modalidade, o `limite_global` de `cfg_parametros` é aplicado como fallback 🟡
- **RN-AL-07** Não há restrição de área por permissão de gerente neste módulo (diferente de AreaExtracao e VendedorList) 🟡
- **RN-AL-08** A listagem suporta filtro por faixa de valor (`limite_palpite_min` / `limite_palpite_max`) — operadores `>=` e `<=` 🟢

---

## Fluxo Principal — Criar Limite

1. Usuário abre `AreaLimiteList` → clica em "+"
2. `AreaLimiteForm` abre no painel direito
3. Usuário seleciona área, modalidade e preenche o valor do limite
4. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - Validações client-side via `TRequiredValidator`
   - Se `area_limite_id` vazio (criação):
     - Constrói `TCriteria` com `area_id + modalidade_id`
     - `AreaLimite::getObjects($criteria)` → se existir: lança exceção de duplicidade
   - `new AreaLimite()` → `fromArray($data)` → `store()`
   - `TForm::sendData('form_area_limite', {area_limite_id})`
   - `TTransaction::close()`
   - Dispara `AreaLimiteList::onReload`

---

## Fluxo Alternativo — Editar Limite

1. Usuário clica em "Editar" → `onEdit(['key' => $id])`:
   - `TTransaction::open('permission')`
   - `new AreaLimite($key)` → `form->setData($object)`
   - `TTransaction::close()`
2. Usuário altera `limite_palpite` (área e modalidade permanecem editáveis mas **não** há re-verificação de unicidade) 🟡
3. `onSave`: `area_limite_id` presente → pula verificação de unicidade → `fromArray($data)` → `store()`

---

## Fluxo Alternativo — Limpar Formulário

```
Botão "Clear" → onClear($param) → $this->form->clear()
```
Diferente do bug em ExtracaoForm, aqui `clear()` é chamado corretamente. 🟢

---

## Integração com BilheteRestService

```sql
-- Lógica inferida do BilheteRestService
SELECT COALESCE(al.limite_palpite, p.limite_global) AS limite_efetivo
FROM cfg_parametros p
LEFT JOIN cfg_area_limite al
  ON al.area_id = :area_id AND al.modalidade_id = :modalidade_id
LIMIT 1
```

Se `cfg_area_limite` não tem registro para a combinação → `COALESCE` retorna `p.limite_global`. 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `AreaLimite` (Active Record) | Entidade principal — `cfg_area_limite` |
| `Area` | FK `area_id` — combo de seleção |
| `Modalidade` | FK `modalidade_id` — combo de seleção |
| `Parametros` | Fornece `limite_global` como fallback quando não há registro por área |
| `BilheteRestService` | Consulta esta tabela via COALESCE para validar valor de aposta |
| `TStandardList` | Herança em AreaLimiteList |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Unicidade (area+modalidade) enforçada em PHP na criação | `AreaLimiteForm.php:81-91` | 🟢 |
| Consistência | Transação com rollback em operações de escrita | `AreaLimiteForm.php:76-112` | 🟢 |
| Flexibilidade | Fallback para limite_global quando sem configuração por área | `Parametros` + `BilheteRestService` | 🟡 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar limite
Dado que não existe limite para area_id=1, modalidade_id=3
Quando o usuário preenche area=1, modalidade=3, limite_palpite=500.00 e salva
Então um registro é criado em cfg_area_limite
E TForm::sendData popula area_limite_id no formulário
E BilheteRestService passa a usar 500.00 como limite para essa combinação

# Fallback para limite_global
Dado que não existe registro em cfg_area_limite para area_id=2, modalidade_id=5
E cfg_parametros.limite_global = 200.00
Quando BilheteRestService valida uma aposta dessa combinação
Então o limite efetivo é 200.00 (fallback via COALESCE)

# Falha — duplicidade na criação
Dado que já existe limite para area_id=1, modalidade_id=3
Quando o usuário tenta criar outro para a mesma combinação
Então exceção "Já existe um limite cadastrado para esta área e modalidade" é lançada
E nenhum registro é criado

# Happy path — editar limite (sem re-verificação de unicidade)
Dado que existe area_limite_id=7 com area=1, modalidade=3, limite=500
Quando o usuário o edita e altera limite para 800 e salva
Então o registro é atualizado para 800 sem erro
(Nota: unicidade não é re-verificada na edição)

# Happy path — filtro por faixa de valor
Dado que existem limites com valores 100, 500 e 1000
Quando o usuário filtra com limite_min=200 e limite_max=600
Então apenas o limite 500 é exibido na grid

# Falha — limite_palpite vazio
Dado que o usuário não preenche limite_palpite
Quando tenta salvar
Então TRequiredValidator impede o submit com mensagem de campo obrigatório
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar limite por área/modalidade | Must | Controla o risco financeiro da banca por área |
| Validação de unicidade na criação | Must | Duplicatas causariam comportamento ambíguo no COALESCE |
| Integração com Parâmetros (fallback) | Must | Sem fallback, ausência de registro quebra validação de bilhete |
| Listar e filtrar por faixa de valor | Must | Navegação e gestão operacional |
| Re-verificação de unicidade na edição | Should | Lacuna atual — editar pode criar conflito lógico |
| Filtro de áreas/modalidades ativas nos combos | Should | Combos atualmente sem filtro ativo='S' |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/area-limite/AreaLimiteForm.php` | `AreaLimiteForm::__construct` | 🟢 |
| `app/control/area-limite/AreaLimiteForm.php` | `AreaLimiteForm::onSave` | 🟢 |
| `app/control/area-limite/AreaLimiteForm.php` | `AreaLimiteForm::onEdit` | 🟢 |
| `app/control/area-limite/AreaLimiteForm.php` | `AreaLimiteForm::onClear` | 🟢 |
| `app/control/area-limite/AreaLimiteList.php` | `AreaLimiteList::__construct` | 🟢 |
| `app/model/entities/AreaLimite.php` | `AreaLimite` (TRecord → `cfg_area_limite`) | 🟢 |
| `_reversa_sdd/flowcharts/area-limite.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/traceability/spec-impact-matrix.md` | Impacto: BilheteRestService depende COALESCE | 🟢 |
