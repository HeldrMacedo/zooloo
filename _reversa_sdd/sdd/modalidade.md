# Modalidade — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Modalidade** gerencia os tipos de aposta disponíveis na banca (Milhar, Centena, Dezena, Grupo, Duque, Terno, etc.). Cada modalidade está vinculada a um tipo de jogo (`int_jogo`) e define os multiplicadores de premiação, limites de palpite e descarga. O formulário possui comportamento dinâmico via AJAX (`onChangeJogo`) que habilita ou desabilita campos de multiplicador de colocação conforme as regras do jogo selecionado.

---

## Responsabilidades

- Criar e editar modalidades vinculadas a tipos de jogo (`int_jogo`) 🟢
- Garantir que cada tipo de jogo tenha no máximo uma modalidade (combo filtra jogos já usados) 🟢
- Calcular automaticamente o campo `ordem` como `MAX(ordem) + 1` na criação 🟢
- Exibir/ocultar campos de multiplicador de colocação via callback AJAX `onChangeJogo` 🟢
- Mostrar alerta de orientação do jogo quando `informar_valores_modalidade = 'N'` 🟢
- Listar modalidades com filtro por descrição e tipo de jogo 🟢
- Exportar listagem em CSV, PDF e XML 🟢

---

## Interface

### ModalidadeForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Padrão | Notas |
|---|---|---|---|---|---|
| `modalidade_id` | `TEntry` | `cad_modalidade.modalidade_id` | — | auto (MAX+1) | Não editável |
| `jogo_id` | `TDBCombo` | `cad_modalidade.jogo_id` | Sim | — | Filtrado: `ativo='S'`, `filtro_banca=1`, `NOT IN cad_modalidade`; ocultado em modo edição |
| `jogo_descricao` | `TEntry` | — (read-only) | — | — | Exibido em modo edição no lugar do combo |
| `apresentacao` | `TEntry` | `cad_modalidade.apresentacao` | Sim | — | Nome de exibição da modalidade |
| `multiplicador` | `TNumeric` | `cad_modalidade.multiplicador` | Sim | — | Decimal 2 casas; desabilitado se `informar_valores_modalidade='N'` |
| `multiplicadorColocacao01` | `TNumeric` | `cad_modalidade.multiplicador_colocacao_01` | Não | — | Exibido apenas para MILHAR_MOTO (IDs 34/35/36) |
| `limite_descarga` | `TNumeric` | `cad_modalidade.limite_descarga` | Não | — | Desabilitado se `informar_valores_modalidade='N'` |
| `limite_palpite` | `TNumeric` | `cad_modalidade.limite_palpite` | Não | — | Desabilitado se `informar_valores_modalidade='N'` |
| `ativo` | `TCombo` | `cad_modalidade.ativo` | Não | `S` | S=Sim / N=Não |

**Campos na entidade mas não editáveis no form:**

| Campo | Tipo | Notas |
|---|---|---|
| `ordem` | `int` | Calculado automaticamente no PHP (MAX+1) |
| `multiplicador_colocacao_02..05` | `numeric` | Não há widgets mapeados no form atual 🔴 |
| `limite_aceite` | — | Sem widget no form 🔴 |
| `limite_min_sorteio_diario` | — | Sem widget no form 🔴 |
| `limite_min_sorteio_colocacao_diario` | — | Sem widget no form 🔴 |

### Constantes de IDs de Modalidade (usadas em BilheteRestService)

| Constante | Valor | Significado |
|---|---|---|
| `Modalidade::MILHAR_INSTANTANEA` | `22` | Milhar instantânea — regras especiais |
| `Modalidade::MILHAR_MOTO_01` | `34` | Milhar Moto 1ª posição |
| `Modalidade::MILHAR_MOTO_02` | `35` | Milhar Moto 2ª posição |
| `Modalidade::MILHAR_MOTO_03` | `36` | Milhar Moto 3ª posição |

### ModalidadeList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `descricao` | `LIKE` | `cad_modalidade.apresentacao` |
| `jogo_id` | `=` | `cad_modalidade.jogo_id` |

---

## Regras de Negócio

- **RN-MO-01** Cada `int_jogo` pode ter no máximo uma modalidade — o combo de criação usa `NOT IN (SELECT jogo_id FROM cad_modalidade)` para excluir jogos já usados 🟢
- **RN-MO-02** Em modo edição, o combo `jogo_id` é ocultado e substituído pelo campo read-only `jogo_descricao` — o jogo não pode ser trocado após criação 🟢
- **RN-MO-03** `ordem` é calculado em PHP na criação: `MAX(ordem) + 1` sobre todos os registros de `cad_modalidade` via `Modalidade::all()` 🟢
- **RN-MO-04** Se `IntJogo.informar_valores_modalidade = 'N'`, os campos `multiplicador`, `limite_descarga` e `limite_palpite` são desabilitados no formulário e uma mensagem de orientação do jogo é exibida 🟢
- **RN-MO-05** Se `jogo_id` for MILHAR_MOTO_01/02/03, o campo `multiplicadorColocacao01` é exibido (oculto por padrão) 🟢
- **RN-MO-06** `filtro_banca = 1` é aplicado ao `TCriteria` do combo de jogos para limitar ao escopo desta banca 🟢
- **RN-MO-07** `modalidade_id` usa `IDPOLICY='max'` — `MAX(modalidade_id) + 1` 🟢
- **RN-MO-08** Os campos `multiplicador_colocacao_02..05`, `limite_aceite`, `limite_min_sorteio_diario` e `limite_min_sorteio_colocacao_diario` existem no banco mas não possuem widgets no formulário atual — não são editáveis pela UI 🔴

---

## Fluxo Principal — Criar Modalidade

1. Usuário abre `ModalidadeList` → clica em "+"
2. `ModalidadeForm` abre no painel direito — combo `jogo_id` visível, `jogo_descricao` oculto
3. Usuário seleciona um jogo → `onChangeJogo` é chamado via AJAX:
   - Carrega `IntJogo` do banco
   - Se `informar_valores_modalidade='N'`: desabilita multiplicador/limites e exibe orientação
   - Se `jogo_id` é MILHAR_MOTO_*: exibe campo `multiplicadorColocacao01`
4. Usuário preenche `apresentacao`, `multiplicador`, limites e `ativo`
5. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - `form->validate()`
   - `isNew = empty(modalidade_id)` → `new Modalidade()`
   - `Modalidade::all()` → `MAX(ordem) + 1`
   - `modalidade->fromArray($data)` → `store()`
   - `TForm::sendData('form_modalidade', {modalidade_id})`
   - `TTransaction::close()`
   - Dispara `ModalidadeList::onReload`

---

## Fluxo Principal — Editar Modalidade

1. Usuário clica em "Editar" → `onEdit(['key' => $id])` → `onReload(['key' => $id])`:
   - Carrega `Modalidade($key)` → `toArray()`
   - Popula `jogo_descricao = jogo->descricao`
   - `TQuickForm::hideField('jogo_id')` + `showField('jogo_descricao')` — jogo não é editável
   - `form->setData($data)`
2. Usuário altera campos editáveis e salva
3. `onSave`: detecta `modalidade_id` preenchido → `Modalidade::find($id)` → `fromArray($data)` → `store()` — `ordem` **não** é recalculada na edição 🟢

---

## Fluxo Alternativo — onChangeJogo (AJAX)

```
Evento: usuário seleciona um jogo no combo
Chamada: ModalidadeForm::onChangeJogo(['jogo_id' => X])
1. TTransaction::open('permission')
2. IntJogo::find(jogo_id) → $jogo
3. TTransaction::close()
4. if informar_valores_modalidade == 'N':
     disableField(multiplicador, limite_descarga, limite_palpite)
     atualizarAlerta($jogo->orientacao)  ← JS inline
   else:
     enableField(multiplicador, limite_descarga, limite_palpite)
     atualizarAlerta(null)
5. if jogo_id in [MILHAR_MOTO_01, _02, _03]:
     showField(multiplicadorColocacao01)
```

---

## Dependências

| Componente | Relação |
|---|---|
| `Modalidade` (Active Record) | Entidade principal — `cad_modalidade` |
| `IntJogo` | FK `jogo_id`; `informar_valores_modalidade` controla UI |
| `AreaCotacaoForm` | FK `modalidade_id` — cotação por área/extração/modalidade |
| `AreaLimiteForm` | FK `modalidade_id` — limite de aposta por modalidade |
| `AreaComissaoModalidadeForm` | FK `modalidade_id` — comissão por modalidade |
| `ExtracaoDescargaForm` | FK `modalidade_id` — descarga por modalidade |
| `PalpiteCotadoForm` | FK `modalidade_id` — cotação especial de palpites |
| `BilheteRestService` | Usa constantes `MILHAR_INSTANTANEA`, `MILHAR_MOTO_*` para regras especiais |
| `ModalidadeRestService` | Retorna modalidades disponíveis para o app móvel |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Um jogo por modalidade — enforçado via `NOT IN` no combo | `ModalidadeForm.php:42` | 🟢 |
| Usabilidade | Campos dinâmicos via AJAX — UX adaptada ao tipo de jogo | `ModalidadeForm.php:123-153` | 🟢 |
| Consistência | Ordem calculada via MAX+1 em PHP — risco de race condition em concurrent inserts | `ModalidadeForm.php:169-176` | 🟡 |
| Consistência | Transação com rollback em todas as operações | `ModalidadeForm.php:158-208` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar modalidade
Dado que o jogo "Milhar" (jogo_id=1) não está em cad_modalidade
Quando o usuário seleciona "Milhar" no combo, preenche apresentacao="MIL",
  multiplicador=6000, ativo="S" e salva
Então uma modalidade é criada em cad_modalidade com jogo_id=1, ordem=MAX+1
E o formulário é repopulado com modalidade_id gerado
E a lista é recarregada

# Happy path — onChangeJogo desabilita campos
Dado que IntJogo id=10 tem informar_valores_modalidade='N' e orientacao='Apostas fixas'
Quando o usuário seleciona esse jogo
Então os campos multiplicador, limite_descarga e limite_palpite são desabilitados
E o alerta "Apostas fixas" é exibido abaixo do formulário

# Happy path — editar modalidade
Dado que existe modalidade_id=5
Quando o usuário clica em Editar
Então o campo jogo_id fica oculto e jogo_descricao read-only é exibido
E o campo jogo não pode ser alterado

# Falha — jogo já usado
Dado que o jogo "Centena" já tem uma modalidade cadastrada
Quando o usuário abre o formulário de nova modalidade
Então "Centena" não aparece no combo de seleção

# Happy path — MILHAR_MOTO exibe colocação
Dado que o usuário seleciona jogo_id=34 (MILHAR_MOTO_01)
Quando onChangeJogo é chamado
Então o campo multiplicadorColocacao01 é exibido no formulário
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar modalidade | Must | Define os tipos de aposta — core do negócio |
| Garantia de 1 jogo por modalidade | Must | Integridade do catálogo de jogos |
| Cálculo de ordem (MAX+1) | Must | Define ordem de exibição no app |
| onChangeJogo AJAX | Must | UX essencial — campos errados podem ser preenchidos sem isso |
| Constantes MILHAR_INSTANTANEA/MOTO | Must | Usadas por BilheteRestService — impacto direto no REST |
| Campos multiplicador_colocacao_02..05 | Should | Existem no banco mas sem UI — precisam ser mapeados |
| Exportação CSV/PDF/XML | Should | Relatórios administrativos |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/modalidade/ModalidadeForm.php` | `ModalidadeForm::__construct` | 🟢 |
| `app/control/modalidade/ModalidadeForm.php` | `ModalidadeForm::onSave` | 🟢 |
| `app/control/modalidade/ModalidadeForm.php` | `ModalidadeForm::onChangeJogo` | 🟢 |
| `app/control/modalidade/ModalidadeForm.php` | `ModalidadeForm::onReload` | 🟢 |
| `app/control/modalidade/ModalidadeForm.php` | `ModalidadeForm::onEdit` | 🟢 |
| `app/model/entities/Modalidade.php` | `Modalidade` (TRecord → `cad_modalidade`) | 🟢 |
| `app/model/entities/Modalidade.php` | `Modalidade::MILHAR_*` (constantes) | 🟢 |
| `app/model/entities/Modalidade.php` | `Modalidade::get_jogo` | 🟢 |
| `_reversa_sdd/flowcharts/modalidade.md` | Fluxogramas Mermaid | 🟢 |
