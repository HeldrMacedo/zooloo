# Extração — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Extração** gerencia os sorteios agendados da banca. Cada extração define os dias da semana em que ocorre, o horário limite para apostas, a premiação máxima e o cálculo de sorteio associado. A criação ou edição de uma extração dispara automaticamente o trigger `trg_mv_cad_extracao_cria_sorteios` no banco, que gera os registros de `mov_sorteio` correspondentes.

---

## Responsabilidades

- Criar e editar extrações com configuração de dias da semana 🟢
- Converter seleção múltipla (`TCheckGroup semanas[]`) em 7 campos booleanos individuais (`segunda`…`domingo`) 🟢
- Fixar `filtro_banca = 1` em todas as persistências 🟢
- Listar extrações com filtro por descrição e status ativo 🟢
- Ativar/desativar extrações (campo `ativo`) 🟢
- Exportar listagem em CSV, PDF e XML 🟢

---

## Interface

### ExtracaoForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Padrão | Notas |
|---|---|---|---|---|---|
| `extracao_id` | `TEntry` | `cad_extracao.extracao_id` | — | auto (MAX+1) | Não editável |
| `descricao` | `TEntry` | `cad_extracao.descricao` | Sim | — | `TRequiredValidator` |
| `descricao_mobile` | `TEntry` | `cad_extracao.descricao_mobile` | Sim | — | Abreviação para app móvel |
| `hora_limite` | `TTime` | `cad_extracao.hora_limite` | Sim | — | Formato HH:MM |
| `premiacao_maxima` | `TEntry` | `cad_extracao.premiacao_maxima` | Sim | — | Máscara `999` (inteiro) |
| `dia_sorteio_inicial` | `TDate` | `cad_extracao.dia_sorteio_inicial` | Sim | — | Máscara `dd/mm/yyyy`; banco `yyyy-mm-dd` |
| `calculo_id` | `TDBCombo` | `cad_extracao.calculo_id` | Não | — | FK para `int_calculo_sorteio` |
| `semanas` | `TCheckGroup` | — (multi-select) | Não | — | Converte em 7 campos boolean no onSave |
| `ativo` | `TCombo` | `cad_extracao.ativo` | Não | `S` | S=Sim / N=Não |

**Campos derivados de `semanas[]` no onSave:**

| Campo BD | Valor se selecionado | Valor se não selecionado |
|---|---|---|
| `segunda` | `'S'` | `'N'` |
| `terca` | `'S'` | `'N'` |
| `quarta` | `'S'` | `'N'` |
| `quinta` | `'S'` | `'N'` |
| `sexta` | `'S'` | `'N'` |
| `sabado` | `'S'` | `'N'` |
| `domingo` | `'S'` | `'N'` |

**Campos na entidade mas não editáveis via UI:**

| Campo | Tipo | Notas |
|---|---|---|
| `filtro_banca` | `int` | Fixado em `1` pelo PHP — nunca editável pelo usuário |
| `ultimo_sorteio_numero` | `int` | Gerenciado por trigger |
| `gerar_restante` | — | Propósito 🔴 LACUNA |
| `extracao_instantanea` | — | Propósito 🔴 LACUNA |
| `limite_palpite` | `numeric` | Campo comentado no form (`$limitePalpite`) 🟢 |

### ExtracaoList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `descricao` | `LIKE` | `cad_extracao.descricao` |
| `ativo` | `=` | `cad_extracao.ativo` |

Ordenação padrão: `descricao ASC` 🟢

---

## Regras de Negócio

- **RN-EX-01** `descricao`, `descricao_mobile`, `hora_limite`, `premiacao_maxima` e `dia_sorteio_inicial` são obrigatórios 🟢
- **RN-EX-02** O campo `semanas` é um `TCheckGroup` de seleção múltipla — o PHP converte o array de chaves selecionadas em 7 campos `S`/`N` individuais no banco 🟢
- **RN-EX-03** `filtro_banca` é sempre fixado em `1` pelo código PHP — não é editável pelo usuário. Finalidade: distinguir extrações desta banca de extrações do sistema legado 🟡
- **RN-EX-04** `extracao_id` usa `IDPOLICY='max'` — `MAX(extracao_id) + 1` 🟢
- **RN-EX-05** Ao salvar, o `TCheckGroup` envia apenas os itens **selecionados** como array — dias não selecionados não aparecem no array e são marcados como `'N'` 🟢
- **RN-EX-06** `dia_sorteio_inicial` define a data do primeiro sorteio gerado — o trigger PostgreSQL usa esse campo para criar os `mov_sorteio` subsequentes 🟡
- **RN-EX-07** `calculo_id` referencia `int_calculo_sorteio` — define o algoritmo de cálculo de premiação 🟡
- **RN-EX-08** `limite_palpite` existe na entidade mas está **comentado no formulário** — não é editável via UI atual 🟢
- **RN-EX-09** O onReload converte os 7 campos boolean de volta para o array `semanas[]` ao carregar no `TCheckGroup` 🟢

---

## Fluxo Principal — Criar/Editar Extração

1. Usuário abre `ExtracaoList` → clica em "+" ou "Editar"
2. `ExtracaoForm` abre no painel direito
3. Se edição: `onEdit($param['key'])` → `onReload(['key' => $key])`:
   - `TTransaction::open('permission')`
   - `new Extracao($key)` → `toArray()`
   - Reconstrói `$data['semanas'][]` iterando os 7 campos e adicionando os que forem `'S'`
   - `form->setData((object) $data)`
4. Usuário preenche/altera campos e seleciona dias da semana
5. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - `new Extracao()`
   - Itera `['segunda','terca',...,'domingo']` → atribui `'S'` se presente em `$data->semanas`, senão `'N'`
   - `$extracao->filtro_banca = 1`
   - `$extracao->fromArray((array) $data)` — sobrescreve com dados do form (incluindo `extracao_id` se edição)
   - `$extracao->store()` → INSERT ou UPDATE
   - `TForm::sendData('form_extracao', {extracao_id})`
   - `TTransaction::close()`
   - Dispara `ExtracaoList::onReload` como pós-ação
6. O trigger `trg_mv_cad_extracao_cria_sorteios` é disparado pelo banco no INSERT/UPDATE 🟡

---

## Fluxo Alternativo — Limpar Formulário (bug)

```
onReload($param) sem 'key':
    $this->form->cleat();  // ← BUG: método não existe
```

**Impacto:** Quando o botão "Clear" é acionado sem um key, ou quando `onReload` é chamado sem parâmetros, o PHP lança um `BadMethodCallException` (ou similar) que é silenciado pelo catch vazio em `onEdit`. O formulário **não é limpo** visualmente. 🟢

---

## Bug Documentado — cleat()

```php
// ExtracaoForm.php:180
$this->form->cleat(); // ← TYPO: deveria ser clear()
```

**Localização:** `onReload()`, no branch `else` (quando não há `$param['key']`).
**Impacto:** O formulário não é limpo quando o usuário clica em "Limpar" — campos anteriores permanecem preenchidos.
**Correção:** Alterar `cleat()` para `clear()`.

---

## Trigger de Banco (Integração)

Ao INSERT ou UPDATE em `cad_extracao`, o trigger `trg_mv_cad_extracao_cria_sorteios` (no banco `jb` ou `applications`) é disparado automaticamente pelo PostgreSQL. Ele gera os registros de `mov_sorteio` para os dias configurados a partir de `dia_sorteio_inicial`. O PHP **não controla** essa lógica — ela reside inteiramente no banco. 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `Extracao` (Active Record) | Entidade principal — `cad_extracao` |
| `IntCalculoSorteio` | FK `calculo_id` — algoritmo de cálculo de premiação |
| `TStandardList` | Herança em ExtracaoList |
| `TTransaction` (banco `permission`) | Todas as operações |
| `AreaExtracaoList` | Configura quais extrações estão ativas por área (FK `extracao_id`) |
| `AreaCotacaoForm` | Configura cotações por área/extração (FK `extracao_id`) |
| `MovSorteio` | Gerado automaticamente pelo trigger ao salvar extração |
| `ResultadoForm` | Usa `extracao_id` para buscar sorteios abertos |
| `SorteioRestService` | Filtra sorteios por `extracao_id` |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Consistência | Transação com rollback em todas as operações de escrita | `ExtracaoForm.php:119-153` | 🟢 |
| Integridade | `filtro_banca=1` sempre fixado — garante escopo correto | `ExtracaoForm.php:137` | 🟢 |
| Integração BD | Criação automática de sorteios via trigger PostgreSQL | `CLAUDE.md` — lista de triggers | 🟡 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar extração
Dado que o usuário está na ExtracaoList
Quando clica em "+" e preenche descricao="Tarde", descricao_mobile="TAR",
  hora_limite="14:00", premiacao_maxima=500, dia_sorteio_inicial="01/05/2026",
  seleciona segunda+quarta+sexta e salva
Então um registro é criado em cad_extracao com segunda='S', terca='N', quarta='S',
  quinta='N', sexta='S', sabado='N', domingo='N', filtro_banca=1
E o trigger PostgreSQL gera os mov_sorteio correspondentes
E a lista é recarregada

# Happy path — editar dias da semana
Dado que existe uma extração com segunda='S' e terca='N'
Quando o usuário a edita e adiciona terca ao TCheckGroup e salva
Então cad_extracao.terca='S' é atualizado
E o trigger recalcula os sorteios futuros

# Happy path — carregar extração no form
Dado que existe extracao_id=5 com segunda='S' e quinta='S'
Quando onEdit é chamado com key=5
Então o TCheckGroup exibe apenas segunda e quinta selecionados
E os demais dias estão desmarcados

# Falha — campos obrigatórios
Dado que o usuário não preenche hora_limite
Quando tenta salvar
Então a validação client-side impede o submit com mensagem de campo obrigatório

# Bug — cleat() ao limpar
Dado que o usuário clica em "Limpar" no formulário sem chave ativa
Então form->cleat() lança erro silenciado pelo catch vazio de onEdit
E o formulário NÃO é limpo visualmente
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar extração | Must | Define os sorteios da banca — core do negócio |
| Conversão semanas[] → 7 campos | Must | Lógica de mapeamento sem a qual dias não são salvos |
| Ativar/desativar extração | Must | Controla quais sorteios são gerados |
| Correção cleat() → clear() | Must | Bug impede limpeza do formulário |
| Listar e filtrar | Must | Navegação principal do módulo |
| Exportação CSV/PDF/XML | Should | Relatórios administrativos |
| Campo limite_palpite (comentado) | Won't | Não disponível na UI atual |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/extracao/ExtracaoForm.php` | `ExtracaoForm::__construct` | 🟢 |
| `app/control/extracao/ExtracaoForm.php` | `ExtracaoForm::onSave` | 🟢 |
| `app/control/extracao/ExtracaoForm.php` | `ExtracaoForm::onReload` | 🟢 |
| `app/control/extracao/ExtracaoForm.php` | `ExtracaoForm::onEdit` | 🟢 |
| `app/control/extracao/ExtracaoList.php` | `ExtracaoList::__construct` | 🟢 |
| `app/model/entities/Extracao.php` | `Extracao` (TRecord → `cad_extracao`) | 🟢 |
| `_reversa_sdd/flowcharts/extracao.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/adrs/004-trigger-para-calculo-premios.md` | ADR trigger de sorteios | 🟢 |
