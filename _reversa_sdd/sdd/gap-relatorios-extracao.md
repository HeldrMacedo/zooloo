# GAP: Relatórios por Extração — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — três telas de relatório agrupadas por extração presentes no Java mas **completamente ausentes** no Zooloo PHP.

---

## Visão Geral

Três componentes de relatório distintos mostram movimentos financeiros agrupados por extração:

| Módulo | Título | Extrações exibidas | Colunas | Totalização |
|---|---|---|---|---|
| `geral-extracaojb` | Movimento Geral Extração | Apenas JB (`queryJb()`) | Extração/Vendedor/Apurado/Comissão/Líquido/Prêmio/Total | Sim — 5 colunas (frontend) |
| `geral-extracao` | Geral Extração | Apenas Bilhetinho (`queryBilhetinho()`) | Extração/Vendedor/Apurado/Comissão/Líquido/Prêmio/Total | **Não** |
| `geral-vendas-extracao` | Movimento Geral Vendas Extração | Sem filtro de extração | Extração/Total (2 colunas) | Sim — Total geral (frontend) |

> **Nota importante:** `geral-extracao` usa `extracaoService.queryBilhetinho()` para popular o combo de extração — isto é, este relatório é específico para extrações do **Bilhetinho**, apesar do título genérico "Geral Extração". 🟢

---

## Módulo 1: `geral-extracaojb` — Movimento Geral Extração (JB)

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `date` | Não | Pré-preenchido com hoje |
| Data Final | `date` | Não | Pré-preenchido com hoje |
| Área | `select` | Sim (gerente) | Gerente/setorista: restrito; Admin (`lastName != null`): com "TODAS" |
| Extração | `select` | Não | Populado via `extracaoService.queryJb()` — ordenado por descrição |

> **Controle de acesso por área:** usa `loadArea(coletorUser, currentAccount)` — se o usuário é coletor/setorista, exibe apenas as áreas associadas a ele; se não tem `lastName` (inferido como gerente), exibe área do usuário; se tem `lastName` (admin), exibe todas com opção "TODAS". 🟢

### Colunas e modelo de dados (`IGeralExtracaoJb`)

| Coluna | Campo | Formato |
|---|---|---|
| Extração | `extracao` | string |
| Vendedor | `vendedor` | string |
| Apurado | `apurado` | BRL |
| Comissão | `comissao` | BRL |
| Líquido | `liquido` | BRL |
| Prêmio | `premio` | BRL |
| Total | `total` | BRL |

### Totalização (rodapé)

Calculada no **frontend** somando todos os itens recebidos:

| Campo | Cálculo |
|---|---|
| `totalApurado` | `SUM(apurado)` |
| `totalComissao` | `SUM(comissao)` |
| `totalLiquido` | `SUM(liquido)` |
| `totalPremio` | `SUM(premio)` |
| `total` | `SUM(total)` |

### API

| Endpoint | Parâmetros | Descrição |
|---|---|---|
| `GET api/geralExtracaojb` | `dataInicial`, `dataFinal`, `area?`, `extracao?` | Lista movimentos JB por extração |

### Regras de negócio específicas

- **RN-GEJ-01** Área obrigatória para gerentes — "Selecione uma área!" 🟢
- **RN-GEJ-02** Admin vê todas as áreas com opção "TODAS" 🟢
- **RN-GEJ-03** Totalização calculada no frontend (não pelo backend) 🟢
- **RN-GEJ-04** Resultado vazio → "Não existe resultado para está data!" 🟢
- **RN-GEJ-05** Erro HTTP → "Selecione uma extração!" 🟢

---

## Módulo 2: `geral-extracao` — Geral Extração (Bilhetinho)

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `date` | Não | Pré-preenchido com hoje (com bug de janeiro — ver abaixo) |
| Data Final | `date` | Não | Pré-preenchido com hoje (com bug de janeiro) |
| Área | `select` | Não | Sempre com "TODAS" — sem restrição por perfil |
| Extração | `select` | Não | Populado via `extracaoService.queryBilhetinho()` — extrações Bilhetinho |

> **Bug de `diaAtual()`:** Este módulo mantém o código comentado de correção de janeiro — se executado em janeiro, `dataInicial` e `dataFinal` são definidos como dezembro do ano anterior. Os outros módulos já corrigiram este bug. A corrigir no Zooloo. 🔴

### Colunas (sem totalização)

| Coluna | Campo | Formato |
|---|---|---|
| Extração | `extracao` | string |
| Vendedor | `vendedor` | string |
| Apurado | `apurado` | BRL |
| Comissão | `comissao` | BRL |
| Líquido | `liquido` | BRL |
| Prêmio | `premio` | BRL |
| Total | `total` | BRL |

> **Sem linha de totalização** — diferença fundamental em relação ao `geral-extracaojb`. 🟢

### API

| Endpoint | Parâmetros | Descrição |
|---|---|---|
| `GET api/geralExtracao` | `dataInicial`, `dataFinal`, `area?`, `extracao?` | Lista movimentos Bilhetinho por extração |

### Regras de negócio específicas

- **RN-GE-01** Sem restrição de área por perfil — todas as áreas sempre disponíveis 🟢
- **RN-GE-02** Sem validação de área obrigatória — campo pode ser nulo 🟢
- **RN-GE-03** Sem totalização — apenas listagem dos dados 🟢
- **RN-GE-04** Erro HTTP → "Selecione uma Extração!" 🟢
- **RN-GE-05** Bug de janeiro presente — corrigir no Zooloo 🔴

---

## Módulo 3: `geral-vendas-extracao` — Movimento Geral Vendas Extração

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `date` | Não | Pré-preenchido com hoje |
| Data Final | `date` | Não | Pré-preenchido com hoje |
| Área | `select` | Sim (gerente) | Gerente/setorista: restrito; Admin: com "TODAS"; `onChange` dispara AJAX de vendedores |
| Vendedor | `select` | Não | "TODOS"; recarregado via AJAX ao mudar área |

> **Sem filtro de extração:** ao contrário dos outros dois módulos, não há dropdown de extração — o resultado mostra todas as extrações do período com seus totais. 🟢

### Colunas e modelo (`IGeralVendasExtracao`)

| Coluna | Campo | Formato |
|---|---|---|
| Extração | `descricao` | string — nome da extração |
| Total | `total` | BRL |

### Totalização

| Campo | Cálculo |
|---|---|
| `totalVenda` | `SUM(total)` de todas as extrações — calculado no frontend |

### API

| Endpoint | Parâmetros | Descrição |
|---|---|---|
| `GET api/geralvendasextracao` | `dataInicial`, `dataFinal`, `area?`, `vendedor?` | Vendas agrupadas por extração no período |

### Regras de negócio específicas

- **RN-GVE-01** Área obrigatória para gerentes — "Selecione uma área!" 🟢
- **RN-GVE-02** Admin vê todas as áreas com opção "TODAS" 🟢
- **RN-GVE-03** AJAX `areaVendedor(area_id)` recarrega combo vendedor ao mudar área 🟢
- **RN-GVE-04** Totalização calculada no frontend 🟢
- **RN-GVE-05** Resultado vazio → "Não existe resultado para está data!" 🟢

---

## Comparação entre os três módulos

| Aspecto | geral-extracaojb | geral-extracao | geral-vendas-extracao |
|---|---|---|---|
| Título | Movimento Geral Extração | Geral Extração | Movimento Geral Vendas Extração |
| Tipo de extração | JB | Bilhetinho | Todos (sem filtro) |
| Filtro Extração | Sim | Sim | **Não** |
| Filtro Vendedor | Não | Não | Sim (com AJAX) |
| Restrição área por perfil | Sim (coletor/setorista) | **Não** | Sim (coletor/setorista) |
| Colunas de resultado | 7 (Ext/Vend/Apr/Com/Liq/Prê/Tot) | 7 (mesmas) | 2 (Extração/Total) |
| Totalização no rodapé | Sim (5 colunas) | **Não** | Sim (1 valor) |
| Bug de janeiro em diaAtual() | Não (corrigido) | **Sim** (bug presente) 🔴 | Não (corrigido) |
| API endpoint | `api/geralExtracaojb` | `api/geralExtracao` | `api/geralvendasextracao` |

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_jb_sorteio` + `mov_jb` | Fonte dos dados para `geral-extracaojb` |
| `mov_bilhetinho_sorteio` | Fonte dos dados para `geral-extracao` (Bilhetinho) |
| `cad_extracao` + `int_jogo` | Filtro e agrupamento por extração |
| `cad_area` | Filtro por área |
| `cad_vendedor` | Filtro por vendedor (`geral-vendas-extracao`) |
| `cad_coletor` (`ColetorService`) | Restrição de área para coletores/setoristas |
| `SetoristaAreaService` | Áreas visíveis ao setorista |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela Movimento Geral Extração (JB) | Angular presente | Ausente | 🔴 |
| API `geralExtracaojb` | Presente | Ausente | 🔴 |
| Tela Geral Extração (Bilhetinho) | Angular presente | Ausente | 🔴 |
| API `geralExtracao` | Presente | Ausente | 🔴 |
| Tela Movimento Geral Vendas Extração | Angular presente | Ausente | 🔴 |
| API `geralvendasextracao` | Presente | Ausente | 🔴 |
| Totalização frontend (geral-extracaojb) | Presente | Ausente | 🔴 |
| AJAX área→vendedor (geral-vendas-extracao) | Presente | Ausente | 🔴 |
| Restrição de área por perfil | Presente | Ausente | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Movimento Geral Extração — happy path
Dado que existem apostas JB em 2026-04-30 na Extração "TARDE" área 1
Quando gestor filtra data=2026-04-30, área=1, extração=TARDE
Então tabela exibe linhas com: extração, vendedor, apurado, comissão, líquido, prêmio, total
E rodapé exibe somas de todas as 5 colunas financeiras

# Movimento Geral Extração — área obrigatória gerente
Dado que usuário é gerente (não admin)
Quando clica Buscar sem selecionar área
Então alerta "Selecione uma área!"

# Geral Extração — sem totalização
Dado que existem apostas Bilhetinho no período
Quando gestor busca
Então tabela exibe 7 colunas mas sem linha de Total no rodapé

# Geral Extração — bug de janeiro (a corrigir)
Dado que é 1º de janeiro de 2027
Quando gestor abre Geral Extração
Então datas iniciais devem ser preenchidas com 2027-01-01 (não 2026-12-01)

# Movimento Geral Vendas Extração — AJAX
Dado que área 1 tem 3 vendedores e área 2 tem 5 vendedores
Quando gestor altera combo área de 1 para 2
Então combo vendedor recarrega com os 5 vendedores da área 2

# Movimento Geral Vendas Extração — 2 colunas
Quando gestor busca período
Então tabela exibe apenas: "Extração" e "Total" por linha
E rodapé exibe total geral de todas as extrações
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| `GeralExtracaoJbList.php` — Movimento Geral Extração JB | Must | Relatório gerencial principal por extração |
| API `geralExtracaojb` | Must | Backend necessário |
| `GeralExtracaoList.php` — Geral Extração Bilhetinho | Should | Complementar ao Bilhetinho |
| `GeralVendasExtracaoList.php` — Movimento Vendas Extração | Should | Visão simplificada de vendas |
| Totalização frontend (geral-extracaojb) | Must | Parte do contrato funcional |
| AJAX área→vendedor (geral-vendas-extracao) | Should | UX consistente com outros relatórios |
| Restrição de área por perfil | Must | Segurança de dados por área |
| Corrigir bug de janeiro | Must | Evitar data errada em janeiro |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Módulo | Sistema |
|---|---|---|
| `allsystem/.../geral-extracaojb/geral-extracaojb.component.html` | geral-extracaojb | Lista com 7 colunas + totalização |
| `allsystem/.../geral-extracaojb/geral-extracaojb.component.ts` | geral-extracaojb | `loadArea()` coletor/setorista, `diaAtual()` correto |
| `allsystem/.../geral-extracaojb/geral-extracaojb.service.ts` | geral-extracaojb | `GET api/geralExtracaojb` |
| `allsystem/.../geral-extracao/geral-extracao.component.html` | geral-extracao | Lista com 7 colunas sem totalização |
| `allsystem/.../geral-extracao/geral-extracao.component.ts` | geral-extracao | `queryBilhetinho()`, `diaAtual()` com bug de janeiro |
| `allsystem/.../geral-extracao/geral-extracao.service.ts` | geral-extracao | `GET api/geralExtracao` |
| `allsystem/.../geral-vendas-extracao/geral-vendas-extracao.component.html` | geral-vendas-extracao | Lista com 2 colunas + total |
| `allsystem/.../geral-vendas-extracao/geral-vendas-extracao.component.ts` | geral-vendas-extracao | `areaVendedor()` AJAX, `loadArea()` coletor/setorista |
| `allsystem/.../geral-vendas-extracao/geral-vendas-extracao.service.ts` | geral-vendas-extracao | `GET api/geralvendasextracao` |

**Arquivos a criar no Zooloo:**

| Arquivo | Módulo | Descrição |
|---|---|---|
| `app/control/relatorio/GeralExtracaoJbList.php` | geral-extracaojb | 7 colunas + totalização, restrito por perfil |
| `app/control/relatorio/GeralExtracaoList.php` | geral-extracao | 7 colunas sem total, filtro Bilhetinho, TODAS as áreas |
| `app/control/relatorio/GeralVendasExtracaoList.php` | geral-vendas-extracao | 2 colunas, AJAX área→vendedor, restrito por perfil |
