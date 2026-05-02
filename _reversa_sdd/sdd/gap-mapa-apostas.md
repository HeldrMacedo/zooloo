# GAP: Mapa de Apostas — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — tela presente no Java (allsystem) mas **não implementada** no Zooloo PHP

---

## Visão Geral

O **Mapa de Apostas** é uma tela de controle de risco **preventivo** — diferente do Descarrego (que é reativo, acionado quando um limite já foi ultrapassado), o Mapa de Apostas exibe o volume consolidado de apostas por número/palpite em um sorteio, permitindo ao gestor identificar concentrações de risco antes do fechamento do sorteio.

Para cada número apostado, exibe: o bicho correspondente no Jogo do Bicho, a quantidade de jogos (contagens de apostas) e o valor total apostado. Permite ordenar por palpite, por quantidade ou por valor para priorizar a análise.

O combo de modalidade é intencionalmente restrito a apenas MILHAR, CENTENA, GRUPO e DEZENA — as únicas modalidades cujo palpite é um número fechado mapeável a um bicho.

---

## Estrutura de Dados Envolvida

A tela consulta `mov_jb_sort_palpite` agregado, cruzando com `mov_jb` (área, data), `mov_sorteio` (extração, data), `cad_modalidade` (tipo de jogo) e a tabela/lógica de grupos do Jogo do Bicho para derivar o `bicho`.

### Modelo de retorno (`IMapaApostas`)

| Campo | Tipo | Descrição |
|---|---|---|
| `bicho` | `string` | Nome do animal no Jogo do Bicho (derivado do grupo do palpite) |
| `palpite` | `string` | Número apostado (4 dígitos para Milhar, 3 para Centena, 2 para Dezena, 2 para Grupo) |
| `jogos` | `number` | Contagem de apostas neste palpite no filtro selecionado |
| `total` | `number` | Soma de `valor_palpite` para este palpite no filtro |

**Total Apostas:** soma de `total` de todos os registros retornados.

---

## Interface no Sistema Java (allsystem — referência)

### Filtros

| Filtro | Campo | Obrigatório | Comportamento |
|---|---|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` | Sim | Pré-preenchido com data atual |
| Data Final | `mov_jb.data_hora <= dataFinal` | Sim | Pré-preenchido com data atual |
| Área | `mov_jb.area_id` | Sim para gerente | Gerente: restrito à própria área; Admin: com opção "TODAS" |
| Extração | `mov_sorteio.extracao_id` | Sim | Sem "TODAS" — seleção obrigatória (erro HTTP se ausente) |
| Modalidade | `cad_modalidade.modalidade_id` | Sim | **Filtrada:** somente MILHAR, CENTENA, GRUPO, DEZENA |
| Ordem | Sort | Não | Palpite (padrão) / Quantidade de jogos / Valor total jogos |

### Valores do filtro Ordem

| Valor | Descrição |
|---|---|
| `0` | Palpite (ordenação padrão, numérica/alfabética) |
| `1` | Quantidade de jogos (descendente — mais apostados primeiro) |
| `2` | Valor total jogos (descendente — maior volume financeiro primeiro) |

### Restrição de modalidades no combo

O combo de modalidade é pré-filtrado pelo frontend para exibir apenas modalidades cujo `int_jogo.descricao` seja um dos quatro tipos:

| `int_jogo.descricao` | Justificativa |
|---|---|
| `MILHAR` | Palpite de 4 dígitos — mapeia a bicho |
| `CENTENA` | Palpite de 3 dígitos — mapeia a bicho pelos 2 últimos |
| `DEZENA` | Palpite de 2 dígitos — mapeia a bicho |
| `GRUPO` | Palpite de 2 dígitos (grupo = 1–25) — é diretamente um bicho |

> **Justificativa da restrição:** Modalidades como Duque, Terno, Milhar Brinde etc. têm estrutura de palpite diferente — o mapa por número apostado não se aplica da mesma forma. 🟡

### Colunas da tabela

| Coluna | Dado | Notas |
|---|---|---|
| Bicho | `bicho` | Nome do animal derivado do grupo do palpite |
| Palpite | `palpite` | Número apostado |
| Jogos | `jogos` | Quantidade de apostas neste número |
| Total | `total` | Valor total apostado — formatado como BRL |

**Linha de totalização:** "Total Apostas" + `SUM(total)` no rodapé da tabela.

---

## Fluxo Principal

```
1. Gestor acessa Mapa de Apostas
2. Frontend pré-preenche dataInicial/dataFinal com data atual
3. Frontend carrega áreas (por perfil) + extrações JB + modalidades filtradas
4. Gestor seleciona Extração + Modalidade (obrigatórios), opcionalmente ajusta Ordem
5. Clica "Buscar" → GET api/mapaapostas?{filtros}
6. Backend agrupa por palpite, calcula bicho, conta jogos, soma total, aplica ordenação
7. Frontend exibe tabela + totalização
8. Se resultado vazio → alerta "Não existe resultado para está data!"
9. Se erro HTTP (extração/modalidade ausentes) → alerta "Escolha uma Extração e Modalidade!"
```

---

## Regras de Negócio

- **RN-MA-01** Extração e Modalidade são obrigatórios — busca sem eles resulta em erro HTTP com alerta 🟢
- **RN-MA-02** Área obrigatória para gerentes — busca sem seleção retorna alerta "Selecione uma área!" 🟢
- **RN-MA-03** Admin visualiza todas as áreas — combo com opção "TODAS" 🟢
- **RN-MA-04** Combo de modalidade exibe apenas MILHAR, CENTENA, GRUPO e DEZENA — outros jogos não aparecem 🟢
- **RN-MA-05** Ordenação padrão é por Palpite (`ordem=0`) — alternativas: por qtd. de jogos (`1`) ou valor total (`2`) 🟢
- **RN-MA-06** `bicho` é derivado pelo backend a partir do grupo JB do palpite — não é campo de `mov_jb_sort_palpite` 🟡
- **RN-MA-07** Diferença do Descarrego: Mapa de Apostas é preventivo (consulta a qualquer momento) vs. Descarrego é reativo (apostas que ultrapassaram o limite de descarga) 🟡
- **RN-MA-08** Não há ação de "processar" — tela é somente leitura, diferente do Descarrego 🟢

---

## Diferença entre Mapa de Apostas e Descarrego

| Aspecto | Mapa de Apostas | Descarrego |
|---|---|---|
| Propósito | Visualização preventiva do volume por número | Gestão de apostas que ultrapassaram o limite |
| Filtro de extração | Obrigatório — sem valor padrão | Obrigatório |
| Filtro de data | Intervalo (ini/fim) | Data exata do sorteio |
| Agrupamento | Por palpite (bicho + número + jogos + total) | Por aposta individual ou agrupado |
| Ação disponível | Nenhuma — somente leitura | "Processar" (individual ou todos) + Download PDF |
| Restrição de modalidade | Somente MILHAR/CENTENA/GRUPO/DEZENA | Todas as modalidades configuradas |
| Quando usar | Monitoramento contínuo antes do sorteio | Após identificar apostas acima do `cfg_extracao_descarga.limite_descarga` |

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_jb_sort_palpite` | Tabela fonte — `palpite`, `valor_palpite`, contagem de apostas |
| `mov_jb` | JOIN para data, área, cancelado |
| `mov_sorteio` | JOIN para extração e data do sorteio |
| `cad_modalidade` + `int_jogo` | Filtro de modalidade + identificação do tipo de jogo |
| Lógica de grupos JB | Derivação de `bicho` a partir do palpite (`dezmilhar → grupo → nome do animal`) |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela Mapa de Apostas | `mapa-apostas` Angular | Ausente | 🔴 |
| API `mapaapostas` | `GET api/mapaapostas` (Spring) | Ausente | 🔴 |
| Agrupamento por palpite com bicho | Presente | Ausente | 🔴 |
| Filtro de modalidade restrito (4 tipos) | Presente | Ausente | 🔴 |
| Ordenação por palpite/qtd/valor | Presente | Ausente | 🔴 |
| Controle de acesso por perfil Gerente | Presente | Ausente | 🔴 |
| Entidade `MovJbSortPalpite.php` | Existe | Existe ✅ | — |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — mapa por modalidade e extração
Dado que existem apostas para 2026-04-30, área 1, extração Federal, modalidade Milhar
Quando gestor seleciona data=2026-04-30, área=1, extração=Federal, modalidade=Milhar, ordem=Palpite
E clica "Buscar"
Então tabela exibe uma linha por palpite com bicho, palpite, jogos, total
E linha de totalização exibe soma total de apostas

# Happy path — ordenação por quantidade
Quando gestor muda Ordem para "Quantidade de jogos" e busca
Então tabela exibe os números mais apostados primeiro

# Happy path — ordenação por valor
Quando gestor muda Ordem para "Valor total jogos" e busca
Então tabela exibe os números com maior valor financeiro primeiro

# Falha — extração não selecionada
Quando gestor clica Buscar sem selecionar extração
Então alerta "Escolha uma Extração e Modalidade!"

# Falha — área não selecionada (gerente)
Dado que usuário é gerente
Quando clica Buscar sem selecionar área
Então alerta "Selecione uma área!"

# Restrição de modalidade no combo
Quando gestor abre o combo de modalidade
Então apenas modalidades do tipo MILHAR, CENTENA, GRUPO ou DEZENA aparecem
E modalidades Duque, Terno, Bilhetinho etc. não aparecem

# Controle de acesso — admin
Dado que usuário é admin
Quando acessa Mapa de Apostas
Então combo de área exibe opção "TODAS" e todas as áreas cadastradas

# Resultado vazio
Dado que não há apostas no período/filtros selecionados
Quando gestor busca
Então alerta "Não existe resultado para está data!"
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Tela Mapa de Apostas com tabela bicho/palpite/jogos/total | Must | Ferramenta de controle de risco operacional diário |
| Filtros data / área / extração / modalidade | Must | Seleção mínima para consulta útil |
| Restrição de modalidade para 4 tipos | Must | Apenas esses fazem sentido no contexto do mapa |
| Ordenação por palpite / qtd / valor | Should | Facilita priorização de risco |
| Controle de acesso por perfil Gerente | Must | Segurança de dados |
| Totalização no rodapé | Must | Visão rápida do volume total no período |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/mapa-apostas/mapa-apostas.compoment.html` | Java |
| `allsystem/.../webapp/app/entities/mapa-apostas/mapa-apostas.component.ts` | Java |
| `allsystem/.../webapp/app/entities/mapa-apostas/mapa-apostas.service.ts` | Java |
| `allsystem/.../webapp/app/shared/model/mapa-apostas.model.ts` | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/mapa-apostas/MapaApostasList.php` | Tela de mapa com filtros, tabela bicho/palpite/jogos/total e ordenação |
