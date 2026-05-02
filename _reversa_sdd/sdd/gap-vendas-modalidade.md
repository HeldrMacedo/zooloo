# GAP: Movimento Geral Vendas Modalidade — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — tela presente no Java (`vendedor-vendas-vendedor`) mas **não implementada** no Zooloo PHP

---

## Visão Geral

O **Movimento Geral Vendas Modalidade** agrupa os resultados financeiros do período por **modalidade** — ao contrário do `geral-venda-modalidade` (que agrupa por vendedor), esta tela mostra quanto cada tipo de aposta gerou de apurado, comissão, líquido, prêmio e total no período filtrado.

Permite ordenar por modalidade, apurado, prêmio ou total, com atualização imediata ao mudar a ordenação (sem novo clique em Buscar).

> **Nota de nomenclatura:** A pasta Java chama-se `vendedor-vendas-vendedor`, mas o título da tela é "Movimento Geral Vendas Modalidade" e o componente se chama `VendedorVendasVendedorComponent`. O modelo Java (`IGeralVendasVendedor`) declara apenas `modalidade` e `total`, mas a API retorna campos adicionais (`comissao`, `liquido`, `premio`, `totalGeral`) não refletidos no modelo TypeScript — discrepância de documentação interna. 🟡

---

## Diferença entre telas de vendas

| Tela | Agrupamento | Colunas financeiras | Filtro adicional |
|---|---|---|---|
| `geral-venda-modalidade` (item 18) | Por **vendedor** | Apenas `total` | Modalidade |
| `geral-vendas-area` (item 18) | Por **área** | Apenas `total` | Modalidade |
| `vendedor-vendas-vendedor` (este) | Por **modalidade** | apurado, comissão, líquido, prêmio, total | Vendedor + Ordenação |

---

## Estrutura de Dados Envolvida

A consulta agrega `mov_jb_sorteio` cruzado com `mov_jb` (filtro de data, área, vendedor, cancelado) e `cad_modalidade` (descrição), calculando por modalidade:

### Modelo de retorno (campos reais da API)

| Campo | Tipo | Cálculo |
|---|---|---|
| `modalidade` | `string` | `cad_modalidade.apresentacao` (ou `int_jogo.descricao`) |
| `total` | `number` | `SUM(total_sorteio) WHERE cancelado='N'` — **Apurado** |
| `comissao` | `number` | `SUM(comissao_sorteio)` |
| `liquido` | `number` | `total - comissao` |
| `premio` | `number` | `SUM(sorteado_valor)` — prêmio a pagar |
| `totalGeral` | `number` | `liquido - premio` — resultado líquido final |

> **Discrepância de modelo:** `IGeralVendasVendedor` no TypeScript declara apenas `{ modalidade, total }`. Os campos `comissao`, `liquido`, `premio` e `totalGeral` existem na API e são usados no template/TS mas não estão tipados no model. 🟡

---

## Interface no Sistema Java (allsystem — referência)

### Filtros

| Filtro | Campo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` | Não | Pré-preenchido com data atual |
| Data Final | `mov_jb.data_hora <= dataFinal` | Não | Pré-preenchido com data atual |
| Área | `mov_jb.area_id` | Sim para gerente | Gerente: restrito à própria área; Admin: com "TODAS" |
| Vendedor | `mov_jb.vendedor_id` | Não | "TODOS" disponível; recarregado ao mudar área via AJAX |
| Ordenar | Sort | Não | MODALIDADE (padrão) / APURADO / PRÊMIO / TOTAL |

### Valores do filtro Ordenar

| Valor | Descrição | Comportamento |
|---|---|---|
| `1` | MODALIDADE | Ordenação alfabética por nome de modalidade (padrão) |
| `2` | APURADO | Descendente por valor apurado |
| `3` | PRÊMIO | Descendente por valor de prêmio |
| `4` | TOTAL | Descendente por total geral |

> **Comportamento especial:** mudar o combo de Ordenação **dispara `loadAll()` automaticamente** — sem necessidade de clicar "Buscar" novamente. 🟢

### Colunas

| Coluna | Dado | Notas |
|---|---|---|
| Modalidade | `modalidade` | Agrupa toda a linha pelo tipo de aposta |
| Apurado | `total` | Moeda BRL |
| Comissão | `comissao` | Moeda BRL |
| Liquido | `liquido` | Moeda BRL = apurado − comissão |
| Prêmio | `premio` | Moeda BRL = prêmio a pagar pelo sorteio |
| Total | `totalGeral` | Moeda BRL = líquido − prêmio |

### Linha de totalização

| Campo | Cálculo |
|---|---|
| Total (apurado) | `SUM(total)` de todas as modalidades |
| Total comissão | `SUM(comissao)` |
| Total líquido | `SUM(liquido)` |
| Total prêmio | `SUM(premio)` |
| Total geral | `SUM(totalGeral)` |

---

## Fluxo Principal

```
1. Gestor acessa Movimento Geral Vendas Modalidade
2. Frontend pré-preenche dataInicial=hoje, dataFinal=hoje; ordenar=1 (MODALIDADE)
3. Frontend carrega áreas (por perfil) + vendedores (lista completa inicial)
4. Gestor seleciona filtros e clica "Buscar" (ou muda Ordenar → dispara automaticamente)
5. GET api/geralvendasvendedor?{filtros}
6. Backend: SELECT modalidade, SUM(total_sorteio), SUM(comissao_sorteio), ...
   FROM mov_jb_sorteio
   JOIN mov_jb ON ...
   JOIN cad_modalidade ON ...
   WHERE cancelado='N' AND data BETWEEN dataInicial AND dataFinal
   [AND area_id=X] [AND vendedor_id=Y]
   GROUP BY modalidade
   ORDER BY {campo escolhido}
7. Frontend exibe tabela + totalização
8. Se resultado vazio → alerta "Não existe resultado para está data!"
```

---

## Regras de Negócio

- **RN-VVM-01** Área obrigatória para gerentes — busca sem seleção retorna alerta "Selecione uma área!" 🟢
- **RN-VVM-02** Admin vê todas as áreas — combo com opção "TODAS" 🟢
- **RN-VVM-03** Mudar Ordenação dispara nova busca automaticamente (sem clicar Buscar) 🟢
- **RN-VVM-04** `totalGeral = líquido − prêmio` — valor que a banca efetivamente retém por modalidade 🟡
- **RN-VVM-05** Vendedor filtrado via AJAX quando área é alterada — mesmo padrão de outros relatórios 🟢
- **RN-VVM-06** Bilhetes cancelados são excluídos do cálculo (`cancelado='N'`) 🟢
- **RN-VVM-07** A ordenação é aplicada pelo backend — o frontend não reordena localmente 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_jb_sorteio` | Fonte: total_sorteio, comissao_sorteio, sorteado_valor |
| `mov_jb` | JOIN para data, área, vendedor, cancelado |
| `cad_modalidade` + `int_jogo` | Agrupamento por modalidade |
| `mov_sorteio` | JOIN para extração e data do sorteio |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela Vendas por Modalidade | `vendedor-vendas-vendedor` Angular | Ausente | 🔴 |
| API `geralvendasvendedor` | Presente (Spring) | Ausente | 🔴 |
| Agrupamento por modalidade com 5 métricas | Presente | Ausente | 🔴 |
| Ordenação por modalidade/apurado/prêmio/total | Presente | Ausente | 🔴 |
| Ordenação automática ao mudar combo | Presente | Ausente | 🔴 |
| AJAX área → vendedor | Presente | Ausente | 🔴 |
| Totalização de 5 colunas no rodapé | Presente | Ausente | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — vendas por modalidade no período
Dado que existem apostas em Milhar (R$1000) e Dezena (R$500) para 2026-04-30 na área 1
Quando gestor filtra data=2026-04-30, área=1, vendedor=TODOS, ordenar=MODALIDADE
Então tabela exibe linha para Dezena e linha para Milhar
E cada linha mostra: apurado, comissão, líquido, prêmio, total
E rodapé totaliza todas as colunas

# Happy path — filtrar por vendedor específico
Quando gestor seleciona vendedor=João e busca
Então tabela exibe somente as modalidades com apostas do vendedor João

# Happy path — ordenar por apurado
Quando gestor muda Ordenar para "APURADO"
Então nova busca é disparada automaticamente
E tabela exibe modalidades da maior para a menor apurada

# Happy path — ordenar por total
Quando gestor muda Ordenar para "TOTAL"
Então tabela exibe modalidades pelo maior resultado líquido final

# Falha — área não selecionada (gerente)
Dado que usuário é gerente
Quando clica Buscar sem área selecionada
Então alerta "Selecione uma área!"

# Resultado vazio
Dado que não há apostas no período
Quando gestor busca
Então alerta "Não existe resultado para está data!"

# Totalização
Dado que há 3 modalidades com apurado=[1000, 500, 200], comissão=[100, 50, 20]
Então rodapé exibe: apurado=1700, comissão=170, líquido=1530
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Tela com agrupamento por modalidade | Should | Visão gerencial de performance por tipo de jogo |
| Filtros data / área / vendedor | Must | Seleção básica para relatório útil |
| Colunas: apurado, comissão, líquido, prêmio, total | Must | Informação financeira completa por modalidade |
| Ordenação por 4 critérios com disparo automático | Should | UX diferencial para análise de risco/performance |
| Totalização no rodapé | Must | Visão consolidada |
| Controle de acesso por perfil Gerente | Must | Segurança de dados |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/vendedor-vendas-vendedor/vendedor-vendas-vendedor.component.html` | Java |
| `allsystem/.../webapp/app/entities/vendedor-vendas-vendedor/vendedor-vendas-vendedor.component.ts` | Java |
| `allsystem/.../webapp/app/entities/vendedor-vendas-vendedor/vendedor-vendas-vendedor.service.ts` | Java |
| `allsystem/.../webapp/app/shared/model/geral-vendas-vendedor.model.ts` | Java (modelo incompleto) |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/relatorio/VendasModalidadeList.php` | Lista agrupada por modalidade com 5 métricas + ordenação dinâmica |
