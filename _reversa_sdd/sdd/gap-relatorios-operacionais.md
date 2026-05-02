# GAP: Relatórios Operacionais — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — telas presentes no Java (allsystem) mas **não implementadas** no Zooloo PHP

---

## Visão Geral

O sistema Java possuía um conjunto completo de relatórios operacionais para gestão financeira da banca. Todos são telas de **consulta/leitura** que cruzam dados das tabelas de movimento (`mov_jb`, `mov_jb_sorteio`, `mov_jb_sort_palpite`) com cadastros (`cad_area`, `cad_vendedor`, `cad_extracao`, `cad_modalidade`). Nenhum deles está implementado no Zooloo.

Este documento cobre 7 telas de relatório agrupadas por categoria funcional:

| Tela Java | Título | Categoria |
|---|---|---|
| `geral-financeiro` | Movimento Geral Caixa | Financeiro |
| `geral-caixajb` | Movimento Geral Financeiro | Financeiro |
| `geral-comissao` | Geral Comissão | Comissão |
| `geral-venda-modalidade` | Mov. Geral Vendas Vendedor | Vendas |
| `geral-vendas-area` | Mov. Geral Vendas Área | Vendas |
| `apuracaojb` | Apuração | Apuração |
| `descarrego-jb` | Descarrego | Controle de Risco |

---

## 1. Movimento Geral Caixa (`geral-financeiro`)

**Título Java:** "Movimento Geral Caixa"

### Filtros

| Filtro | Campo |
|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` |
| Data Final | `mov_jb.data_hora <= dataFinal` |
| Tipo | Combo dinâmico (TODOS + opções configuráveis) |
| Área | `mov_jb.area_id` (gestor pode ver todas; restrito por permissão) |

### Colunas

| Coluna | Cálculo |
|---|---|
| Vendedor | `cad_vendedor.nome` |
| Apurado | `SUM(total_bilhete) WHERE cancelado='N'` |
| Comissão | `SUM(comissao_valor)` |
| Líquido | `apurado - comissao` |
| Prêmio Pago | `SUM(sorteado_valor_pago) WHERE sorteado_pago='S' AND vendedor próprio` |
| P. Pago de Outros | Prêmios pagos de bilhetes de outros vendedores |
| Total de P. Pagos | `prêmio_pago + p_pago_outros` |
| Total | `líquido - total_p_pagos` |

**Linha de totalização:** soma de todas as colunas numéricas.

---

## 2. Movimento Geral Financeiro (`geral-caixajb`)

**Título Java:** "Movimento Geral Financeiro"

### Filtros

| Filtro | Campo |
|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` |
| Data Final | `mov_jb.data_hora <= dataFinal` |
| Tipo | Combo dinâmico |
| Área | `mov_jb.area_id` (TODAS disponível para gestor) |

### Colunas

| Coluna | Cálculo |
|---|---|
| Área | `cad_area.descricao` |
| Vendedor | `cad_vendedor.nome` |
| Apurado | `SUM(total_bilhete) WHERE cancelado='N'` |
| Comissão | `SUM(comissao_valor)` |
| Total | `apurado - comissao` (líquido) |
| Valor Prêmio | `SUM(sorteado_valor)` (prêmio a pagar) |
| Total Geral | `total - valor_premio` |
| Prêmio Pago | `SUM(sorteado_valor_pago) WHERE sorteado_pago='S'` |
| Diferença | `valor_premio - premio_pago` (prêmios ainda não pagos) |

> **Observação:** Este relatório é mais detalhado que o `geral-financeiro` — inclui a coluna `Área` e a distinção entre prêmio a pagar e prêmio já pago. 🟢

---

## 3. Geral Comissão (`geral-comissao`)

**Título Java:** "Geral Comissão"

### Filtros

| Filtro | Campo |
|---|---|
| Data Inicial / Final | Período |
| Área | `mov_jb.area_id` (dependente de permissão) |
| Extração | `mov_sorteio.extracao_id` |
| Vendedor | `mov_jb.vendedor_id` (recarrega ao mudar área) |
| Modalidade | `mov_jb_sorteio.modalidade_id` |

### Dois modos de exibição (botões separados)

**Modo Geral Vendedor** — agrupa por `(vendedor, extracao, modalidade)`:

| Coluna | Cálculo |
|---|---|
| Vendedor | `cad_vendedor.nome` |
| Extração | `cad_extracao.descricao` |
| Modalidade | `cad_modalidade.apresentacao` |
| Total | `SUM(total_sorteio)` |
| Comissão | `SUM(comissao_sorteio)` |
| Líquido | `total - comissao` |

**Modo Geral Área** — agrupa por `(area, extracao, modalidade)`:

| Coluna | Cálculo |
|---|---|
| Área | `cad_area.descricao` |
| Extração | `cad_extracao.descricao` |
| Modalidade | `cad_modalidade.apresentacao` |
| Total | `SUM(total_sorteio)` |
| Comissão | `SUM(comissao_sorteio)` |
| Líquido | `total - comissao` |

---

## 4. Movimento Geral Vendas Vendedor (`geral-venda-modalidade`)

**Título Java:** "Movimento Geral Vendas Vendedor"

### Filtros

| Filtro | Campo |
|---|---|
| Data Inicial / Final | Período |
| Área | `mov_jb.area_id` |
| Modalidade | `mov_jb_sorteio.modalidade_id` |

### Colunas

| Coluna | Cálculo |
|---|---|
| Vendedor | `cad_vendedor.nome` |
| Total | `SUM(total_sorteio)` no período/filtros |

> **Observação:** Tela simples — resume total apostado por vendedor no período, opcionalmente filtrado por modalidade. 🟢

---

## 5. Movimento Geral Vendas Área (`geral-vendas-area`)

**Título Java:** "Movimento Geral Vendas Área"

### Filtros

| Filtro | Campo |
|---|---|
| Data Inicial / Final | Período |
| Modalidade | `mov_jb_sorteio.modalidade_id` (TODAS disponível) |

### Colunas

| Coluna | Cálculo |
|---|---|
| Área | `cad_area.descricao` |
| Total | `SUM(total_sorteio)` no período |

> **Diferença do anterior:** Agrupa por área (não por vendedor) e não tem filtro por área — exibe o comparativo entre todas as áreas. 🟢

---

## 6. Apuração (`apuracaojb`)

**Título Java:** "Apuração"

Exibe o detalhe de apostas realizadas por sorteio — equivalente a uma listagem de `mov_jb_sorteio` com joins.

### Filtros

| Filtro | Campo |
|---|---|
| Data | `mov_sorteio.data_sorteio` (data exata) |
| Área | `mov_jb.area_id` |
| Extração | `mov_sorteio.extracao_id` |

### Colunas

| Coluna | Dado |
|---|---|
| NSU | `mov_jb.jb_id` zero-padded 6 dígitos |
| Poule | `mov_jb.bilhete_numero` zero-padded 6 dígitos |
| Vendedor | `cad_vendedor.nome` |
| Data | `mov_jb.data_hora` (`dd/MM/yyyy HH:mm:ss`) |
| Extração | `cad_extracao.descricao` |
| Palpites | `mov_jb_sorteio.palpites` (CSV) |
| Total | `mov_jb_sorteio.total_sorteio` |

**Linha de totalização:** `SUM(total_sorteio)`

---

## 7. Descarrego (`descarrego-jb`)

**Título Java:** "Descarrego"

Tela operacional crítica — mostra apostas que atingiram o limite de descarga (`cfg_extracao_descarga`) e precisam ser "processadas" (transferidas para outra banca ou recusadas manualmente).

### Filtros

| Filtro | Campo |
|---|---|
| Data | `mov_sorteio.data_sorteio` (obrigatório) |
| Área | `mov_jb.area_id` |
| Extração | `mov_sorteio.extracao_id` (obrigatório) |

### Quatro modos de visualização (botões)

| Botão | Descrição |
|---|---|
| Busca [Detalhada] | Lista apostas que ultrapassaram o limite — por palpite individual |
| [Agrupada] | Mesmo conteúdo, agrupado por número apostado |
| Descarregadas [Detalhada] | Apostas já processadas/descarregadas |
| [Agrupado] | Idem, agrupado |

### Colunas (modo Detalhado)

| Coluna | Dado |
|---|---|
| # | Índice sequencial |
| Data | `data_sorteio` |
| Apostado | Valor que excede o limite |
| Jogo | Modalidade + palpite |
| Ação | Botão "Processar" (individual) |

### Colunas (Descarregadas)

| Coluna | Dado |
|---|---|
| Data | Timestamp do processamento |
| Usuário | Quem processou |
| Apostado | Valor processado |
| Jogo | Modalidade + palpite |

### Ações especiais

| Ação | Descrição |
|---|---|
| Processar (individual) | Abre modal de confirmação para processar apostas do número |
| Processar Todas | Processa todas as apostas exibidas em lote |
| Download PDF | Gera PDF das apostas listadas |

> **Conceito de "Processar":** No contexto do Jogo do Bicho, "descarregar" significa transferir o excesso de apostas em um número para outra banca parceira (operação de risco). O sistema registra o processamento e os dados ficam na listagem de "Descarregadas". 🟡

---

## Regras de Negócio Gerais dos Relatórios

- **RN-RO-01** Usuários do tipo Gerente só visualizam dados de sua área — filtro automático por `area_id` do gerente 🟡
- **RN-RO-02** Usuários admin visualizam todas as áreas — combo de área com opção "TODAS" 🟡
- **RN-RO-03** Todos os relatórios mostram linha de totalização no rodapé da tabela 🟢
- **RN-RO-04** Descarrego requer data E extração selecionados antes de buscar 🟢
- **RN-RO-05** Processamento de descarrego é irreversível — registra data/hora e usuário responsável 🟡
- **RN-RO-06** Geral Comissão tem dois modos mutuamente exclusivos (Vendedor / Área) — exibe apenas um por vez 🟢

---

## Gap Analysis

| Tela Java | Zooloo | Lacuna |
|---|---|---|
| `geral-financeiro` (Movimento Geral Caixa) | Ausente | 🔴 |
| `geral-caixajb` (Movimento Geral Financeiro) | Ausente | 🔴 |
| `geral-comissao` (Geral Comissão) | Ausente | 🔴 |
| `geral-venda-modalidade` (Vendas por Vendedor) | Ausente | 🔴 |
| `geral-vendas-area` (Vendas por Área) | Ausente | 🔴 |
| `apuracaojb` (Apuração) | Ausente | 🔴 |
| `descarrego-jb` (Descarrego) | Ausente | 🔴 |
| `CaixaRestService::resumo` (por dia/vendedor) | ✅ Implementado via API | Parcial |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — Movimento Geral Financeiro
Dado que existem bilhetes para 01/04/2026 a 30/04/2026
Quando gestor filtra por data e área
Então tabela exibe: área, vendedor, apurado, comissão, total, prêmio, total geral, prêmio pago, diferença
E linha de totalização exibe somas corretas

# Happy path — Geral Comissão (modo Vendedor)
Quando gestor filtra por data, área, extração e clica "Geral Vendedor"
Então tabela agrupa por (vendedor, extração, modalidade) com total, comissão, líquido

# Happy path — Geral Comissão (modo Área)
Quando gestor clica "Geral Área"
Então tabela agrupa por (área, extração, modalidade) com total, comissão, líquido

# Happy path — Apuração
Dado que existem apostas para data=30/04/2026, extração=Federal
Quando gestor filtra por data e extração
Então exibe NSU, Poule, Vendedor, Data, Extração, Palpites, Total por linha de sorteio

# Happy path — Descarrego detalhado
Dado que existe aposta cujo volume supera cfg_extracao_descarga.limite_descarga
Quando gestor filtra por data + área + extração e clica "Busca [Detalhada]"
Então exibe lista de apostas com botão "Processar" por linha

# Happy path — Processar descarrego
Quando gestor clica "Processar" para uma aposta
Então abre modal de confirmação
E ao confirmar, registra processamento com data/hora/usuário
E aposta migra para a listagem "Descarregadas"

# Controle de acesso — gerente
Dado que usuário tem perfil de Gerente da área 2
Quando acessa qualquer relatório
Então combo de área exibe apenas a área 2 sem opção "TODAS"
```

---

## Prioridade

| Tela | MoSCoW | Justificativa |
|---|---|---|
| Apuração (listagem de bilhetes por sorteio) | Must | Controle operacional diário |
| Movimento Geral Financeiro | Must | Fechamento financeiro do gestor |
| Movimento Geral Caixa | Must | Visão detalhada apurado/comissão/prêmio/saldo |
| Descarrego (visualizar + processar) | Must | Controle de risco — operação crítica |
| Geral Comissão (vendedor + área) | Must | Pagamento de comissões |
| Vendas por Vendedor | Should | Acompanhamento de performance |
| Vendas por Área | Should | Comparativo entre áreas |
| Download PDF dos relatórios | Should | Auditoria e registro físico |
| Controle de acesso por perfil Gerente | Must | Segurança de dados |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/geral-financeiro/` | Java |
| `allsystem/.../webapp/app/entities/geral-caixajb/` | Java |
| `allsystem/.../webapp/app/entities/geral-comissao/` | Java |
| `allsystem/.../webapp/app/entities/geral-venda-modalidade/` | Java |
| `allsystem/.../webapp/app/entities/geral-vendas-area/` | Java |
| `allsystem/.../webapp/app/entities/apuracaojb/` | Java |
| `allsystem/.../webapp/app/entities/descarrego-jb/` | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Tela |
|---|---|
| `app/control/relatorio/MovimentoGeralFinanceiroList.php` | Caixa geral por vendedor |
| `app/control/relatorio/MovimentoGeralCaixaList.php` | Financeiro detalhado por área/vendedor |
| `app/control/relatorio/GeralComissaoList.php` | Comissão (2 modos: vendedor/área) |
| `app/control/relatorio/VendasVendedorList.php` | Vendas por vendedor |
| `app/control/relatorio/VendasAreaList.php` | Vendas por área |
| `app/control/relatorio/ApuracaoList.php` | Apuração por sorteio |
| `app/control/descarrego/DescarregoList.php` | Descarrego com ação de processar |
