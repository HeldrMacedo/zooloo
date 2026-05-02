# GAP: Módulos Administrativos e Operacionais — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — seis módulos operacionais presentes no Java mas **completamente ausentes** no Zooloo PHP.

---

## Visão Geral

Este documento cobre os módulos administrativos e operacionais de alta complexidade que ainda não possuem equivalente no Zooloo:

| Módulo | Título na tela | Função principal |
|---|---|---|
| `descarrego-jb` | Descarrego | Gerenciar apostas acima do limite de descarga (JB) |
| `geral-caixajb` | Movimento Geral Financeiro | Relatório financeiro completo por área/vendedor |
| `geral-comissao` | Geral Comissão | Relatório de comissão por vendedor ou por área |
| `apuracao` | Apuração | Listagem de bilhetes Bilhetinho por data/área/extração |
| `apuracaojb` | Apuração | Listagem de bilhetes JB por data/área/extração |
| `register-log` | — | Auditoria de ações — somente service, sem UI dedicada |

---

## Módulo 1: `descarrego-jb` — Descarrego

### Visão Geral

Gerencia apostas cujo volume ultrapassa o limite configurado em `cfg_extracao_descarga`. O operador visualiza as apostas pendentes de processamento ("descarrego") e pode processá-las individualmente via modal ou em lote ("Processar Todas").

O módulo tem **quatro modos de visualização** controlados por flags booleanas — apenas uma tabela é exibida por vez:

| Flag | Ativo quando | Botão que ativa |
|---|---|---|
| `validDescarrego` | Listagem detalhada pendente | "Busca Detalhada" |
| `validDescarregoAgrupado` | Listagem agrupada pendente | "Agrupada" |
| `ValidDescarregado` | Descarregados processados (detalhado) | "Descarregadas Detalhada" |
| `ValidDescarregadoAgrupado` | Descarregados processados (agrupado) | "Descarregados Agrupado" |

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data | `date` | Não | Pré-preenchido com hoje (bug de janeiro **corrigido**) |
| Área | `select` | Sim (quando não admin) | `loadArea()` — coletor/setorista restrito; admin vê todas |
| Extração | `select` | Não | Populado via `extracaoService.queryJb()` — somente JB |

> **Sem filtro de Bilhetinho:** o Descarrego JB usa apenas extrações JB. 🟢

### APIs

| Método | Endpoint | Parâmetros | Descrição |
|---|---|---|---|
| `GET` | `api/descarregojb` | `data, area?, extracao?` | Listagem detalhada pendente |
| `GET` | `api/descarregoagrupado` | `data, area?, extracao?` | Listagem agrupada pendente |
| `GET` | `api/descarregados` | `data, area?, extracao?` | Processados — detalhado |
| `GET` | `api/descarregadosagrupado` | `data, area?, extracao?` | Processados — agrupado |
| `PUT` | `api/descarregojb/{userId}/{userName}` | body: `IDescarrego` | Processar individual (modal) |
| `PUT` | `api/descarregoagrupado/{userId}/{userName}` | body: `IDescarrego` | Processar individual agrupado |
| `PUT` | `api/descarregarTodas/{userId}/{userName}` | body: `{data_sorteio, extracao}` | Processar todas em lote |
| `PUT` | `api/descarregartodasagrupado/{userId}/{userName}` | body: `{data_sorteio, extracao}` | Processar todas agrupado em lote |

### Tabelas de dados

#### Tabela "Pendentes" (detalhado e agrupado)

| Coluna | Campo | Notas |
|---|---|---|
| # | índice sequencial | |
| Data | `data_sorteio` | |
| Apostado | `apostado` | BRL |
| Jogo | `jogou` | número apostado |
| Ações | botão "Processar" | abre modal — somente se `situacao === 'A'` |

**Rodapé:** `totalGeral` = `SUM(apostado)` de todas as linhas — calculado no frontend.

#### Tabela "Processados" (detalhado e agrupado)

| Coluna | Campo | Notas |
|---|---|---|
| Data | `data_sorteio` | |
| Usuário | usuário que processou | |
| Apostado | `apostado` | BRL |
| Jogo | `jogou` | |

> **Sem botão Processar** nos descarregados — somente visualização. 🟢

### Contador regressivo

Após buscar, um contador em `#ContentPlaceHolderMaster_demo` exibe o tempo restante até `hora_limite` da extração selecionada no formato `"Xh Ym Zs para encerrar"`. Quando o tempo expira ou a data da pesquisa não é hoje, o elemento fica em branco. 🟢

### Botões contextuais

| Botão | Visível quando | Ação |
|---|---|---|
| Busca Detalhada | Sempre | Carrega modo `validDescarrego` |
| Agrupada | Sempre | Carrega modo `validDescarregoAgrupado` |
| Descarregadas Detalhada | Sempre | Carrega modo `ValidDescarregado` |
| Descarregados Agrupado | Sempre | Carrega modo `ValidDescarregadoAgrupado` |
| Processar Todas | `btnProcessarTodas = true` | PUT lote detalhado + register_log |
| Processar Todas (Agrupado) | `btnProcessarTodasAgrupado = true` | PUT lote agrupado + register_log |
| Download PDF | `btnDownload = true` | Gera PDF com jsPDF: 3 colunas (#, Jogo, Valor) + cabeçalho `nomeBanca` |
| Download PDF (Agrupado) | `btnDownloadAgrupado = true` | PDF da visão agrupada |

> **Lógica de botões de situação:** `btnProcessar/btnProcessarTodas/btnDownload` são habilitados apenas se **a última linha** iterada tiver `situacao === 'A'` — há um bug de iteração que sobrescreve o estado a cada linha, portanto o estado final reflete apenas a última linha da lista. 🔴

### Auditoria register_log

| Ação | `acao` | `historico` |
|---|---|---|
| Processar individual (modal) | `'Descarrego'` | `'Processar'` |
| Processar todas (detalhado) | `'Descarrego'` | `'Processar Todas'` |
| Processar todas (agrupado) | `'Descarrego'` | `'Processar Todas Agrupado'` |

> **IP incluído** em todos os logs — coletado via `http://api.ipify.org/?format=json`. 🟢

### Regras de Negócio

- **RN-DJ-01** Área obrigatória para gerentes — "Selecione uma área!" 🟢
- **RN-DJ-02** Admin (lastName != null) vê todas as áreas sem restrição 🟢
- **RN-DJ-03** Apenas extrações JB disponíveis no filtro de Extração 🟢
- **RN-DJ-04** Erro HTTP → "Selecione uma extração!" 🟢
- **RN-DJ-05** Contador regressivo exibido apenas quando `data pesquisa == data atual` 🟢
- **RN-DJ-06** Processamento individual via modal NgbModal; agrupado via modal separado (`DescarregoAgrupadoModalComponent`) 🟢
- **RN-DJ-07** Typo no alerta de sucesso: "**Porcessado** com sucesso!" — corrigir no Zooloo para "Processado" 🔴
- **RN-DJ-08** Bug de lógica: habilitação de btnProcessar baseada apenas na última linha iterada 🔴

---

## Módulo 2: `geral-caixajb` — Movimento Geral Financeiro

### Visão Geral

Relatório financeiro consolidado por área e vendedor. Apesar do nome do módulo `geral-caixajb`, o título da tela é **"Movimento Geral Financeiro"** — diferente do `geral-financeiro` (item 30) que exibe "Movimento Geral Caixa". São dois relatórios distintos. 🟢

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `date` | Não | Pré-preenchido com hoje |
| Data Final | `date` | Não | Pré-preenchido com hoje |
| Tipo | `select` | Não | `loadParametro()` — oculto se apenas 1 jogo ativo |
| Área | `select` | Sim (gerente) | `loadArea()` — coletor/setorista restrito; admin vê "TODAS" |

### Colunas e modelo (`IGeralCaixaJb`)

| Coluna | Campo | Formato | Fonte |
|---|---|---|---|
| Área | `area` | string | backend |
| Vendedor | `vendedor` | string | backend |
| Apurado | `apurado` | BRL | backend |
| Comissão | `comissao` | BRL | backend |
| Total | `total` | BRL | backend |
| Valor Prêmio | `premio` | BRL | backend |
| Total Geral | — | BRL | template: `total - premio` |
| Prêmio Pago | `premioPago` | BRL | backend |
| Diferença | — | BRL | template: `premio - premioPago` |

> **2 colunas calculadas no template:** "Total Geral" e "Diferença" não vêm do backend — são computadas inline no HTML. 🟢

### Totalização (rodapé)

| Campo | Cálculo | Fonte |
|---|---|---|
| `totalApurado` | `SUM(apurado)` | frontend |
| `totalComissao` | `SUM(comissao)` | frontend |
| `total` | `SUM(total)` | frontend |
| `premio` | `SUM(premio)` | frontend |
| `totalGeral` | `SUM(totalGeral)` (somado do backend) | frontend |
| `premioPago` | `SUM(premioPago)` | frontend |
| Total Geral (rodapé) | `total - premio` | template |
| Diferença (rodapé) | `premio - premioPago` | template |

### API

| Endpoint | Parâmetros | Descrição |
|---|---|---|
| `GET api/geralCaixajb` | `dataInicial, dataFinal, area?, combo?` | Financeiro por área/vendedor no período |

> **Nota:** existe também `GET api/geralpremio` no service mas está comentado — não utilizado. 🟡
> **Typo de comparação:** `geral-financeiro` (item 30) usa `api/getalfinanceirojb` (typo). Este módulo usa `api/geralCaixajb` (correto, mas camelCase no 'C'). 🟢

### Regras de Negócio

- **RN-GCJ-01** Área obrigatória para gerentes — "Selecione uma área!" 🟢
- **RN-GCJ-02** Admin vê todas as áreas com opção "TODAS" 🟢
- **RN-GCJ-03** Combo Tipo usa `loadParametro()` — oculto (`desativarCombo=true`) se somente 1 jogo ativo 🟢
- **RN-GCJ-04** Quando `objComboBox.length === 1`, o combo fixo é enviado automaticamente no filtro sem exibir o campo 🟢
- **RN-GCJ-05** Resultado vazio → "Não existe resultado para está data!" 🟢
- **RN-GCJ-06** Colunas "Total Geral" e "Diferença" calculadas no template — não retornadas pelo backend 🟢

---

## Módulo 3: `geral-comissao` — Geral Comissão

### Visão Geral

Relatório de comissão de vendas com dois modos distintos de agrupamento, ativados por botões separados:

| Botão | Modo | API |
|---|---|---|
| "Geral Vendedor" | Agrupa por vendedor | `GET api/geralvendedor` |
| "Geral Area" | Agrupa por área | `GET api/geralarea` |

Apenas um modo é exibido por vez (flags `vendedorValid` / `areaValid`).

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `date` | Não | Pré-preenchido com hoje |
| Data Final | `date` | Não | Pré-preenchido com hoje |
| Área | `select` | Sim (gerente) | `loadAreaColetor()` — coletor/setorista restrito; admin vê "TODAS"; `onChange` → `areaVendedor()` AJAX |
| Extração | `select` | Não | "TODAS" + `queryJb()` |
| Vendedor | `select` | Não | "TODOS"; recarrega via AJAX ao mudar área |
| Modalidade | `select` | Não | "TODAS"; carregada por `filtro_banca` do usuário |

### Colunas das tabelas

#### Tabela "Geral Vendedor" (`IGeralVendedor`)

| Coluna | Campo | Formato | Fonte |
|---|---|---|---|
| Vendedor | `vendedor` | string | backend |
| Extração | `extracao` | string | backend |
| Modalidade | `modalidade` | string | backend |
| Total | `total` | BRL | backend |
| Comissão | `comissao` | BRL | backend |
| Líquido | — | BRL | template: `total - comissao` |

#### Tabela "Geral Area" (`IGeralArea`)

| Coluna | Campo | Formato | Fonte |
|---|---|---|---|
| Área | `area` | string | backend |
| Extração | `extracao` | string | backend |
| Modalidade | `modalidade` | string | backend |
| Total | `total` | BRL | backend |
| Comissão | `comissao` | BRL | backend |
| Líquido | — | BRL | template: `total - comissao` |

### Totalização (rodapé — compartilhada entre os dois modos)

| Campo | Cálculo |
|---|---|
| `total` | `SUM(total)` — frontend |
| `totalComissao` | `SUM(comissao)` — frontend |
| Líquido | `total - totalComissao` — template |

### Regras de Negócio

- **RN-GC-01** Área obrigatória para gerentes em ambos os modos 🟢
- **RN-GC-02** Admin vê todas as áreas com "TODAS" 🟢
- **RN-GC-03** `areaVendedor(area)` recarrega combo Vendedor via AJAX ao mudar área 🟢
- **RN-GC-04** Modalidade carregada com base em `currentAccount.filtro_banca` (campo de usuário que filtra por banca) 🟡
- **RN-GC-05** Coluna "Líquido" calculada no template — não retornada pelo backend 🟢
- **RN-GC-06** Resultado vazio → "Não existe resultado para está data!" 🟢
- **RN-GC-07** Sem register_log — relatório de consulta apenas 🟢

---

## Módulo 4: `apuracao` — Apuração (Bilhetinho)

### Visão Geral

Listagem de bilhetes Bilhetinho individuais por data/área/extração. **Sem restrição de área por perfil** — todos os usuários veem todas as áreas. Mantém o bug de janeiro em `diaAtual()`. 🔴

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data | `date` | Não | Pré-preenchido com hoje (**bug de janeiro presente**) |
| Área | `select` | Não | "TODAS" — sem restrição por perfil 🔴 |
| Extração | `select` | Não | `queryBilhetinho()` — somente Bilhetinho |

> **Sem restrição de coletor/setorista** — qualquer usuário vê todas as áreas, diferente do `apuracaojb`. 🔴

### Colunas (`IApuracao`)

| Coluna | Campo | Formato | Notas |
|---|---|---|---|
| Nsu | `nsu` | `000000`-padded | `("000000" + nsu).slice(-6)` |
| Poule | `poule` | `000000`-padded | idem |
| Vendedor | `nome` | string | |
| Data | `data_hora` | `dd/MM/yyyy HH:mm:ss` | pipe Angular `date` |
| Extração | `descricao` | string | |
| Palpite | `palpites` | string | exibido como vem do backend |
| Valor | `total_sorteio` | BRL | |

### Totalização (rodapé)

```
| Total (colspan=6) | R$ X.XXX,XX |
```

Total calculado no frontend: `SUM(total_sorteio)`.

### API

| Endpoint | Parâmetros |
|---|---|
| `GET api/apuracao` | `data?, area?, extracao?` |

### Regras de Negócio

- **RN-APB-01** Sem restrição de área — todas as áreas visíveis independente do perfil 🔴
- **RN-APB-02** Extração filtrada por `queryBilhetinho()` — somente Bilhetinho 🟢
- **RN-APB-03** Bug de janeiro em `diaAtual()` — define dezembro do ano anterior em janeiro 🔴
- **RN-APB-04** Sem auditoria register_log 🟢

---

## Módulo 5: `apuracaojb` — Apuração (JB)

### Visão Geral

Listagem de bilhetes JB individuais por data/área/extração. **Com restrição de área** por perfil (coletor/setorista). Bug de janeiro **corrigido**. 🟢

### Filtros

| Filtro | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Data | `date` | Não | Pré-preenchido com hoje (bug de janeiro **corrigido**) |
| Área | `select` | Sim (gerente) | `loadArea()` — coletor/setorista restrito; admin vê "TODAS" |
| Extração | `select` | Não | `queryJb()` — somente JB |

### Colunas (`IApuracaoJb`)

| Coluna | Campo | Formato | Notas |
|---|---|---|---|
| Nsu | `nsu` | `000000`-padded | idem `apuracao` |
| Poule | `poule` | `000000`-padded | |
| Vendedor | `nome` | string | |
| Data | `data_hora` | `dd/MM/yyyy HH:mm:ss` | |
| Extração | `descricao` | string | |
| Palpite | `palpites` | string | **processado**: `split(',').join(' ')` — vírgulas → espaços |
| Valor | `total_sorteio` | BRL | |

> **Diferença no campo palpites:** `apuracaojb` converte vírgulas para espaços em `palpites` (`split(',').join(' ')`). `apuracao` (Bilhetinho) exibe o campo tal como vem do backend. 🟢

### Totalização (rodapé)

```
| Total R$ X.XXX,XX (colspan=7) |       <- em célula única
```

> **Diferença de layout do rodapé:** `apuracao` tem label e valor em células separadas (colspan=6 + 1 célula). `apuracaojb` coloca tudo em uma única célula colspan=7. 🟢

### API

| Endpoint | Parâmetros |
|---|---|
| `GET api/apuracaojb` | `data?, area?, extracao?` |

### Regras de Negócio

- **RN-APJ-01** Área obrigatória para gerentes — "Selecione uma área!" 🟢
- **RN-APJ-02** Admin vê todas as áreas com "TODAS" 🟢
- **RN-APJ-03** Palpites com vírgulas são normalizados para espaços no frontend 🟢
- **RN-APJ-04** Resultado vazio → "Não existe resultado para está data!" 🟢
- **RN-APJ-05** Sem auditoria register_log 🟢

---

## Módulo 6: `register-log` — Serviço de Auditoria

### Visão Geral

Não há tela dedicada ao registro de log — `register-log` é um **serviço puro** consumido por outros módulos. Toda ação de auditoria do sistema passa por este serviço. 🟢

### APIs

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `api/registerLog` | Listar logs |
| `POST` | `api/registerLog` | Criar entrada de log |
| `PUT` | `api/registerLog/{id}` | Atualizar log |

### Payload de criação (padrão)

```json
{
  "acao": "NomeDoModulo",
  "usuario": "login_do_usuario",
  "historico": "Descrição da ação",
  "ip": "xxx.xxx.xxx.xxx"
}
```

### Obtenção do IP

`getIpAddress()` chama `http://api.ipify.org/?format=json` → retorna `{ ip: "..." }`. 🟢

> **Dependência externa:** o serviço depende de `api.ipify.org` para obter o IP público. Se o serviço estiver indisponível, o IP fica `undefined`. 🔴

### Módulos que utilizam register_log

| Módulo | Ação registrada |
|---|---|
| Descarrego JB | Processar / Processar Todas / Processar Todas Agrupado |
| Resultado JB | Encerrar Sorteio / Processar / Limpar |
| Quininha Extração | Editar Extração |
| Seninha Extração | Editar Extração |
| Lotinha Extração | Editar Extração |

> **Sem UI de listagem de logs** no Java — somente service. No Zooloo, a implementação PHP precisará do service e, opcionalmente, de uma tela de auditoria. 🟡

---

## Comparação entre `apuracao` e `apuracaojb`

| Aspecto | apuracao (Bilhetinho) | apuracaojb (JB) |
|---|---|---|
| Tipo de extração | Bilhetinho (`queryBilhetinho`) | JB (`queryJb`) |
| Restrição área por perfil | **Não** (TODAS sempre) 🔴 | Sim (coletor/setorista) |
| Bug de janeiro em `diaAtual()` | **Sim** 🔴 | Não (corrigido) |
| Normalização de palpites | Não | `split(',').join(' ')` |
| Layout do rodapé | 2 células (label + valor) | 1 célula colspan=7 |
| API | `api/apuracao` | `api/apuracaojb` |

---

## Dependências

| Componente | Relacionado a |
|---|---|
| `cfg_extracao_descarga` | Descarrego — determina quais apostas são "descarga" |
| `cad_extracao` | Todos — filtros por extração |
| `cad_area` | Todos — filtros e restrição por área |
| `cad_vendedor` | Geral Comissão — filtro por vendedor |
| `cad_modalidade` | Geral Comissão — filtro por modalidade |
| `cad_coletor` / `SetoristaAreaService` | Descarrego, GeralCaixaJb, ApuracaoJb, GeralComissao — restrição de área |
| `cfg_parametros` | GeralCaixaJb — combo Tipo de jogo ativo |
| `mov_jb` / `mov_bilhetinho` | Apuração e Descarrego — fonte dos bilhetes |
| `register_log` | Descarrego — auditoria de processamento |
| `api.ipify.org` | register-log service — IP externo |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela Descarrego (4 modos) | Angular presente | Ausente | 🔴 |
| APIs `descarregojb`, `descarregoagrupado`, `descarregados`, `descarregadosagrupado` | Presentes | Ausentes | 🔴 |
| APIs processar Descarrego (6 endpoints PUT) | Presentes | Ausentes | 🔴 |
| Download PDF (Descarrego) | Presente | Ausente | 🔴 |
| Countdown hora_limite (Descarrego) | Presente | Ausente | 🔴 |
| Tela Movimento Geral Financeiro | Angular presente | Ausente | 🔴 |
| API `geralCaixajb` | Presente | Ausente | 🔴 |
| Tela Geral Comissão (2 modos) | Angular presente | Ausente | 🔴 |
| APIs `geralvendedor` e `geralarea` | Presentes | Ausentes | 🔴 |
| Tela Apuração Bilhetinho | Angular presente | Ausente | 🔴 |
| API `apuracao` | Presente | Ausente | 🔴 |
| Tela Apuração JB | Angular presente | Ausente | 🔴 |
| API `apuracaojb` | Presente | Ausente | 🔴 |
| Service register-log (POST/GET) | Presente | Ausente como service PHP | 🔴 |
| Corrigir bug bouteão Descarrego (última linha) | Bug presente | — | 🔴 (corrigir) |
| Corrigir typo "Porcessado" | Bug presente | — | 🔴 (corrigir) |
| Corrigir bug janeiro (apuracao Bilhetinho) | Bug presente | — | 🔴 (corrigir) |
| Corrigir falta de restrição área (apuracao) | Bug presente | — | 🔴 (corrigir) |

---

## Critérios de Aceitação (propostos)

```gherkin
# Descarrego — happy path detalhado
Dado que existem apostas acima do limite em 2026-04-30, extração TARDE, área 1
Quando gestor filtra data=2026-04-30, área=1, extração=TARDE e clica "Busca Detalhada"
Então tabela exibe linhas com: #, Data, Apostado (BRL), Jogo, botão Processar
E rodapé exibe totalGeral somado

# Descarrego — processar individual
Quando gestor clica Processar em uma linha
Então modal exibe os dados da aposta
E ao confirmar: PUT api/descarregojb/{userId}/{nome} enviado
E register_log criado com acao='Descarrego', historico='Processar'
E lista recarrega (evento descarregoListModification)

# Descarrego — processar todas
Quando gestor clica "Processar Todas"
Então PUT api/descarregarTodas/{userId}/{nome} enviado com {data_sorteio, extracao}
E register_log criado com historico='Processar Todas'
E alerta de sucesso "Processado com sucesso!" exibido

# Descarrego — restrição de área gerente
Dado que usuário é gerente (não admin)
Quando clica Busca Detalhada sem selecionar área
Então alerta "Selecione uma área!"

# Movimento Geral Financeiro — 9 colunas
Dado que existem movimentos no período
Quando gestor busca
Então tabela exibe 9 colunas incluindo "Total Geral" (total-premio) e "Diferença" (premio-premioPago)
E rodapé exibe totalizações de todas as 9 colunas

# Geral Comissão — modo vendedor
Quando gestor clica "Geral Vendedor"
Então tabela Vendedor/Extração/Modalidade/Total/Comissão/Líquido exibida
E Líquido = Total - Comissão para cada linha

# Geral Comissão — AJAX vendedor
Quando gestor altera área de 1 para 2
Então combo Vendedor recarrega com vendedores da área 2

# Apuração Bilhetinho — listagem
Dado que existem bilhetes Bilhetinho em 2026-04-30
Quando gestor busca
Então tabela exibe: Nsu (6 dígitos), Poule (6 dígitos), Vendedor, Data, Extração, Palpite, Valor

# Apuração JB — palpites separados por espaço
Dado que um bilhete tem palpites "1234,5678,9012"
Quando gestor busca
Então coluna Palpite exibe "1234 5678 9012" (vírgulas → espaços)

# Apuração JB — área obrigatória
Dado que usuário é gerente
Quando clica Buscar sem selecionar área
Então alerta "Selecione uma área!"
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| `DescarregoList.php` — 4 modos de visualização | Must | Operação crítica diária de controle de risco |
| APIs Descarrego (6 GET + 6 PUT) | Must | Backend necessário para o módulo |
| Processar individual (modal) | Must | Fluxo principal de aprovação de descarga |
| Processar Todas (lote) | Must | Eficiência operacional |
| Download PDF Descarrego | Should | Utilidade operacional |
| Countdown hora_limite | Should | UX — contexto temporal para o operador |
| `GeralCaixaJbList.php` — 9 colunas | Must | Relatório financeiro gerencial principal |
| API `geralCaixajb` | Must | Backend necessário |
| `GeralComissaoList.php` — 2 modos | Must | Gestão de comissões por área/vendedor |
| APIs `geralvendedor` + `geralarea` | Must | Backend necessário |
| `ApuracaoList.php` (Bilhetinho) | Should | Conferência de bilhetes Bilhetinho |
| `ApuracaoJbList.php` (JB) | Should | Conferência de bilhetes JB |
| Service register-log PHP | Must | Auditoria de todas as ações do sistema |
| Corrigir bug situação botões Descarrego | Must | Lógica incorreta de habilitação |
| Corrigir typo "Porcessado" → "Processado" | Should | UX |
| Corrigir bug janeiro (apuracao Bilhetinho) | Must | Data incorreta em janeiro |
| Adicionar restrição área (apuracao Bilhetinho) | Must | Segurança de dados |
| Dependência externa `api.ipify.org` | Should | Avaliar alternativa interna para IP |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Módulo | Sistema |
|---|---|---|
| `allsystem/.../descarrego-jb/descarrego-jb.component.html` | Descarrego | 4 tabelas + botões contextuais |
| `allsystem/.../descarrego-jb/descarrego-jb.component.ts` | Descarrego | `load()`, `processarTodas()`, `downloadPDF()`, countdown |
| `allsystem/.../descarrego-jb/descarrego-jb.service.ts` | Descarrego | 6 GET + 6 PUT endpoints |
| `allsystem/.../descarrego-jb/descarrego-jb-modal.component.ts` | Descarrego | Modal processar individual |
| `allsystem/.../geral-caixajb/geral-caixajb.component.html` | GeralCaixaJb | 9 colunas (2 computadas) |
| `allsystem/.../geral-caixajb/geral-caixajb.component.ts` | GeralCaixaJb | `loadParametro()`, totalização frontend |
| `allsystem/.../geral-caixajb/geral-caixajb.service.ts` | GeralCaixaJb | `GET api/geralCaixajb` |
| `allsystem/.../geral-comissao/geral-comissao.component.html` | GeralComissao | 2 tabelas + 2 botões de modo |
| `allsystem/.../geral-comissao/geral-comissao.component.ts` | GeralComissao | `loadAllVendedor()`, `loadAllArea()`, `areaVendedor()` AJAX |
| `allsystem/.../geral-comissao/geral-comissao.service.ts` | GeralComissao | `GET api/geralvendedor`, `GET api/geralarea` |
| `allsystem/.../apuracao/apuracao.component.html` | Apuração Bilhetinho | 7 colunas, rodapé 2 células |
| `allsystem/.../apuracao/apuracao.component.ts` | Apuração Bilhetinho | Bug janeiro, sem restrição área |
| `allsystem/.../apuracao/apuracao.service.ts` | Apuração Bilhetinho | `GET api/apuracao` |
| `allsystem/.../apuracaojb/apuracaojb.component.html` | Apuração JB | 7 colunas, rodapé 1 célula |
| `allsystem/.../apuracaojb/apuracaojb.component.ts` | Apuração JB | `loadArea()` restrito, `split(',').join(' ')` em palpites |
| `allsystem/.../apuracaojb/apuracaojb.service.ts` | Apuração JB | `GET api/apuracaojb` |
| `allsystem/.../register-log/register-log.service.ts` | Auditoria | `POST api/registerLog`, `getIpAddress()` ipify |

**Arquivos a criar no Zooloo:**

| Arquivo | Módulo | Descrição |
|---|---|---|
| `app/control/descarrego/DescarregoList.php` | Descarrego | 4 modos de visualização + modal processar |
| `app/service/rest/DescarregoRestService.php` | Descarrego | 6 endpoints GET + 6 PUT |
| `app/control/relatorio/GeralCaixaJbList.php` | GeralCaixaJb | 9 colunas (2 computadas) + totalização |
| `app/control/relatorio/GeralComissaoList.php` | GeralComissao | 2 modos (vendedor/área) + AJAX |
| `app/control/relatorio/ApuracaoList.php` | Apuração Bilhetinho | 7 colunas, sem restrição área, corrigir bug janeiro |
| `app/control/relatorio/ApuracaoJbList.php` | Apuração JB | 7 colunas, restrição área, normalizar palpites |
| `app/service/log/RegisterLogService.php` | Auditoria | POST `api/registerLog`, obtenção de IP |
