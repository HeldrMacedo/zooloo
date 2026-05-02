# GAP: Resultado JB Avançado — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP parcial — o Zooloo tem `ResultadoForm.php` básico, mas a versão Java (`resultadojb`) possui lógica avançada de auto-geração de números, múltiplos algoritmos de cálculo, `premiacaoMaxima` dinâmica e validações de cálculo de sorteio que **não existem** na implementação PHP atual.

---

## Visão Geral

O **Resultado JB** (`resultadojb`) é a tela central de lançamento de resultado do Jogo do Bicho no sistema allsystem. Ela difere significativamente do `ResultadoForm.php` atual do Zooloo em:

1. **Suporte a até 10 prêmios** — controlado por `extracaoPremiacao.premiacaoMaxima`
2. **Auto-display de grupo e animal** — ao digitar a milhar, exibe automaticamente o grupo (01–25) e o nome do animal correspondente
3. **Algoritmos de auto-geração** — 4 algoritmos distintos (`calculo_id` 2–6) que permitem sortear números automaticamente ou derivar prêmios dos 5 primeiros
4. **Permissão `PERM_ALTERAR_SORTEIO_FECHADO`** — usuários com esta permissão podem reabrir sorteios fechados (além do `allsystem/Administrator`)
5. **Vista `vwsorteio`** — combo de extração usa uma view materializada com os sorteios abertos do dia

---

## Estrutura de Dados

### Campos `cad_extracao` relevantes

| Campo | Tipo | Descrição |
|---|---|---|
| `premiacao_maxima` | `int` | Quantos prêmios o resultado deve ter (1–10) |
| `gerar_restante` | `boolean` | Se o cálculo id=4 pode auto-completar os prêmios 6–10 |
| `calc_sorteio_id` | FK `int_calculo_sorteio` | Algoritmo de cálculo do resultado |

### `int_calculo_sorteio` — algoritmos de resultado

| `calculo_id` | Comportamento |
|---|---|
| `1` | Manual puro — apenas entrada humana, sem auto-geração |
| `2` | Sortear aleatório com 10 prêmios (dezenas únicas, sem repetição) — botão Sortear exibido |
| `3` | Sortear aleatório com 5 prêmios (6–10 desabilitados) — botão Sortear exibido |
| `4` | Manual + auto-completar prêmios 6–10 a partir dos 5 primeiros (transposição de dígitos) |
| `5` | Manual + prêmios 6 e 7 calculados como soma e produto dos 5 primeiros |
| `6` | Sortear aleatório com 5 prêmios únicos (dezenas sem repetição) — botão Sortear exibido |

> **Cálculo id=4 — algoritmo:** prêmios 6–9 são compostos transposicionando colunas dos prêmios 1–4: `numsMilhar[5+k] = milhar[0][k] + milhar[1][k] + milhar[2][k] + milhar[3][k]`. Prêmio 10 é a soma dos 9 primeiros (últimos 4 dígitos). 🟢
>
> **Cálculo id=5 — algoritmo:** prêmio 6 = soma dos 5 primeiros (últimos 3 dígitos com prefixo `-`); prêmio 7 = produto de prêmio[0] × prêmio[1] (parte significativa com prefixo `-`). Validação extra: campos 6 e 7 devem ter 4 caracteres no formato `-DDD`. 🟢

### `vw_sorteio` — view de sorteios

| Campo | Uso |
|---|---|
| `extracao` | ID da extração (usado como key) |
| `descricao` | Texto exibido no combo — ordenado alfabeticamente |

### Mapeamento grupo → animal (25 grupos)

`chkGrupoDescricao(idx)` deriva o grupo a partir dos 2 últimos dígitos da milhar (`dezena`):

| Dezenas | Grupo | Animal |
|---|---|---|
| 01–04 | 01 | AVESTRUZ |
| 05–08 | 02 | AGUIA |
| 09–12 | 03 | BURRO |
| 13–16 | 04 | BORBOLETA |
| 17–20 | 05 | CACHORRO |
| 21–24 | 06 | CABRA |
| 25–28 | 07 | CARNEIRO |
| 29–32 | 08 | CAMELO |
| 33–36 | 09 | COBRA |
| 37–40 | 10 | COELHO |
| 41–44 | 11 | CAVALO |
| 45–48 | 12 | ELEFANTE |
| 49–52 | 13 | GALO |
| 53–56 | 14 | GATO |
| 57–60 | 15 | JACARE |
| 61–64 | 16 | LEAO |
| 65–68 | 17 | MACACO |
| 69–72 | 18 | PORCO |
| 73–76 | 19 | PAVAO |
| 77–80 | 20 | PERU |
| 81–84 | 21 | TOURO |
| 85–88 | 22 | TIGRE |
| 89–92 | 23 | URSO |
| 93–96 | 24 | VEADO |
| 97–00 | 25 | VACA |

> Dezena `00` é tratada como `100` para mapeamento correto no grupo 25. 🟢

---

## Interface no Sistema Java (allsystem — referência)

### Filtros

| Campo | Tipo | Comportamento |
|---|---|---|
| Data | `date` | Pré-preenchido com data atual |
| Extração | `select` | Populado via `GET api/vwsorteio` — lista sorteios abertos, ordenados por descrição |

### Botões de ação principais

| Botão | Estado inicial | Habilitado quando |
|---|---|---|
| Buscar | Habilitado | Sempre (executa busca) |
| Sortear | Hidden | `calculo_id` ∈ {2, 3, 6} E `numeros_sorteados === ''` |
| Salvar | Disabled | Todos os campos 1..premiacaoMaxima preenchidos com 4 dígitos |
| Limpar | Disabled | Após salvar números (numeros_sorteados preenchido, sorteio aberto) |
| Encerrar Sorteio | Disabled | Após salvar números |

### Campos de resultado (por prêmio 1..10)

| Campo | Tipo | Visibilidade | Comportamento |
|---|---|---|---|
| Nº Prêmio (input) | `text` maxlength=4 | Visível se `premiacaoMaxima >= N` | Numérico; tabindex avança automaticamente ao atingir 4 chars |
| Grupo (readonly) | `text` | Sempre visível | Auto-preenchido por `chkGrupoDescricao()` |
| Descrição (readonly) | `text` | Sempre visível | Nome do animal — auto-preenchido |

> **Tab automático:** `onkeyup="if(this.value.length == 4){next_field.focus();}"` — o cursor avança para o próximo prêmio automaticamente. 🟢

### Estados do sorteio após `buscar()`

| `numeros_sorteados` | `situacao` | Resultado |
|---|---|---|
| Vazio `''` | Qualquer | Habilita entrada + Salvar; alerta "Digite os sorteios!" |
| Preenchido | `'A'` (Aberto) | Habilita Limpar + Encerrar; desabilita Salvar e inputs |
| Preenchido | `'F'` (Fechado) | Se `isAllsystem=true`: habilita Limpar + Salvar; caso contrário: desabilita tudo |
| Sem sorteio | — | alerta "Não existe sorteio para está data e extração informada!" |

---

## Fluxo Principal

```
1. Gestor seleciona Data + Extração e clica Buscar
   a. GET api/vwsorteio → popula combo extração com sorteios abertos do dia
   b. GET api/buscar?data=X&extracao=Y → retorna dados do mov_sorteio

2. Sistema avalia estado do sorteio (numeros_sorteados + situacao):
   a. Vazio → habilita inputs e botão Salvar
   b. Preenchido aberto → mostra dados, habilita Limpar/Encerrar
   c. Fechado → bloqueia ou permite edição conforme permissão

3. Gestor digita milhar do 1º prêmio (4 dígitos):
   - Grupo e animal são exibidos automaticamente
   - Foco avança para o 2º prêmio automaticamente

4. (Opcional) Para calculo_id ∈ {2,3,6}: clica "Sortear"
   - Números gerados aleatoriamente com dezenas únicas

5. (Opcional) Para calculo_id=4: após 5º prêmio digitado:
   - Pergunta "Deseja gerar automaticamente os demais números?"
   - Sim → prêmios 6-10 calculados por algoritmo de transposição

6. (Opcional) Para calculo_id=5: após 5º prêmio digitado:
   - Pergunta confirmação
   - Sim → prêmios 6-7 calculados como soma/produto (formato -DDD)

7. Gestor clica Salvar (após validação de hora):
   - `verificarDataHora()`: agora >= hora_sorteio → prossegue
   - Para calculo_id=5: valida que prêmios 6-7 têm formato -DDD
   - PUT api/resultadojb/{extracao} com {data, numeros: "NNNN,NNNN,..."}
   - register_log: {acao:'Lançar Resultado', historico:'Salvar Resultado'}
   - Habilita Limpar + Encerrar; desabilita Salvar

8. (Opcional) Gestor clica Limpar:
   - Confirmação "Deseja limpar os prêmios?"
   - PUT api/limparjb/{extracao} com {data, sorteio_id}
   - register_log: {historico:'Limpar Resultado'}
   - Limpa inputs, volta ao estado inicial

9. Gestor clica Encerrar Sorteio:
   - Confirmação "Deseja encerrar este sorteio?"
   - PUT api/processarjb/{extracao} com {data, sorteio_id}
   - register_log: {historico:'Encerrar Resultado'}
   - Trigger trg_mv_sorteio_verifica_ganhadores calcula premiados
   - Desabilita Limpar + Encerrar
```

---

## Regras de Negócio

- **RN-RJB-01** `premiacaoMaxima` da extração controla quantos campos de prêmio são exibidos (1–10) 🟢
- **RN-RJB-02** Botão Salvar só fica ativo quando todos os campos 1..`premiacaoMaxima` estão preenchidos com 4 dígitos 🟢
- **RN-RJB-03** Milhar digitada com 4 dígitos auto-completa Grupo e Animal sem necessidade de botão 🟢
- **RN-RJB-04** Validação de hora antes de salvar: `now >= hora_sorteio` — igual ao Quininha/Seninha 🟢
- **RN-RJB-05** `calculo_id=4`: prêmios 6–10 podem ser gerados automaticamente a partir dos primeiros 5 por transposição de dígitos 🟢
- **RN-RJB-06** `calculo_id=5`: prêmios 6 e 7 usam fórmula matemática (soma dos 5 / produto de p1×p2) com formato `-DDD`; campos 6 e 7 devem ter exatamente 4 chars começando com `-` 🟢
- **RN-RJB-07** `calculo_id` ∈ {2, 6}: gera 5 milhares com dezenas únicas (sem repetição) aleatoriamente 🟢
- **RN-RJB-08** `calculo_id=2`: gera 10 milhares aleatórias com dezenas únicas 🟢
- **RN-RJB-09** `calculo_id=3`: apenas 5 campos habilitados (6–10 desabilitados via DOM); usa sorteio aleatório 🟢
- **RN-RJB-10** Sorteio fechado só pode ser reaberto por `allsystem/Administrator` OU usuário com `PERM_ALTERAR_SORTEIO_FECHADO` 🟢
- **RN-RJB-11** Combo de extração usa `vwsorteio` (view de sorteios abertos) — não a lista estática de extrações 🟢
- **RN-RJB-12** Dezena `00` mapeia para grupo 25 (VACA) — tratada como `100` internamente 🟢
- **RN-RJB-13** Após encerrar sorteio, trigger `trg_mv_sorteio_verifica_ganhadores` calcula premiados automaticamente 🟢
- **RN-RJB-14** Auditoria obrigatória em `register_log` para Salvar, Limpar e Encerrar 🟢

---

## Diferença com `ResultadoForm.php` atual

| Aspecto | PHP atual (`ResultadoForm.php`) | Java (`resultadojb`) |
|---|---|---|
| Número de prêmios | Fixo (5 ou configurável) | Dinâmico: 1–10 via `premiacaoMaxima` |
| Auto-display grupo/animal | Não implementado | Sim — ao digitar 4 dígitos |
| calculo_id (algoritmos) | Não suportado | 5 algoritmos distintos |
| Botão Sortear | Ausente | Presente para calculo_id ∈ {2,3,6} |
| Auto-completar prêmios 6–10 | Ausente | Presente para calculo_id=4 e 5 |
| `PERM_ALTERAR_SORTEIO_FECHADO` | Ausente | Presente |
| Fonte do combo extração | `cad_extracao` direta | `vwsorteio` (sorteios abertos do dia) |
| Foco automático entre prêmios | Ausente | Sim — ao atingir 4 dígitos |

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_sorteio` | Tabela de estado do sorteio (`numeros_sorteados`, `situacao`) |
| `cad_extracao` | `premiacao_maxima`, `calc_sorteio_id`, `gerar_restante` |
| `int_calculo_sorteio` | Define o algoritmo de resultado por extração |
| `vw_sorteio` | View de sorteios abertos usada no combo |
| `trg_mv_sorteio_verifica_ganhadores` | Trigger acionada ao encerrar o sorteio |
| `register_log` | Auditoria de cada ação |
| `PERM_ALTERAR_SORTEIO_FECHADO` | Permissão para editar sorteios fechados |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| premiacaoMaxima dinâmica (1–10 prêmios) | Presente | Ausente | 🔴 |
| Auto-display grupo + animal ao digitar | Presente | Ausente | 🔴 |
| calculo_id=4 (auto-completar 6–10) | Presente | Ausente | 🔴 |
| calculo_id=5 (soma/produto formato -DDD) | Presente | Ausente | 🔴 |
| Botão Sortear (calculo_id 2/3/6) | Presente | Ausente | 🔴 |
| `vwsorteio` como fonte do combo | Presente | Ausente (usa cad_extracao) | 🟡 |
| `PERM_ALTERAR_SORTEIO_FECHADO` | Presente | Ausente | 🔴 |
| Foco automático entre campos | Presente | Ausente | 🟡 |
| Validação formato -DDD para calculo_id=5 | Presente | Ausente | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Auto-display grupo e animal
Dado que extração tem premiacaoMaxima=5
Quando gestor digita "1234" no campo 1º Prêmio
Então campo Grupo exibe "12" e Descrição exibe "ELEFANTE"
E foco avança automaticamente para o 2º Prêmio

# calculo_id=4 — auto-gerar 6-10
Dado que extração tem calculo_id=4 e premiacaoMaxima=10
Quando gestor digita os 5 primeiros prêmios e confirma "gerar automaticamente"
Então prêmios 6-9 exibem dígitos transpostos dos primeiros 4
E prêmio 10 exibe últimos 4 dígitos da soma dos 9 primeiros

# calculo_id=5 — prêmios 6 e 7 com fórmula
Dado que extração tem calculo_id=5
Quando gestor tenta salvar com prêmio 6 = "1234" (sem prefixo -)
Então alerta "Digite um traço (-) e 3 números após!"

# calculo_id=6 — botão Sortear
Dado que extração tem calculo_id=6 e numeros_sorteados=''
Quando gestor clica "Sortear"
Então 5 milhares aleatórias são geradas com dezenas únicas (sem repetição)

# PERM_ALTERAR_SORTEIO_FECHADO — editar sorteio fechado
Dado que sorteio está fechado (situacao='F')
E usuário tem permissão PERM_ALTERAR_SORTEIO_FECHADO
Quando acessa a tela e busca o sorteio
Então botões Limpar e Salvar ficam habilitados

# Sem permissão — sorteio fechado
Dado que sorteio está fechado
E usuário não tem PERM_ALTERAR_SORTEIO_FECHADO nem é allsystem/Administrator
Quando acessa a tela
Então alerta "Sorteio encerrado!" e todos os campos ficam desabilitados

# Validação de hora
Dado que hora_sorteio é 15:00:00
Quando gestor tenta salvar às 14:59:59
Então alerta "O sorteio só pode ser lançado após às 15:00:00"

# Dezena 00 → grupo 25
Dado que gestor digita "1200" (dezena = 00)
Então grupo exibe "25" e descrição exibe "VACA"
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| premiacaoMaxima dinâmica (1–10 campos) | Must | Extrações configuradas com >5 prêmios não funcionam sem isso |
| Auto-display grupo + animal | Must | Validação visual essencial durante entrada de dados |
| `PERM_ALTERAR_SORTEIO_FECHADO` | Must | Correção de resultados sem precisar de acesso DB direto |
| calculo_id=4 (auto-completar 6–10) | Should | Agiliza entrada para extrações de 10 prêmios |
| calculo_id=5 (soma/produto) | Should | Necessário para tipos específicos de extração |
| Botão Sortear (calculo_id 2/3/6) | Should | Usado em extrações com sorteio automático |
| `vwsorteio` como fonte do combo | Should | Melhor UX — mostra apenas sorteios do dia |
| Foco automático entre campos | Could | UX — reduz tempo de entrada mas não bloqueia funcionalidade |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/resultadojb/resultadojb.component.html` | Java — layout com 10 campos de prêmio |
| `allsystem/.../webapp/app/entities/resultadojb/resultadojb.componet.ts` | Java — lógica de auto-geração e estados |
| `allsystem/.../webapp/app/entities/resultadojb/resultadojb.service.ts` | Java — `api/buscar`, `api/resultadojb`, `api/processarjb`, `api/limparjb`, `api/vwsorteio` |

**Lacunas a implementar no Zooloo `ResultadoForm.php`:**

| Item | Descrição |
|---|---|
| `premiacaoMaxima` dinâmica | Renderizar 1–10 campos de prêmio conforme `cad_extracao.premiacao_maxima` |
| Mapeamento grupo/animal | Lógica `chkGrupoDescricao()` — 25 grupos × dezenas |
| Foco automático | JavaScript ao digitar 4 dígitos |
| calculo_id lógica | PHP/JS para os 5 algoritmos |
| `PERM_ALTERAR_SORTEIO_FECHADO` | Nova permissão no sistema Adianti |
| `vwsorteio` integration | Usar view para populo do combo de extração |
