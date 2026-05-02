# GAP: Caixa Vendedor / Geral Caixa — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP parcial — `CaixaRestService::resumo` implementado para o app móvel (por vendedor, data única), mas **não há tela web gerencial** com visão multi-vendedor, período configurável nem filtro por área

---

## Visão Geral

O módulo **Caixa** consolida a apuração financeira por vendedor — quanto cada um vendeu, qual a comissão devida e o líquido a receber. No sistema Java havia duas telas complementares que usam o mesmo endpoint (`api/geralCaixa`):

| Tela Java | Título | Diferença |
|---|---|---|
| `caixa-vendedor` | "Caixa Vendedor" | Data única, filtra por área **e** vendedor; coluna com BUG de label |
| `geral-caixa` | "Geral Caixa" | Período (dataInicial + dataFinal), filtra só por área |

Ambas exibem as mesmas colunas (`área, vendedor, apurado, comissão, total`) e usam o mesmo modelo `IGeralCaixa`. São visões diferentes do mesmo conjunto de dados agregados de `mov_jb`.

No Zooloo, o `CaixaRestService::resumo` implementa a visão **do vendedor** (mobile app, data única, próprios dados). A visão **gerencial** (múltiplos vendedores, período, filtro por área) está ausente.

---

## Estrutura de Dados

### Modelo de retorno (`IGeralCaixa` — Java)

| Campo | Tipo | Descrição |
|---|---|---|
| `area` | `string` | Descrição da área (`cad_area.descricao`) |
| `vendedor` | `string` | Nome do vendedor (`cad_vendedor.nome`) |
| `apurado` | `number` | `SUM(total_bilhete) WHERE cancelado='N'` |
| `comissao` | `number` | `SUM(comissao_valor)` |
| `total` | `number` | `apurado - comissao` (líquido) |

### `CaixaRestService::resumo` — resposta Zooloo (mobile)

| Campo | Tipo | Descrição |
|---|---|---|
| `data` | string | Data consultada |
| `vendedor_id` | int | ID do vendedor |
| `vendedor_nome` | string | Nome do vendedor |
| `exibe_comissao` | `S`/`N` | Controla exibição no app |
| `qtde_bilhetes` | int | Total de bilhetes do dia |
| `qtde_cancelados` | int | Bilhetes cancelados |
| `total_vendido` | decimal | `SUM(total_bilhete)` |
| `total_cancelado` | decimal | `SUM(total_bilhete WHERE cancelado='S')` |
| `total_liquido` | decimal | `total_vendido - total_cancelado` |
| `total_comissao` | decimal | `SUM(comissao_valor)` |
| `total_premios_pagos` | decimal | `SUM(sorteado_valor_pago WHERE sorteado_pago='S')` |
| `saldo_liquido` | decimal | `total_vendido - total_cancelado - total_premios_pagos` |
| `por_extracao` | array | Breakdown: extracao_descricao, qtde_bilhetes, total_vendido |

> **Nota:** O `CaixaRestService::resumo` inclui `total_premios_pagos` e `saldo_liquido` — campos mais completos que o `IGeralCaixa` Java (que omite prêmios). 🟡

---

## Tela `caixa-vendedor` — Interface Java (referência)

### Filtros

| Filtro | Campo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` | Não | Pré-preenchido com data atual¹ |
| Área | `mov_jb.area_id` | Não | "TODAS" disponível para todos os perfis |
| Vendedor | `mov_jb.vendedor_id` | Não | "TODOS" disponível; lista completa (sem AJAX por área) |

> ¹ Bug de data: `diaAtual()` retrocede para dezembro do ano anterior quando executado em janeiro (`getMonth() === 0`). 🔴

### Colunas

| Coluna | Dado | Notas |
|---|---|---|
| ~~Extração~~ Área | `geralCaixas.area` | **BUG de label:** o cabeçalho da coluna diz "Extração" mas o dado exibido é a área 🔴 |
| Vendedor | `geralCaixas.vendedor` | |
| Apurado | `geralCaixas.apurado` | Moeda BRL |
| Comissão | `geralCaixas.comissao` | Moeda BRL |
| Total | `geralCaixas.total` | Moeda BRL — líquido = apurado − comissão |

**Sem linha de totalização** nesta versão. 🟡

---

## Tela `geral-caixa` — Interface Java (referência)

### Filtros

| Filtro | Campo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` | Não | Pré-preenchido com data atual¹ |
| Data Final | `mov_jb.data_hora <= dataFinal` | Não | Pré-preenchido com `hoje + 1 dia`² |
| Área | `mov_jb.area_id` | Não | "TODAS" disponível |

> ¹ Mesmo bug de janeiro do `caixa-vendedor`. 🔴  
> ² `outroDia()`: dataFinal é definida como `today + 1 dia` para garantir que o dia atual seja incluído no intervalo. 🟡

### Colunas

| Coluna | Dado | Notas |
|---|---|---|
| Área | `geralCaixas.area` | Label correta nesta tela |
| Vendedor | `geralCaixas.vendedor` | |
| Apurado | `geralCaixas.apurado` | Moeda BRL |
| Comissão | `geralCaixas.comissao` | Moeda BRL |
| Total | `geralCaixas.total` | Moeda BRL |

**Sem linha de totalização** nesta versão também. 🟡

---

## Tabelas `mov_caixa` e `mov_caixa_lancamentos`

O `CLAUDE.md` lista `mov_caixa` e `mov_caixa_lancamentos` como tabelas existentes no banco PostgreSQL. Nenhuma das duas tem entidade PHP nem controller no Zooloo. As telas Java de caixa consultam `mov_jb` diretamente (via agregação), sem usar `mov_caixa`. O `CaixaRestService::resumo` do Zooloo também consulta `mov_jb` diretamente.

> **Hipótese:** `mov_caixa` pode ser um diário de caixa de dupla entrada (débitos/créditos) implementado parcialmente no Java mas não utilizado nas telas identificadas. 🔴

---

## Responsabilidades Faltantes no Zooloo

| Responsabilidade | Status |
|---|---|
| Resumo financeiro do próprio vendedor (mobile) | ✅ `CaixaRestService::resumo` |
| Tela gerencial multi-vendedor (gestão diária) | 🔴 Ausente |
| Filtro por área + vendedor + período | 🔴 Ausente |
| Visão consolidada área+vendedor+apurado+comissão+total | 🔴 Ausente |
| Entidades PHP para `mov_caixa` e `mov_caixa_lancamentos` | 🔴 Ausente |

---

## Regras de Negócio

- **RN-CV-01** `apurado = SUM(total_bilhete) WHERE cancelado='N'` — bilhetes cancelados não entram na apuração 🟢
- **RN-CV-02** `total = apurado - comissao` — é o líquido antes de descontar prêmios 🟢
- **RN-CV-03** `CaixaRestService::resumo` inclui prêmios pagos: `saldo_liquido = total_vendido - total_cancelado - total_premios_pagos` 🟢
- **RN-CV-04** `exibe_comissao` no vendedor controla se o app exibe o campo de comissão ao vendedor 🟡
- **RN-CV-05** A tela gerencial não aplica restrição de perfil de gerente — todos os perfis veem combo "TODAS" no Java. Isso deve ser revisado no Zooloo 🟡
- **RN-CV-06** `dataFinal = today + 1` é uma solução de contorno para inclusão do dia atual no intervalo `<` — ao implementar no Zooloo usar `<= dataFinal` em vez de `< dataFinal` para evitar essa gambiarra 🟡

---

## Fluxo Principal (tela gerencial a implementar)

```
1. Gestor acessa CaixaVendedorList
2. Frontend pré-preenche dataInicial=hoje, dataFinal=hoje
3. Frontend carrega combo de áreas e vendedores
4. Gestor ajusta filtros (data, área, vendedor) e clica "Buscar"
5. SELECT área, vendedor, SUM(total_bilhete) AS apurado, SUM(comissao_valor) AS comissao,
         SUM(total_bilhete) - SUM(comissao_valor) AS total
   FROM mov_jb
   JOIN cad_vendedor ON ...
   JOIN cad_area ON ...
   WHERE cancelado='N' AND data_hora BETWEEN dataInicial AND dataFinal
   [AND area_id = X] [AND vendedor_id = Y]
   GROUP BY area, vendedor
   ORDER BY area, vendedor
6. Exibe tabela com totalização no rodapé
```

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_jb` | Fonte: total_bilhete, comissao_valor, cancelado |
| `cad_vendedor` | JOIN para nome do vendedor |
| `cad_area` | JOIN para nome da área |
| `CaixaRestService::resumo` | Implementado — visão do vendedor via mobile |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela gerencial `caixa-vendedor` | Angular presente | Ausente | 🔴 |
| Tela gerencial `geral-caixa` | Angular presente | Ausente | 🔴 |
| API `geralCaixa` (Spring) | Presente | Ausente (equivalente partial via REST) | 🔴 |
| `CaixaRestService::resumo` (mobile) | Ausente no Java | ✅ Implementado | — |
| `mov_caixa` entidade PHP | Ausente | Ausente | 🔴 |
| `mov_caixa_lancamentos` entidade PHP | Ausente | Ausente | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — caixa gerencial por data e área
Dado que existem bilhetes não cancelados para 2026-04-30 nas áreas 1 e 2
Quando gestor filtra dataInicial=2026-04-30, dataFinal=2026-04-30, área=TODAS
Então tabela exibe uma linha por vendedor com área, vendedor, apurado, comissão, total
E linha de totalização exibe somas corretas

# Happy path — filtrar por área específica
Quando gestor seleciona área=1 e busca
Então tabela exibe somente vendedores da área 1

# Happy path — filtrar por vendedor
Quando gestor seleciona vendedor=João e busca
Então tabela exibe somente a linha do vendedor João

# Resultado vazio
Dado que não há bilhetes no período selecionado
Quando gestor busca
Então alerta "Não existe resultado para esta data!"

# Totais corretos
Dado bilhetes: vendedor A apurado=R$500, comissao=R$50
Então total=R$450 (apurado - comissao)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Tela gerencial com filtro data/área/vendedor | Must | Fechamento diário do gestor |
| Colunas: área, vendedor, apurado, comissão, total | Must | Informação mínima para gestão |
| Totalização no rodapé | Should | Visão consolidada da banca |
| Corrigir bug label "Extração" → "Área" | Must | Não reproduzir o bug do Java |
| Corrigir bug data janeiro | Must | Não reproduzir o bug do Java |
| Entidades PHP para `mov_caixa` / `mov_caixa_lancamentos` | Could | Depende de definição de uso |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/caixa-vendedor/caixa-vendedor.component.html` | Java |
| `allsystem/.../webapp/app/entities/caixa-vendedor/caixa-vendedor.component.ts` | Java |
| `allsystem/.../webapp/app/entities/geral-caixa/geral-caixa.component.html` | Java |
| `allsystem/.../webapp/app/entities/geral-caixa/geral-caixa.component.ts` | Java |

**Arquivos existentes no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/service/rest/CaixaRestService.php` | Resumo do vendedor para app móvel ✅ |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/caixa/CaixaVendedorList.php` | Tela gerencial com filtros data/área/vendedor + totalização |
