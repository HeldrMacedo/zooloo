# GAP: Relatórios de Vendas — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — três telas de consulta e relatório de vendas presentes no Java mas **completamente ausentes** no Zooloo PHP.

---

## Visão Geral

Três componentes de relatório de vendas com propósitos distintos:

| Módulo | Título | Foco | Filtros |
|---|---|---|---|
| `vendasjb` | Consulta de Vendas | Bilhetes individuais com NSU | Data/Tipo/Área/Extração/Vendedor/Situação/NSU |
| `vendas-por-sorteio` | Vendas por Sorteio (Aberto) | Sorteios abertos com totais | Nenhum — auto-carrega |
| `geral-financeiro` | Movimento Geral Caixa | Caixa por vendedor no período | Data/Tipo/Área |

---

## Módulo 1: `vendasjb` — Consulta de Vendas

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data | `date` | Não | Pré-preenchido com hoje (`datainicio`) |
| Data Final | `date` | Não | Pré-preenchido com hoje (`datafim`) |
| Tipo | `select` | Não | Combo gerado dinamicamente a partir de `cfg_parametros` — exibe somente os jogos ativos (JOGOS, QUININHA, SENINHA, BILHETINHO); oculto se só houver 1 tipo ativo |
| Área | `select` | Sim (gerente) | Coletor/setorista: restrito; Admin: com "TODAS" |
| Extração | `select` | Não | "TODAS"; populado via `extracaoService.queryJb()` |
| Vendedor | `select` | Não | "TODOS"; AJAX ao mudar área (`areaVendedor()`) |
| Situação | `select` | Não | TODOS / ATIVO / CANCELADO |
| Vendas a partir de | `number` | Não | Filtro por valor mínimo de `total_sorteio` |
| NSU | `number` | Não | **Modo exclusivo:** ao digitar, desabilita todos outros campos e troca botão Buscar por "Buscar Nsu" |

> **Combo Tipo — lógica dinâmica:** `loadParametro()` lê `cfg_parametros` e monta o combo somente com os jogos cujos flags estão ativos (`ativo_jb`, `ativo_quininha`, `ativo_seninha`, `ativo_bilhetinho`). Se apenas 1 jogo está ativo, o combo é ocultado (`desativarCombo = true`) e aquele ID é passado automaticamente ao filtro. 🟢

> **Modo NSU vs Modo Normal:** `desativarCampos()` monitoriza o campo NSU. Ao digitar qualquer valor: todos os outros inputs são desabilitados, o botão Buscar some, o botão "Buscar Nsu" aparece. Ao apagar o NSU: retorna ao modo normal. 🟢

### Colunas

| Coluna | Campo | Notas |
|---|---|---|
| NSU | `nsu` | Link para detalhe via popup `vendasjb/{nsu}/view`; formatado com 6 dígitos com zeros à esquerda |
| Data | `data_hora` | Formato `dd/MM/yyyy HH:mm:ss` |
| Vendedor | `vendedor` | Nome |
| Modalidade | `apresentacao` | Nome da modalidade da aposta |
| Situação | `situacao` | ATIVO / CANCELADO |
| Extração | `extracao` | Nome da extração |
| Comissão | `comissao_sorteio` | BRL |
| Total Sorteio | `total_sorteio` | BRL |
| Previsão Prêmio | `previsao_premio` | BRL |

### Totalização (rodapé)

Calculada no frontend, excluindo linhas com `situacao === 'CANCELADO'`:

| Campo | Cálculo |
|---|---|
| `comissaoTotal` | `SUM(comissao_sorteio)` onde não cancelado |
| `total` | `SUM(total_sorteio)` onde não cancelado |
| `previsaoPremio` | `SUM(previsao_premio)` onde não cancelado |

### APIs

| Endpoint | Uso |
|---|---|
| `GET api/rel-vendasjb` | Lista de vendas com filtros normais |
| `GET api/vendasnsu/{nsu}` | Busca por NSU específico |
| `GET api/vendas-detalhe/{nsu}` | Detalhes do bilhete (popup) |
| `PUT api/vendasjb-cancelamento/{nsu}` | Cancelar bilhete |

### Regras de negócio

- **RN-VJB-01** Modo NSU é mutuamente exclusivo com o modo filtros — ao usar NSU, demais campos são desabilitados 🟢
- **RN-VJB-02** Cancelados são excluídos da totalização, mas aparecem na lista com situacao='CANCELADO' 🟢
- **RN-VJB-03** Combo Tipo só exibe jogos habilitados em `cfg_parametros` — oculto se único jogo ativo 🟢
- **RN-VJB-04** Área obrigatória para gerentes/coletores 🟢
- **RN-VJB-05** AJAX recarrega vendedores ao mudar área 🟢
- **RN-VJB-06** NSU exibido com 6 dígitos com zeros (`"000000" + nsu`).slice(-6)` 🟢

---

## Módulo 2: `vendas-por-sorteio` — Vendas por Sorteio (Aberto)

### Comportamento

Carrega automaticamente ao acessar a tela — sem filtros de usuário. Lista todos os sorteios (abertos e fechados recentes) com seus totais financeiros.

O frontend avalia cada linha e marca como "EXPIRADO" se `hora_sorteio < agora`, aplicando classe CSS `disabled` ao link do NSU sorteio.

### Colunas

| Coluna | Campo | Notas |
|---|---|---|
| Número do Sorteio | `sorteio_numero` | Link popup `vendasorteio/{sorteio_numero}/view`; formatado 6 dígitos |
| Extração | `descricao` | Nome da extração |
| Data | `data_sorteio` | Formato `dd/MM/yyyy` |
| Hora | `hora` | Hora limite do sorteio |
| Status | (calculado) | "EXPIRADO" se `hora_sorteio < agora`, "" caso contrário |
| Total Sorteio | `total_sorteio` | BRL |
| Comissão | `comissao` | BRL |
| Líquido | `liquido` | BRL |

> **Status calculado no frontend:** não vem da API — é calculado comparando `data_sorteio + hora` com `new Date()`. Linhas expiradas recebem classe CSS `disabled`. 🟢

> **Registro de evento:** `eventManager.subscribe('vendaSorteioListModification')` — a lista recarrega ao ser notificada (ex: após registrar resultado). 🟢

### API

| Endpoint | Descrição |
|---|---|
| `GET api/vendasorteio` | Lista todos os sorteios com totais (sem filtros) 🟡 |

### Regras de negócio

- **RN-VPS-01** Sem filtros de usuário — exibe todos os sorteios visíveis para o perfil logado 🟢
- **RN-VPS-02** Status "EXPIRADO" calculado no frontend: `hora_sorteio < new Date()` 🟢
- **RN-VPS-03** Link do sorteio abre popup com detalhes de vendas por sorteio 🟢
- **RN-VPS-04** Recarrega automaticamente ao evento `vendaSorteioListModification` 🟢

---

## Módulo 3: `geral-financeiro` — Movimento Geral Caixa

> **Nota de nomenclatura:** O módulo chama-se `geral-financeiro` e o arquivo de serviço usa `api/getalfinanceirojb` (typo: "getal"), mas o título da tela é "Movimento Geral Caixa". No Zooloo: corrigir para `api/geralfinanceirojb`. 🔴

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `date` | Não | Pré-preenchido com hoje |
| Data Final | `date` | Não | Pré-preenchido com hoje |
| Tipo | `select` | Não | Mesmo combo dinâmico de `cfg_parametros` — oculto se único jogo ativo |
| Área | `select` | Sim (gerente) | Coletor/setorista: restrito; Admin: com "TODAS" |

### Colunas e modelo (`IGeralFinanceiro`)

| Coluna | Campo | Cálculo | Formato |
|---|---|---|---|
| Vendedor | `vendedorDescricao` | — | string |
| Apurado | `apuracao` | total das vendas | BRL |
| Comissão | `comissao` | — | BRL |
| Líquido | `liquido` | apurado - comissão | BRL |
| Prêmio Pago | `premioPagos` | prêmios pagos ao próprio vendedor | BRL |
| P. Pago de Outros | `premioPagosTerceiros` | prêmios pagos de outros vendedores | BRL |
| Total de P. Pagos | (calculado) | `premioPagos + premioPagosTerceiros` | BRL — calculado no template |
| Total | (calculado) | `liquido - (premioPagos + premioPagosTerceiros)` | BRL — calculado no template |

### Totalização (rodapé)

Calculada no frontend somando todos os registros:

| Coluna | Variável |
|---|---|
| Apurado | `totalApurado` |
| Comissão | `totalComissao` |
| Líquido | `totalLiquido` |
| Prêmio Pago | `totalPremioPago` |
| P. Pago Outros | `totalPremioPagoTerceiro` |
| Total P. Pagos | `totalPremioPago + totalPremioPagoTerceiro` |
| Total | `totalLiquido - (totalPremioPago + totalPremioPagoTerceiro)` |

### API

| Endpoint | Descrição |
|---|---|
| `GET api/getalfinanceirojb` | Caixa por vendedor (typo no endpoint — corrigir para `geralfinanceirojb`) 🔴 |

### Regras de negócio

- **RN-GF-01** Área obrigatória para gerentes — "Selecione uma área!" 🟢
- **RN-GF-02** Admin vê todas as áreas com "TODAS" 🟢
- **RN-GF-03** Combo Tipo dinâmico idêntico ao de `vendasjb` 🟢
- **RN-GF-04** "Total de P. Pagos" e "Total" calculados no template — não vêm da API 🟢
- **RN-GF-05** API usa typo `getalfinanceirojb` — corrigir para `geralfinanceirojb` no Zooloo 🔴
- **RN-GF-06** Resultado vazio → "Não existe resultado para está data!" 🟢

---

## Comparação entre os três módulos

| Aspecto | vendasjb | vendas-por-sorteio | geral-financeiro |
|---|---|---|---|
| Nível de detalhe | Bilhete individual | Sorteio consolidado | Vendedor consolidado |
| Filtros | 8 filtros + NSU mode | **Nenhum** | 3 filtros |
| Combo Tipo (cfg_parametros) | Sim | **Não** | Sim |
| AJAX área→vendedor | Sim | **Não** | **Não** |
| Totalização rodapé | 3 colunas (excl. cancelados) | **Não** | 8 colunas |
| Status calculado frontend | Não | Sim ("EXPIRADO") | Não |
| Cancelamento de bilhete | Sim (via detalhe popup) | **Não** | **Não** |
| API endpoint | `api/rel-vendasjb` | `api/vendasorteio` | `api/getalfinanceirojb` (typo) |

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_jb_sorteio` + `mov_jb` | Fonte dos dados de vendasjb e geral-financeiro |
| `mov_sorteio` | Fonte dos dados de vendas-por-sorteio |
| `cad_area`, `cad_vendedor` | Filtros e agrupamentos |
| `cad_extracao` | Filtro de extração |
| `cfg_parametros` | Feature flags de jogos ativos para combo Tipo |
| `cad_coletor`, `SetoristaArea` | Restrição de áreas por perfil |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela Consulta de Vendas | Angular presente | Ausente | 🔴 |
| API `rel-vendasjb` + `vendasnsu` | Presente | Ausente | 🔴 |
| Modo NSU (desabilita filtros) | Presente | Ausente | 🔴 |
| Combo Tipo por cfg_parametros | Presente | Ausente | 🔴 |
| Tela Vendas por Sorteio | Angular presente | Ausente | 🔴 |
| API `vendasorteio` | Presente | Ausente | 🔴 |
| Status "EXPIRADO" calculado no frontend | Presente | Ausente | 🔴 |
| Tela Movimento Geral Caixa | Angular presente | Ausente | 🔴 |
| API `getalfinanceirojb` (typo a corrigir) | Presente | Ausente | 🔴 |
| "Total P. Pagos" e "Total" calculados no template | Presente | Ausente | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Consulta de Vendas — modo normal
Dado que existem vendas em 2026-05-01 na área 1
Quando gestor filtra data=2026-05-01, área=1, situação=ATIVO
Então lista exibe bilhetes com NSU, data, vendedor, modalidade, extração, comissão, total, previsão
E rodapé totaliza excluindo cancelados

# Consulta de Vendas — modo NSU
Quando gestor digita NSU=123456 no campo NSU
Então todos os outros filtros ficam desabilitados
E botão "Buscar" some; botão "Buscar Nsu" aparece
Quando clica "Buscar Nsu"
Então lista exibe apenas o bilhete com NSU 123456

# Combo Tipo — jogo único
Dado que apenas ativo_jb=true em cfg_parametros
Quando gestor acessa Consulta de Vendas
Então combo Tipo está oculto e ID=1 (JOGOS) é passado automaticamente

# Vendas por Sorteio — status EXPIRADO
Dado que sorteio TARDE tem hora=14:00 e agora são 15:00
Quando gestor acessa Vendas por Sorteio
Então linha do sorteio TARDE exibe "EXPIRADO" com classe CSS disabled

# Movimento Geral Caixa — colunas calculadas
Dado que vendedor tem premioPagos=100 e premioPagosTerceiros=50
Quando resultado é exibido
Então "Total de P. Pagos" exibe R$ 150,00
E "Total" exibe liquido - R$ 150,00
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| `ConsultaVendasList.php` (vendasjb) | Must | Consulta operacional diária de bilhetes |
| Modo NSU | Must | Suporte a busca de bilhete específico |
| Combo Tipo dinâmico | Must | Necessário para bancas com múltiplos jogos |
| `VendasPorSorteioList.php` | Should | Visão gerencial de sorteios abertos |
| Status EXPIRADO no frontend | Should | Indicação visual de sorteios vencidos |
| `GeralFinanceiroList.php` | Must | Caixa por vendedor — relatório gerencial crítico |
| Corrigir typo `getalfinanceirojb` → `geralfinanceirojb` | Must | Consistência de nomenclatura |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Módulo | Sistema |
|---|---|---|
| `allsystem/.../vendasjb/vendasjb.component.html` | vendasjb | 8 filtros + NSU mode |
| `allsystem/.../vendasjb/vendasjb.component.ts` | vendasjb | `desativarCampos()`, `loadParametro()`, `somarTotal()` |
| `allsystem/.../vendasjb/vendasjb.service.ts` | vendasjb | `api/rel-vendasjb`, `api/vendasnsu` |
| `allsystem/.../vendas-por-sorteio/vendas-por-sorteio.component.html` | vendas-por-sorteio | Status calculado |
| `allsystem/.../vendas-por-sorteio/vendas-por-sorteio.component.ts` | vendas-por-sorteio | `loadAll()` auto-init, status EXPIRADO |
| `allsystem/.../geral-financeiro/geral-financeiro.component.html` | geral-financeiro | 8 colunas, 2 calculadas no template |
| `allsystem/.../geral-financeiro/geral-financeiro.component.ts` | geral-financeiro | `loadParametro()`, totalização |
| `allsystem/.../geral-financeiro/geral-financeiro.service.ts` | geral-financeiro | `api/getalfinanceirojb` (typo) |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/relatorio/ConsultaVendasList.php` | Consulta de bilhetes com 8 filtros, modo NSU, combo Tipo |
| `app/control/relatorio/VendasPorSorteioList.php` | Sorteios com totais, status EXPIRADO no frontend |
| `app/control/relatorio/GeralFinanceiroList.php` | Caixa por vendedor, 8 colunas com 2 calculadas |
