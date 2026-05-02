# Resultado — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Resultado** registra os números sorteados em cada sorteio agendado (`mov_sorteio`). O operador informa até 10 prêmios (4 dígitos cada), e a interface calcula automaticamente via JavaScript o grupo do Jogo do Bicho (01–25) correspondente a cada número. Ao encerrar o sorteio (`situacao='F'`), o banco de dados dispara automaticamente três triggers que calculam os bilhetes premiados — o PHP não executa essa lógica. Sorteios são criados automaticamente pela trigger `trg_mv_cad_extracao_cria_sorteios`; este módulo apenas preenche os resultados.

> **TODO documentado em CLAUDE.md:** Verificar o horário limite da extração antes de permitir salvar o resultado. A validação atual não bloqueia o registro de resultado após hora_limite. 🔴

---

## Responsabilidades

- Listar sorteios com filtro por data e extração 🟢
- Carregar dados do sorteio (extração, data, hora, situação) para leitura 🟢
- Registrar números sorteados (até 10 prêmios de 4 dígitos) 🟢
- Calcular grupo e nome do animal do Jogo do Bicho em tempo real (JS + PHP) 🟢
- Sincronizar campos individuais de prêmio com o campo de texto consolidado 🟢
- Limitar campos editáveis ao `premiacao_maxima` da extração 🟢
- Bloquear edição de sorteios já encerrados (situação `F`) 🟢
- Limpar o resultado de um sorteio aberto (com confirmação) 🟢
- Encerrar o sorteio (com confirmação) — dispara cálculo de ganhadores via trigger 🟢

---

## Interface

### ResultadoList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `data_sorteio` | `=` | `mov_sorteio.data_sorteio` |
| `extracao_id` | `=` | `mov_sorteio.extracao_id` |

Ordenação padrão: `data_sorteio DESC` 🟢

**Colunas:**

| Coluna | Campo | Transformação |
|---|---|---|
| ID | `sorteio_id` | — |
| Extração | `extracao->descricao` | Lazy load |
| Data Sorteio | `data_sorteio` | `TDate::date2br` |
| Hora Sorteio | `hora_sorteio` | — |
| Situação | `situacao` | `A` → badge verde "Aberto"; `F` → badge vermelho "Encerrado" |
| Números Sorteados | `numeros_sorteados` | Vazio → texto cinza "Sem resultado" |

> **Ação disponível:** apenas "Resultado" (editar) — sem botão de exclusão de sorteios. 🟢

### ResultadoForm — Campos

#### Informações do Sorteio (somente leitura)
| Campo | Widget | Fonte | Notas |
|---|---|---|---|
| `sorteio_id` | `TEntry` | `mov_sorteio.sorteio_id` | Oculto; `display:none` |
| `extracao_descricao` | `TEntry` | `extracao->descricao` | Não editável |
| `data_sorteio` | `TEntry` | `mov_sorteio.data_sorteio` | Não editável; formato BR |
| `hora_sorteio` | `TEntry` | `mov_sorteio.hora_sorteio` | Não editável |
| `situacao_display` | `TEntry` | Derivado de `situacao` | "Aberto" / "Encerrado"; não editável |

#### Prêmios (repetição 1–10)
| Campo | Widget | Notas |
|---|---|---|
| `premio_{i}` | `TEntry` (máscara `9999`) | Editável apenas se `situacao='A'` e `i <= premiacao_maxima` |
| `grupo_{i}` | `TEntry` (máscara `99`) | Calculado automaticamente; não editável |
| `descricao_grupo_{i}` | `TEntry` | Nome do animal; calculado; não editável |

#### Consolidado
| Campo | Widget | Notas |
|---|---|---|
| `numeros_sorteados` | `TText` (100% × 60px) | Comma-separated; editável se `situacao='A'`; sincronizado com prêmios individuais |

#### Botões de Ação
| Botão | Ação | Visibilidade |
|---|---|---|
| Salvar | `onSave` | Visível apenas se `situacao='A'` |
| Limpar | `onClear` → `onConfirmClear` | Visível apenas se `situacao='A'` |
| Encerrar | `onCloseDraw` → `onConfirmCloseDraw` | Visível apenas se `situacao='A'` e `numeros_sorteados` não vazio |
| Fechar | `ResultadoList::onReload` | Sempre visível |

---

## Algoritmo — Cálculo de Grupo do Jogo do Bicho

O Jogo do Bicho tem 25 grupos (animais), cada um associado a 4 números de "dezena de milhar" (últimos 2 dígitos de um número de 4 dígitos).

### Regra

```
dezmilhar = int(numero[2:4])   // últimos 2 dígitos
if (dezmilhar == 0) dezmilhar = 100   // "00" → 100 (pertence ao grupo 25 VACA)
grupo = ceil(dezmilhar / 4)           // cada 4 dezenas = 1 grupo
```

### Tabela dos 25 Grupos

| Grupo | Min | Max | Animal |
|---|---|---|---|
| 01 | 01 | 04 | AVESTRUZ |
| 02 | 05 | 08 | AGUIA |
| 03 | 09 | 12 | BURRO |
| 04 | 13 | 16 | BORBOLETA |
| 05 | 17 | 20 | CACHORRO |
| 06 | 21 | 24 | CABRA |
| 07 | 25 | 28 | CARNEIRO |
| 08 | 29 | 32 | CAMELO |
| 09 | 33 | 36 | COBRA |
| 10 | 37 | 40 | COELHO |
| 11 | 41 | 44 | CAVALO |
| 12 | 45 | 48 | ELEFANTE |
| 13 | 49 | 52 | GALO |
| 14 | 53 | 56 | GATO |
| 15 | 57 | 60 | JACARE |
| 16 | 61 | 64 | LEAO |
| 17 | 65 | 68 | MACACO |
| 18 | 69 | 72 | PORCO |
| 19 | 73 | 76 | PAVAO |
| 20 | 77 | 80 | PERU |
| 21 | 81 | 84 | TOURO |
| 22 | 85 | 88 | TIGRE |
| 23 | 89 | 92 | URSO |
| 24 | 93 | 96 | VEADO |
| 25 | 97 | 100 | VACA |

**Exemplos:**
- `1234` → dezmilhar = `34` → grupo `09` → COBRA
- `5600` → dezmilhar = `00` → 100 → grupo `25` → VACA
- `7777` → dezmilhar = `77` → grupo `20` → PERU

Esta lógica existe duplicada em dois lugares:
- **JavaScript** (`chkGrupoDescricao`) — executa on-blur de cada campo prêmio para feedback imediato
- **PHP** (`calcularGrupoDescricao`) — executa em `onEdit` ao carregar sorteio existente

---

## Sincronização de Campos (JavaScript)

Dois campos representam os mesmos dados — prêmios individuais e string consolidada — mantidos em sincronia via JS:

```javascript
// premio_1..10 → numeros_sorteados (on-blur de cada prêmio)
function updateNumerosString() {
    var numeros = [];
    for (var i = 1; i <= 10; i++) {
        if (campo.value.trim() !== "") numeros.push(campo.value);
    }
    numerosField.value = numeros.join(",");
}

// numeros_sorteados → premio_1..10 (on-blur do texto)
function updatePremiosFields() {
    var numeros = numerosField.value.split(",");
    for (var i = 0; i < 10; i++) {
        campo_i.value = numeros[i] || "";
        if (campo_i.value.length === 4) chkGrupoDescricao(i+1);
    }
}
```

> **Fonte primária no servidor:** Em `onSave`, o campo `numeros_sorteados` tem prioridade. Se vazio, o servidor reconstrói a string a partir dos campos `premio_1..10`. 🟢

---

## Regras de Negócio

- **RN-RE-01** Apenas sorteios com `situacao='A'` são editáveis — campos e botões de edição são ocultados/desabilitados para `situacao='F'` 🟢
- **RN-RE-02** Para encerrar um sorteio, `numeros_sorteados` não pode estar vazio — validação server-side em `onConfirmCloseDraw` 🟢
- **RN-RE-03** Número de campos de prêmio ativos = `extracao.premiacao_maxima` — campos acima desse índice são desabilitados via `TQuickForm::disableField` 🟢
- **RN-RE-04** `numeros_sorteados` é a fonte de verdade ao salvar — se o campo de texto estiver preenchido, os campos individuais são ignorados no server-side 🟢
- **RN-RE-05** Ao mudar `situacao` para `'F'`, o banco dispara automaticamente três triggers de cálculo de ganhadores — o PHP não executa essa lógica 🟢
- **RN-RE-06** Sorteios são criados pela trigger `trg_mv_cad_extracao_cria_sorteios` ao inserir/atualizar `cad_extracao` — este módulo não cria sorteios 🟢
- **RN-RE-07** `onClear` e `onCloseDraw` exigem confirmação via `TQuestion` antes de executar 🟢
- **RN-RE-08** Uma vez encerrado (`situacao='F'`), o sorteio não pode ser reaberto pela interface — operação unidirecional 🟢
- **RN-RE-09** Não há validação de horário limite (`hora_limite` da extração) antes de salvar resultado — TODO documentado em CLAUDE.md 🔴
- **RN-RE-10** Não há ação de exclusão de sorteios na UI — `ResultadoList` não tem botão delete 🟢

---

## Fluxo Principal — Registrar Resultado

1. Usuário acessa `ResultadoList` → filtra por data/extração → clica em "Resultado"
2. `onEdit(['key' => $sorteio_id])`:
   - `TTransaction::open('permission')`
   - `new MovSorteio($key)` — carrega sorteio
   - Preenche campos de informação (extração, data, hora, situação)
   - Se `numeros_sorteados` não vazio: explode por `,`, preenche `premio_1..N`, calcula grupo/descricao via `calcularGrupoDescricao`
   - Desabilita campos acima de `extracao->premiacao_maxima` via `TQuickForm::disableField`
   - Se `situacao='F'`: desabilita tudo e oculta botões de ação
   - `TTransaction::close()`
3. Usuário preenche os prêmios (JS sincroniza em tempo real)
4. Clica em "Salvar" → `onSave($param)`:
   - `$this->form->validate()`
   - `TTransaction::open('permission')`
   - `new MovSorteio($data->sorteio_id)` — verifica `situacao='A'`
   - Usa `numeros_sorteados` se preenchido; senão reconstrói de `premio_1..10`
   - `$sorteio->numeros_sorteados = $numeros_string` → `$sorteio->store()`
   - `TTransaction::close()`
   - `TMessage('info', 'Resultado salvo com sucesso!')` → pós-ação: `ResultadoList::onReload`
   - Re-executa `onEdit` para recarregar o formulário

---

## Fluxo Alternativo — Encerrar Sorteio

1. Com números sorteados já salvos, clica em "Encerrar" → `onCloseDraw`:
   - `TQuestion('Deseja realmente encerrar este sorteio? Esta ação não poderá ser desfeita.')` → confirma
2. `onConfirmCloseDraw`:
   - `TTransaction::open('permission')`
   - `new MovSorteio($param['sorteio_id'])` — verifica `numeros_sorteados` não vazio
   - `$sorteio->situacao = 'F'` → `$sorteio->store()`
   - **Trigger automática:** banco executa `trg_mv_sorteio_verifica_ganhadores` (JB), `trg_mv_sorteio_verifica_ganhadores_lotinha` (Lotinha), `trg_mv_sorteio_verifica_ganhadores_qui_sen` (Quininha/Seninha)
   - `TTransaction::close()`
3. Formulário recarrega com todos os campos desabilitados

---

## Fluxo Alternativo — Limpar Resultado

1. Clica em "Limpar" → `onClear`:
   - `TQuestion('Deseja realmente limpar o resultado deste sorteio?')` → confirma
2. `onConfirmClear`:
   - `TTransaction::open('permission')`
   - `new MovSorteio($param['sorteio_id'])` — verifica `situacao='A'`
   - `$sorteio->numeros_sorteados = ''` → `$sorteio->store()`
   - `TTransaction::close()`
3. Formulário recarrega com campos de prêmio vazios

---

## Integração com Triggers do Banco

```
Ao executar: $sorteio->situacao = 'F'; $sorteio->store();

PostgreSQL dispara automaticamente:
  ├── trg_mv_sorteio_verifica_ganhadores      → calcula bilhetes JB premiados
  ├── trg_mv_sorteio_verifica_ganhadores_lotinha → calcula prêmios Lotinha
  └── trg_mv_sorteio_verifica_ganhadores_qui_sen → calcula prêmios Quininha/Seninha

O PHP não tem acesso ao resultado do cálculo — a trigger opera diretamente nas tabelas
mov_jb_sorteio, mov_jb_sort_palpite, mov_bilhetinho_sorteio.
```

---

## Dependências

| Componente | Relação |
|---|---|
| `MovSorteio` (Active Record) | Entidade principal — `mov_sorteio` |
| `Extracao` | FK `extracao_id`; lazy load para `descricao` e `premiacao_maxima` |
| `trg_mv_sorteio_verifica_ganhadores` | Trigger JB — dispara ao `situacao='F'` |
| `trg_mv_sorteio_verifica_ganhadores_lotinha` | Trigger Lotinha |
| `trg_mv_sorteio_verifica_ganhadores_qui_sen` | Trigger Quininha/Seninha |
| `trg_mv_cad_extracao_cria_sorteios` | Cria sorteios automaticamente — não interfere com este módulo |
| `TStandardList` | Herança em ResultadoList |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Segurança | Sorteios encerrados são imutáveis pela UI | `ResultadoForm.php:377` | 🟢 |
| Segurança | Encerramento requer confirmação explícita (TQuestion) | `ResultadoForm.php:479` | 🟢 |
| Consistência | Triggers calculam ganhadores atomicamente com o encerramento | CLAUDE.md (triggers) | 🟢 |
| Correção | Cálculo de grupo duplicado (JS + PHP) — pode divergir se um for atualizado sem o outro | `ResultadoForm.php:118-193, 524-561` | 🟡 |
| Lacuna | Hora limite da extração não validada antes de salvar resultado | CLAUDE.md TODO | 🔴 |
| Lacuna | Não há como reabrir sorteio encerrado via UI | Comportamento observado | 🔴 |

---

## Critérios de Aceitação

```gherkin
# Happy path — registrar resultado
Dado que existe sorteio_id=42 com situacao='A' e premiacao_maxima=5
Quando o usuário preenche premio_1="1234" ... premio_5="9876" e clica em Salvar
Então numeros_sorteados="1234,9876,..." é salvo em mov_sorteio
E os campos premio_6..10 permanecem desabilitados
E grupo_1 exibe "09" e descricao_grupo_1 exibe "COBRA"

# Happy path — encerrar sorteio
Dado que sorteio_id=42 tem numeros_sorteados="1234,5678,..." e situacao='A'
Quando o usuário clica em Encerrar e confirma
Então situacao é alterado para 'F'
E triggers de cálculo de ganhadores são disparadas automaticamente
E o formulário recarrega com todos os campos desabilitados

# Falha — tentar editar sorteio encerrado
Dado que sorteio_id=10 tem situacao='F'
Quando o usuário clica em Resultado
Então todos os campos aparecem desabilitados
E os botões Salvar, Limpar e Encerrar estão ocultos

# Falha — encerrar sem números
Dado que sorteio_id=42 tem numeros_sorteados='' e situacao='A'
Quando onConfirmCloseDraw é chamado
Então exceção "Não é possível encerrar um sorteio sem números sorteados" é lançada

# Happy path — cálculo de grupo
Dado que o usuário digita "1234" no campo premio_1
Quando o campo perde o foco
Então JS executa: dezmilhar = int("34") = 34; grupo = "09"; descricao = "COBRA"
E grupo_1 exibe "09" e descricao_grupo_1 exibe "COBRA"

# Caso especial — número terminado em "00"
Dado que o usuário digita "1200" no campo premio_1
Quando chkGrupoDescricao executa
Então dezmilhar = int("00") = 0 → normaliza para 100
E grupo = "25" e descricao = "VACA"

# Happy path — limpar resultado
Dado que sorteio_id=42 tem numeros_sorteados="1234,5678" e situacao='A'
Quando o usuário clica em Limpar e confirma
Então numeros_sorteados é gravado como ''
E os campos premio_1..10 ficam vazios
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Registrar números sorteados | Must | Função central do módulo |
| Encerrar sorteio (dispara triggers) | Must | Sem encerramento, ganhadores não são calculados |
| Bloquear edição de sorteio encerrado | Must | Integridade do resultado — não pode ser alterado após cálculo |
| Cálculo de grupo JB em tempo real (JS) | Must | Feedback visual essencial para o operador confirmar os números |
| Cálculo de grupo JB server-side (PHP) | Must | Necessário para exibir grupos corretamente ao reabrir o form |
| Limpar resultado | Should | Correção operacional antes do encerramento |
| Validação de hora_limite antes de salvar | Must | TODO atual — sem isso operador pode registrar resultado fora do prazo 🔴 |
| Reabrir sorteio encerrado | Won't | Não implementado — requereria anular trigger de ganhadores |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/resultado/ResultadoForm.php` | `ResultadoForm::__construct` | 🟢 |
| `app/control/resultado/ResultadoForm.php` | `ResultadoForm::onEdit` | 🟢 |
| `app/control/resultado/ResultadoForm.php` | `ResultadoForm::onSave` | 🟢 |
| `app/control/resultado/ResultadoForm.php` | `ResultadoForm::onClear` / `onConfirmClear` | 🟢 |
| `app/control/resultado/ResultadoForm.php` | `ResultadoForm::onCloseDraw` / `onConfirmCloseDraw` | 🟢 |
| `app/control/resultado/ResultadoForm.php` | `ResultadoForm::calcularGrupoDescricao` | 🟢 |
| `app/control/resultado/ResultadoForm.php` | JS `chkGrupoDescricao` / `updateNumerosString` / `updatePremiosFields` | 🟢 |
| `app/control/resultado/ResultadoList.php` | `ResultadoList::__construct` | 🟢 |
| `app/model/entities/MovSorteio.php` | `MovSorteio` (TRecord → `mov_sorteio`) | 🟢 |
| `_reversa_sdd/flowcharts/resultado.md` | Fluxogramas Mermaid | 🟢 |
