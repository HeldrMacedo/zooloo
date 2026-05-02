# GAP: Bilhetinho — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — módulo presente no Java (allsystem) com configuração e movimento, mas **completamente ausente** no Zooloo PHP (sem entidades, sem controllers, sem telas)

---

## Visão Geral

**Bilhetinho** é um tipo de jogo da banca onde o jogador aposta em um número e concorre a prêmios distribuídos nas 5 primeiras colocações de um sorteio. Diferente do Jogo do Bicho (que usa `cad_modalidade` e `mov_jb`), o Bilhetinho tem sua própria tabela de configuração (`cad_modalidade_bilhetinho`) e suas próprias tabelas de movimento (`mov_bilhetinho`, `mov_bilhetinho_sorteio`).

O Java possuía configuração inline de modalidades bilhetinho (com multiplicadores por colocação) e controle de apostas. No Zooloo, o campo `ativo_bilhetinho` em `cfg_parametros` indica que o jogo é suportado, mas **nenhuma tabela bilhetinho tem entidade PHP, controller ou tela web**.

---

## Estrutura de Dados

### `cad_modalidade_bilhetinho` — Java (`ModalidadeBilhetinho.java`)

**Chave composta:** `(modalidade_id, colocacao)`

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `modalidade_id` | FK `cad_modalidade` (PK parte 1) | Sim | Modalidade do JB pai (ex: Milhar, Dezena) |
| `colocacao` | `integer` (PK parte 2) | Sim | Colocação do prêmio (1 a 5) |
| `multiplicador` | `double` | Sim | Multiplicador do prêmio para esta colocação |
| `limite_descarga` | `double` | Sim | Limite de volume antes do descarrego |
| `limite_palpite` | `double` | Sim | Limite de aposta por palpite |
| `limite_aceite` | `double` | Sim | Limite de aceitação da aposta |
| `ativo` | `boolean` | Sim | Ativo ou inativo |

> **Chave composta:** Um par `(modalidade_id, colocacao)` identifica unicamente a linha. Uma modalidade pode ter até 5 linhas (uma por colocação premiada). 🟢

### Modelo Angular (`IBilhetinho`) — visão agregada

O Angular **agrega as 5 colocações** em um único objeto por modalidade:

| Campo | Tipo | Notas |
|---|---|---|
| `id` | `number` | ID interno (inferido) |
| `apresentacao` | `string` | Nome de exibição da modalidade bilhetinho |
| `multiplicador` | `number` | Valor/preço do bilhetinho |
| `multiplicador01` | `number` | Prêmio multiplicador — 1ª colocação |
| `multiplicador02` | `number` | Prêmio multiplicador — 2ª colocação |
| `multiplicador03` | `number` | Prêmio multiplicador — 3ª colocação |
| `multiplicador04` | `number` | Prêmio multiplicador — 4ª colocação |
| `multiplicador05` | `number` | Prêmio multiplicador — 5ª colocação |
| `limitePalpite` | `number` | Limite por palpite |
| `limiteDescarga` | `number` | Limite de descarga |
| `limiteAceite` | `number` | Limite de aceite |
| `ordem` | `number` | Ordem de exibição |
| `ativo` | `boolean` | Ativo/inativo |
| `intJogo` | `IintJogo` | Referência ao tipo de jogo |

> **Discrepância modelo/banco:** O Angular usa um objeto achatado com `multiplicador01..05`, mas o banco tem uma linha por colocação. A API Spring faz a agregação. 🟡

### `mov_bilhetinho` — movimento de apostas

> Tabela mencionada no `CLAUDE.md`. Sem PHP entity. Estrutura inferida a partir do padrão JB. 🟡

| Campo | Tipo (inferido) | Notas |
|---|---|---|
| `bilhetinho_id` | PK | ID do bilhete |
| `vendedor_id` | FK `cad_vendedor` | |
| `area_id` | FK `cad_area` | |
| `terminal_id` | FK `cad_terminal` | |
| `data_hora` | timestamp | |
| `total_bilhete` | decimal | Valor apostado |
| `cancelado` | `S`/`N` | |

### `mov_bilhetinho_sorteio` — detalhe por sorteio

> Tabela mencionada no `CLAUDE.md`. Sem PHP entity. Estrutura inferida. 🟡

| Campo | Tipo (inferido) | Notas |
|---|---|---|
| `bilhetinho_sorteio_id` | PK | |
| `bilhetinho_id` | FK `mov_bilhetinho` | |
| `sorteio_id` | FK `mov_sorteio` | |
| `palpite` | varchar | Número apostado |
| `valor` | decimal | Valor da aposta |
| `sorteado` | `S`/`N` | Preenchido pela trigger |
| `sorteado_colocacao` | int | Colocação vencedora |
| `sorteado_valor` | decimal | Prêmio calculado |
| `sorteado_pago` | `S`/`N` | Prêmio pago |

---

## Interface no Sistema Java (allsystem — referência)

### Tela de Configuração Bilhetinho

**Comportamento:** Lista + Edição inline — sem rota separada para o formulário. Ao clicar "Editar", o formulário aparece no topo da página (scroll para `#page-heading`).

**Sem botão de criar** — as modalidades bilhetinho são pré-configuradas (seed). **Sem botão de excluir.** 🟢

### Colunas da lista

| Coluna | Dado | Notas |
|---|---|---|
| Modalidades | `intJogo.descricao` | Nome do tipo de jogo |
| Apresentação | `apresentacao` | Nome de exibição |
| Valor Bilhetinho | `multiplicador` | Preço do bilhete — BRL |
| Multip. 01–05 | `multiplicador01`..`05` | Prêmio por colocação — BRL |
| Limite Palpite | `limitePalpite` | BRL |
| Ativo | `ativo ? "Sim" : "Não"` | |
| Ações | Editar | Sem Delete |

### Campos do formulário inline

| Campo | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Bilhetinho | `text` readonly | — | `intJogo.descricao` — não editável |
| Apresentação | `text` | **Sim** | Nome de exibição |
| Ativo | `checkbox` | Sim | |
| Valor Bilhetinho | `number` | **Sim** | Preço do bilhete (`multiplicador`) |
| Multip. 1º prêmio | `number` | **Sim** | `multiplicador01` |
| Multip. 2º prêmio | `number` | **Sim** | `multiplicador02` |
| Multip. 3º prêmio | `number` | **Sim** | `multiplicador03` |
| Multip. 4º prêmio | `number` | **Sim** | `multiplicador04` |
| Multip. 5º prêmio | `number` | **Sim** | `multiplicador05` |
| Limite Palpite | `number` | Não | |
| Limite Descarga | `number` | Não | |
| Limite Aceite | `number` | Não | |

**API:** `PUT /api/bilhetinhos` — atualiza o registro; `GET /api/bilhetinhos` — lista todos (sem id).

---

## Responsabilidades Faltantes no Zooloo

| Responsabilidade | Status |
|---|---|
| Entidade PHP `ModalidadeBilhetinho` → `cad_modalidade_bilhetinho` | 🔴 Ausente |
| Tela de configuração de bilhetinho (list + edit inline) | 🔴 Ausente |
| Entidades PHP `MovBilhetinho` → `mov_bilhetinho` | 🔴 Ausente |
| Entidade PHP `MovBilhetinhoSorteio` → `mov_bilhetinho_sorteio` | 🔴 Ausente |
| API REST para registrar apostas bilhetinho (mobile app) | 🔴 Ausente |
| Tela de consulta de bilhetes bilhetinho (gestor) | 🔴 Ausente |
| Tela de premiação bilhetinho | 🔴 Ausente |

---

## Regras de Negócio

- **RN-BIL-01** Configurações bilhetinho são pré-seeded — não há criação de novos registros via tela (somente edição) 🟢
- **RN-BIL-02** Cada modalidade bilhetinho tem 5 multiplicadores de prêmio (um por colocação) 🟢
- **RN-BIL-03** `ativo_bilhetinho` em `cfg_parametros` controla se o jogo está habilitado para apostas via app 🟢
- **RN-BIL-04** `limite_palpite`, `limite_descarga` e `limite_aceite` controlam o volume de apostas aceitas por número 🟡
- **RN-BIL-05** O `multiplicador` (Valor Bilhetinho) é o preço por bilhete — não o multiplicador de prêmio 🟡
- **RN-BIL-06** `mov_bilhetinho` e `mov_bilhetinho_sorteio` seguem estrutura análoga às tabelas JB (`mov_jb` / `mov_jb_sorteio`) 🟡
- **RN-BIL-07** Triggers de resultado devem existir para `mov_bilhetinho_sorteio` análogas às triggers JB 🔴

---

## Dependências

| Componente | Relação |
|---|---|
| `cad_modalidade_bilhetinho` | Tabela de configuração — chave composta (modalidade_id, colocacao) |
| `cad_modalidade` | FK pai — modalidade associada |
| `int_jogo` | Tipo de jogo (`BIL`) |
| `mov_bilhetinho` | Apostas bilhetinho — sem entidade PHP |
| `mov_bilhetinho_sorteio` | Detalhe por sorteio — sem entidade PHP |
| `cfg_parametros.ativo_bilhetinho` | Feature flag — habilita/desabilita o jogo |
| `mov_sorteio` | Sorteios usados pelo bilhetinho (compartilhado com JB) |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Entidade `ModalidadeBilhetinho` (config) | `ModalidadeBilhetinho.java` | Ausente | 🔴 |
| Tela de configuração (list + inline edit) | Angular presente | Ausente | 🔴 |
| Entidade `MovBilhetinho` | `MovBilhetinho.java` (inferido) | Ausente | 🔴 |
| Entidade `MovBilhetinhoSorteio` | `MovBilhetinhoSorteio.java` (inferido) | Ausente | 🔴 |
| API REST para apostas bilhetinho | Presente (Spring) | Ausente | 🔴 |
| Tabelas no banco | Existem (`mov_bilhetinho`, etc.) | Existem (sem PHP) | Parcial |
| `cfg_parametros.ativo_bilhetinho` | Presente | ✅ Campo existe | — |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — visualizar configuração bilhetinho
Dado que existem modalidades bilhetinho cadastradas
Quando gestor acessa ModalidadeBilhetinhoList
Então lista exibe: modalidade, apresentação, valor bilhetinho, multip. 1–5, limite palpite, ativo

# Happy path — editar multiplicadores
Dado que modalidade bilhetinho Milhar tem multiplicador01=70.00
Quando gestor clica em Editar e altera multiplicador01=75.00 e salva
Então cad_modalidade_bilhetinho atualizado com novo multiplicador para colocacao=1

# Feature flag ativa
Dado que cfg_parametros.ativo_bilhetinho=true
Quando vendedor via app tenta registrar aposta bilhetinho
Então aposta é aceita (quando REST implementado)

# Feature flag inativa
Dado que cfg_parametros.ativo_bilhetinho=false
Quando vendedor via app tenta registrar aposta bilhetinho
Então aposta é recusada

# Sem botão criar
Quando gestor acessa lista bilhetinho
Então não há botão "Novo" — somente edição inline de registros existentes
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Entidade PHP `ModalidadeBilhetinho` + tela de config | Must | Base para configurar o jogo |
| Entidades PHP `MovBilhetinho` + `MovBilhetinhoSorteio` | Must | Base para registrar apostas |
| API REST para apostas bilhetinho (app móvel) | Must | Paridade com `BilheteRestService` do JB |
| Tela de consulta de bilhetes bilhetinho (gestor) | Must | Operação gerencial diária |
| Tela de premiação bilhetinho | Must | Pagamento de prêmios |
| Triggers de resultado bilhetinho | Must | Sem triggers, prêmios não são calculados |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../domain/ModalidadeBilhetinho.java` | Java — `@Table(name = "CAD_MODALIDADE_BILHETINHO")` |
| `allsystem/.../webapp/app/entities/bilhetinho/bilhetinho.component.html` | Java — Angular list+edit |
| `allsystem/.../webapp/app/entities/bilhetinho/bilhetinho.component.ts` | Java — Angular |
| `allsystem/.../webapp/app/entities/bilhetinho/bilhetinho.service.ts` | Java — REST client |
| `allsystem/.../webapp/app/shared/model/Bilhetinho.model.ts` | Java — modelo achatado |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/model/entities/ModalidadeBilhetinho.php` | TRecord → `cad_modalidade_bilhetinho` (PK composta) |
| `app/model/entities/MovBilhetinho.php` | TRecord → `mov_bilhetinho` |
| `app/model/entities/MovBilhetinhoSorteio.php` | TRecord → `mov_bilhetinho_sorteio` |
| `app/control/bilhetinho/ModalidadeBilhetinhoList.php` | Lista + edição inline de configuração |
| `app/service/rest/BilhetinhoRestService.php` | API REST para apostas bilhetinho via app |
