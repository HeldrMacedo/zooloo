# Área Cotação — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Área Cotação** configura o multiplicador de premiação (cotação) por combinação de área, extração e modalidade. A cotação é o fator pelo qual o valor apostado é multiplicado para calcular o prêmio. Por exemplo: R$1 apostado na Milhar com cotação 4000 resulta em prêmio de R$4.000. O campo `extracao_id` é opcional — uma cotação sem extração funciona como configuração global da área para aquela modalidade; uma cotação com extração específica sobrepõe a global. O `ModalidadeRestService` usa essa tabela para retornar as cotações ao app móvel.

---

## Responsabilidades

- Criar e editar configurações de cotação por combinação área/extração/modalidade 🟢
- Validar unicidade da combinação (area_id + extracao_id + modalidade_id) antes de persistir 🟢
- Listar cotações com filtro por área, extração e modalidade 🟢
- Excluir configurações de cotação 🟢
- Servir como fonte de multiplicadores de premiação para o app móvel via `ModalidadeRestService` 🟢

---

## Interface

### AreaCotacaoForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `area_cotacao_id` | `TEntry` | `cfg_area_cotacao.area_cotacao_id` | — | Não editável; auto MAX+1 |
| `area_id` | `TDBCombo` | `cfg_area_cotacao.area_id` | Sim | Filtra `ativo='S'`; com busca habilitada |
| `extracao_id` | `TDBCombo` | `cfg_area_cotacao.extracao_id` | **Não** | Filtra `ativo='S'`; `NULL` = cotação global da área |
| `modalidade_id` | `TDBCombo` | `cfg_area_cotacao.modalidade_id` | Sim | Filtra `ativo='S'`; exibe `{jogo_id} - {apresentacao}` |
| `multiplicador` | `TNumeric` | `cfg_area_cotacao.multiplicador` | Sim | Decimal 2 casas; validado como numérico |

### AreaCotacaoList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `area_cotacao_id` | `=` | `cfg_area_cotacao.area_cotacao_id` |
| `area_id` | `=` | `cfg_area_cotacao.area_id` |
| `extracao_id` | `=` | `cfg_area_cotacao.extracao_id` |
| `modalidade_id` | `=` | `cfg_area_cotacao.modalidade_id` |

Ordenação padrão: `area_cotacao_id ASC` 🟢

---

## Regras de Negócio

- **RN-AC-01** `area_id`, `modalidade_id` e `multiplicador` são obrigatórios 🟢
- **RN-AC-02** `extracao_id` é opcional — `NULL` representa cotação global da área para a modalidade 🟢
- **RN-AC-03** A combinação (`area_id`, `extracao_id`, `modalidade_id`) deve ser única — validada por query antes do INSERT/UPDATE 🟢
- **RN-AC-04** Na validação de unicidade, `extracao_id = NULL` usa `IS NULL` em vez de `= NULL` para compatibilidade SQL 🟢
- **RN-AC-05** Em edição, o próprio registro é excluído da verificação de unicidade via `area_cotacao_id != X` 🟢
- **RN-AC-06** `area_cotacao_id` usa `IDPOLICY='max'` — `MAX(area_cotacao_id) + 1` 🟢
- **RN-AC-07** O combo de área filtra apenas áreas com `ativo='S'` 🟢
- **RN-AC-08** O combo de extração filtra apenas extrações com `ativo='S'` 🟢
- **RN-AC-09** O combo de modalidade filtra apenas modalidades com `ativo='S'` e exibe `{jogo_id} - {apresentacao}` 🟢
- **RN-AC-10** O `ModalidadeRestService` lê esta tabela para montar a cotação por modalidade disponível ao vendedor no app. Quando `extracao_id` é específico, sobrepõe a cotação global. 🟡

---

## Fluxo Principal — Criar Cotação

1. Usuário abre `AreaCotacaoList` → clica em "+"
2. `AreaCotacaoForm` abre no painel direito
3. Usuário seleciona área, (opcionalmente) extração, modalidade e preenche multiplicador
4. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - Validações server-side: `area_id`, `modalidade_id`, `multiplicador` obrigatórios
   - Constrói `TCriteria` com `area_id + modalidade_id + extracao_id (ou IS NULL)`
   - Se `area_cotacao_id` preenchido: adiciona filtro `!= area_cotacao_id`
   - `AreaCotacao::getObjects($criteria)` → se retornar resultado: lança exceção de duplicidade
   - `new AreaCotacao()` (ou carrega existente se edição) → `fromArray($data)` → `store()`
   - `TForm::sendData('form_area_cotacao', {area_cotacao_id})`
   - `TTransaction::close()`
   - Dispara `AreaCotacaoList::onReload`

---

## Fluxo Alternativo — Cotação Global vs. Cotação por Extração

```
Cotação sem extracao_id (NULL):
  → Aplicada a todas as extrações da área para essa modalidade
  → ModalidadeRestService retorna como cotação padrão

Cotação com extracao_id específico:
  → Sobrepõe a cotação global para essa extração/área/modalidade
  → ModalidadeRestService prioriza a cotação específica sobre a global
```

**Precedência** (inferida do ModalidadeRestService): cotação com `extracao_id` específico > cotação com `extracao_id = NULL`. 🟡

---

## Fluxo Alternativo — Validação de Unicidade

```php
$criteria->add(new TFilter('area_id', '=', $area_id));
$criteria->add(new TFilter('modalidade_id', '=', $modalidade_id));
if ($extracao_id) {
    $criteria->add(new TFilter('extracao_id', '=', $extracao_id));
} else {
    $criteria->add(new TFilter('extracao_id', 'IS', NULL));
}
if ($area_cotacao_id) {  // edição
    $criteria->add(new TFilter('area_cotacao_id', '!=', $area_cotacao_id));
}
$existing = AreaCotacao::getObjects($criteria);
if ($existing) throw new Exception('Já existe uma configuração...');
```

---

## Dependências

| Componente | Relação |
|---|---|
| `AreaCotacao` (Active Record) | Entidade principal — `cfg_area_cotacao` |
| `Area` | FK `area_id` — combo filtra `ativo='S'` |
| `Extracao` | FK `extracao_id` (opcional) — combo filtra `ativo='S'` |
| `Modalidade` | FK `modalidade_id` — combo filtra `ativo='S'` |
| `TStandardList` | Herança em AreaCotacaoList |
| `ModalidadeRestService` | Lê cotações para retornar ao app móvel |
| `BilheteRestService` | Usa cotação para calcular prêmio esperado ao registrar bilhete 🟡 |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Unicidade (area+extracao+modalidade) enforçada em PHP antes do store | `AreaCotacaoForm.php:126-143` | 🟢 |
| Integridade | IS NULL usado corretamente para extracao_id opcional | `AreaCotacaoForm.php:133-134` | 🟢 |
| Consistência | Transação com rollback em toda operação de escrita | `AreaCotacaoForm.php:105-166` | 🟢 |
| Usabilidade | Combos com busca habilitada (enableSearch) para listas longas | `AreaCotacaoForm.php:43,49,56` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar cotação global
Dado que não existe cotação para area_id=1, modalidade_id=3, extracao_id=NULL
Quando o usuário preenche area=1, extração=(vazio), modalidade=3, multiplicador=4000 e salva
Então um registro é criado em cfg_area_cotacao com extracao_id=NULL e multiplicador=4000
E o ModalidadeRestService retorna multiplicador=4000 para essa combinação

# Happy path — criar cotação por extração (sobrescreve global)
Dado que existe cotação global area_id=1, modalidade_id=3, multiplicador=4000
Quando o usuário cria cotação area=1, extração=5, modalidade=3, multiplicador=5000
Então um registro é criado com extracao_id=5 e multiplicador=5000
E o ModalidadeRestService retorna 5000 para sorteios da extração 5

# Falha — duplicidade
Dado que já existe cotação para area=1, extração=5, modalidade=3
Quando o usuário tenta criar outra com a mesma combinação
Então exceção "Já existe uma configuração para esta Área, Extração e Modalidade" é lançada
E nenhum registro é criado

# Happy path — editar cotação (unicidade exclui o próprio registro)
Dado que existe cotacao_id=10 com area=1, extração=5, modalidade=3
Quando o usuário edita esse registro e altera multiplicador=6000 e salva
Então o registro é atualizado sem erro de duplicidade
Pois o filtro de unicidade exclui o area_cotacao_id=10 da verificação

# Falha — multiplicador vazio
Dado que o usuário deixa o campo multiplicador vazio
Quando tenta salvar
Então validação client-side (TRequiredValidator + TNumericValidator) impede o submit
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar cotação por área/modalidade | Must | Define o valor do prêmio — core do negócio |
| Validação de unicidade (area+extracao+modalidade) | Must | Sem isso, múltiplas cotações conflitantes para a mesma combinação |
| Suporte a cotação global (extracao_id NULL) | Must | Permite configuração padrão sem precisar repetir por extração |
| Cotação por extração específica | Should | Sobrescreve o global — mais granularidade |
| Listar e filtrar | Must | Gestão operacional |
| Exclusão de cotação | Should | Permite remover configurações obsoletas |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/area-cotacao/AreaCotacaoForm.php` | `AreaCotacaoForm::__construct` | 🟢 |
| `app/control/area-cotacao/AreaCotacaoForm.php` | `AreaCotacaoForm::onSave` | 🟢 |
| `app/control/area-cotacao/AreaCotacaoForm.php` | `AreaCotacaoForm::onEdit` | 🟢 |
| `app/control/area-cotacao/AreaCotacaoList.php` | `AreaCotacaoList::__construct` | 🟢 |
| `app/model/entities/AreaCotacao.php` | `AreaCotacao` (TRecord → `cfg_area_cotacao`) | 🟢 |
| `_reversa_sdd/flowcharts/area-cotacao.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/traceability/spec-impact-matrix.md` | Impacto: ModalidadeRestService depende desta tabela | 🟢 |
