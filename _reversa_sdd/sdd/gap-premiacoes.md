# GAP: Premiações — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — tela presente no Java (allsystem) mas **não implementada** no Zooloo PHP

---

## Visão Geral

O módulo **Premiações** (`premiacaojb`) é a tela operacional central para visualizar e registrar o pagamento de prêmios de bilhetes de Jogo do Bicho. Permite filtrar bilhetes premiados por período, área, extração, vendedor e status de pagamento, e efetuar o pagamento individualmente (via modal de confirmação) ou em lote ("Pagar todos"). O pagamento registra log de auditoria com usuário e IP.

No Zooloo, a flag `sorteado_pago` existe na entidade `MovJbSorteio.php`, mas **não há tela web** para o gestor visualizar prêmios pendentes nem efetuar pagamentos.

---

## Estrutura de Dados Envolvida

### `mov_jb_sorteio` — campos relevantes

| Campo | Tipo | Significado |
|---|---|---|
| `jb_sorteio_id` | PK | ID do detalhe de sorteio |
| `jb_id` | FK `mov_jb` | NSU do bilhete |
| `sorteio_id` | FK `mov_sorteio` | Sorteio associado |
| `modalidade_id` | FK `cad_modalidade` | Modalidade apostada |
| `palpites` | varchar | Números apostados (CSV) |
| `colocao_inicial` / `colocao_final` | int | Colocações jogadas |
| `total_sorteio` | decimal | Valor apostado neste sorteio |
| `sorteado` | `S`/`N` | Calculado pela trigger de resultado |
| `sorteado_colocacao` | varchar | Colocação vencedora (CSV) |
| `sorteado_valor` | decimal | Valor do prêmio a pagar |
| `sorteado_pago` | `S`/`N` | **Flag de pagamento** |
| `sorteado_valor_pago` | decimal | Valor efetivamente pago |

### `mov_jb` — campos relevantes

| Campo | Tipo | Significado |
|---|---|---|
| `jb_id` | PK | NSU (6 dígitos zero-padded) |
| `bilhete_numero` | int | Poule (sequencial diário por vendedor) |
| `vendedor_id` | FK | Vendedor |
| `area_id` | FK | Área do vendedor |
| `data_hora` | timestamp | Data/hora do bilhete |
| `cancelado` | `S`/`N` | Bilhetes cancelados excluídos da premiação |

---

## Interface no Sistema Java (allsystem — referência)

### Filtros

| Filtro | Campo | Comportamento |
|---|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` | Pré-preenchido com data atual |
| Data Final | `mov_jb.data_hora <= dataFinal` | Pré-preenchido com data atual |
| Tipo | Combo dinâmico: JOGOS / QUININHA / SENINHA / BILHETINHO | Exibido somente se há mais de um tipo ativo (`cfg_parametros`) |
| Área | `mov_jb.area_id` | Gerente vê só a própria área; Admin vê com opção "TODAS" |
| Extração | `mov_sorteio.extracao_id` | TODAS disponível |
| Vendedor | `mov_jb.vendedor_id` | Recarregado ao mudar área via AJAX |
| Pagos | `mov_jb_sorteio.sorteado_pago` | Todos / Pagos (`S`) / Não Pagos (`N`) |
| NSU | `mov_jb.jb_id` = NSU | **Modo alternativo:** ao digitar NSU, desabilita todos os outros filtros |

**Lógica de modos de busca:**
- **Modo normal:** usuário preenche filtros e clica "Buscar"
- **Modo NSU:** ao digitar qualquer valor no campo NSU, o formulário inteiro é desabilitado e o botão muda para "Buscar NSU" — busca pelo bilhete específico

### Colunas

| Coluna | Dado | Notas |
|---|---|---|
| Extração | `cad_extracao.descricao` | |
| Data | `mov_jb.data_hora` | `dd/MM/yyyy HH:mm:ss` |
| Vendedor | `cad_vendedor.nome` | |
| NSU | `mov_jb.jb_id` | Zero-padded 6 dígitos: `("000000" + nsu).slice(-6)` |
| Palpite | `mov_jb_sorteio.palpites` | CSV de números (exibido com espaço entre eles) |
| Modalidade | `cad_modalidade.apresentacao` | |
| Apostado | `mov_jb_sorteio.total_sorteio` | Formatado como moeda BRL |
| Prêmio | `mov_jb_sorteio.sorteado_valor` | Formatado como moeda BRL |
| Colocação | `mov_jb_sorteio.sorteado_colocacao` | Ver tratamento especial por jogo |
| Pago | `mov_jb_sorteio.sorteado_pago` | "Não" / "Sim" — **link clicável** que abre modal de pagamento |

**Linha de totalização:** `SUM(total_sorteio)` e `SUM(sorteado_valor)` no rodapé da tabela.

### Tratamento especial de Colocação por tipo de jogo

| `int_jogo.jogo_id` | Jogo | Substituição de colocação |
|---|---|---|
| 25 | Quininha | `01` → `QUI`, `02` → `QUA`, `03` → `TER` |
| 27 | Seninha | `01` → `SEN`, `02` → `QUI`, `03` → `QUA` |
| demais | JB / outros | Exibe colocação numérica direta |

### Ações

| Ação | Condição | Descrição |
|---|---|---|
| Buscar | Sempre visível no modo normal | Executa filtros |
| Buscar NSU | Visível apenas quando NSU foi preenchido | Busca bilhete por NSU |
| Pagar todos | Visível quando há prêmios não pagos na lista | Paga em lote todos os prêmios listados |
| Pago (link) | Por linha — sempre clicável | Abre modal de confirmação de pagamento individual |

---

## Modal de Pagamento Individual (`PremiacaoJbPagaComponent`)

Abre como modal `size: 'sm'` com `backdrop: 'static'` (não fecha clicando fora).

### Validações antes de abrir o modal

| Condição | Resultado |
|---|---|
| Usuário sem `PERM_PAGAMENTO_PREMIO` | Alerta "Você não tem permissão para realizar pagamento!" — modal não abre |
| `sorteado_valor = 0` | Alerta "Não há prêmio para este jogo" — modal não abre |
| `sorteado_pago = 'S'` | Alerta "O prêmio já foi pago" — modal não abre |

### Conteúdo do modal

Título: "Deseja pagar está premiação?"

Exibe:
- Vendedor
- Cliente (`mov_jb.nome_cliente`)
- Pago: Não / Sim
- Prêmio: valor formatado BRL

Botão "Pagar Prêmiação" — desabilitado se `pago = 'S'`
Botão "Fechar"

### Fluxo de pagamento

```
1. Clique em "Pagar Prêmiação"
2. PUT api/premiacaojb/{userId}
   → UPDATE mov_jb_sorteio SET sorteado_pago='S', sorteado_valor_pago=sorteado_valor
3. INSERT register_log { acao: 'Premiações', historico: 'Pagar Prêmiação', usuario, ip }
4. Broadcast evento 'premiacaoListModification' → recarrega lista
5. Modal fecha; alerta sucesso "Premiação paga com sucesso!"
```

---

## Fluxo "Pagar Todos"

Aparece quando há pelo menos 1 prêmio não pago na listagem (especialmente após busca por NSU).

```
1. Clique em "Pagar todos"
2. Para CADA item da premiacao[]:
   PUT api/premiacaojb/{userId} com: {
     nsu, sorteioId, modalidadeId, palpites,
     ganhou_Colocacao_01..10
   }
3. Quando todos os PUTs concluírem → alerta sucesso
4. Botão "Pagar todos" some da tela
5. Broadcast 'pagarListModification' → recarrega lista via buscarNsu()
```

> **Observação:** `pagarTodos()` envia N requisições paralelas (uma por item) — não é uma operação atômica. 🟡

---

## Regras de Negócio

- **RN-PR-01** Apenas usuários com `PERM_PAGAMENTO_PREMIO` podem registrar pagamentos 🟢
- **RN-PR-02** Bilhetes com `sorteado_valor = 0` não têm prêmio — clique no "Pago" exibe alerta sem abrir modal 🟢
- **RN-PR-03** Prêmio já pago (`sorteado_pago = 'S'`) não pode ser pago novamente — botão desabilitado no modal 🟢
- **RN-PR-04** Área obrigatória para gerentes — sem seleção de área o botão Buscar retorna alerta 🟢
- **RN-PR-05** Admin pode ver todas as áreas — combo com opção "TODAS" 🟢
- **RN-PR-06** Busca por NSU desabilita todos os outros filtros — modos mutuamente exclusivos 🟢
- **RN-PR-07** Pagamento é auditado: registra `register_log` com usuário e IP 🟢
- **RN-PR-08** Colocações de Quininha e Seninha são exibidas com labels textuais (QUI/QUA/TER/SEN) 🟢
- **RN-PR-09** Combo de tipo (JB/Quininha/Seninha/Bilhetinho) só aparece se mais de um jogo estiver ativo em `cfg_parametros` 🟢
- **RN-PR-10** `pagarTodos()` é não-atômico — falha parcial possível se uma requisição falhar 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `mov_jb_sorteio` | Tabela fonte — `sorteado='S'` filtra premiados |
| `mov_jb` | JOIN para NSU, vendedor, área, data, cancelado |
| `mov_sorteio` | JOIN para extração, data do sorteio |
| `cad_vendedor` | Nome do vendedor |
| `cad_extracao` | Descrição da extração |
| `cad_modalidade` | Nome/apresentação da modalidade |
| `cfg_parametros` | Controla quais jogos aparecem no combo (ativo_jb, ativo_quininha, etc.) |
| `register_log` | Tabela de auditoria de pagamentos |
| `PERM_PAGAMENTO_PREMIO` | Permissão de sistema — controla acesso ao pagamento |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela de listagem de premiados | `premiacaojb` Angular | Ausente | 🔴 |
| Filtro por data / área / extração / vendedor / pagos | Presente | Ausente | 🔴 |
| Busca por NSU | `premiacaonsu` endpoint | Ausente | 🔴 |
| Modal de pagamento individual | `PremiacaoJbPagaComponent` | Ausente | 🔴 |
| Pagar todos em lote | `pagarTodos()` | Ausente | 🔴 |
| Auditoria de pagamento (`register_log`) | Presente | Ausente | 🔴 |
| Tratamento de colocação Quininha/Seninha | Presente | Ausente | 🔴 |
| Campo `sorteado_pago` na entidade PHP | Presente | Existe em `MovJbSorteio.php` ✅ | Parcial |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — listar prêmios não pagos
Dado que existem bilhetes premiados para 2026-04-30 na área 1
Quando gestor filtra por data=2026-04-30, área=1, pagos=Não Pagos e clica "Buscar"
Então tabela exibe: extração, data, vendedor, NSU, palpite, modalidade, apostado, prêmio, colocação, pago="Não"
E linha de totalização mostra soma de apostado e prêmio

# Happy path — pagar prêmio individual
Dado que existe premiação com sorteado_pago='N', sorteado_valor=50.00
Quando gestor clica em "Não" na coluna Pago
Então abre modal "Deseja pagar está premiação?"
E ao clicar "Pagar Prêmiação", sorteado_pago='S' é registrado
E log de auditoria criado com usuario + ip
E linha na tabela passa a mostrar "Sim"

# Falha — sem permissão
Dado que usuário não tem PERM_PAGAMENTO_PREMIO
Quando clica em "Não" na coluna Pago
Então alerta "Você não tem permissão para realizar pagamento!" sem abrir modal

# Falha — prêmio = 0
Dado que sorteado_valor=0 para o registro
Quando gestor clica no link Pago
Então alerta "Não há prêmio para este jogo"

# Modo NSU — busca
Dado que bilhete jb_id=123456 tem prêmios
Quando gestor digita NSU=123456 e clica "Buscar NSU"
Então lista exibe somente os prêmios deste bilhete
E botão "Pagar todos" aparece se houver prêmios não pagos

# Pagar todos
Dado que busca NSU retornou 3 prêmios não pagos
Quando gestor clica "Pagar todos"
Então os 3 prêmios são marcados como pagos
E botão "Pagar todos" some

# Controle de acesso — gerente
Dado que usuário é gerente da área 2
Quando acessa a tela de Premiações
Então combo de área exibe somente a área 2 sem opção "TODAS"
E busca sem selecionar área retorna alerta "Selecione uma área!"

# Tratamento Quininha
Dado que premiação é de jogo_id=25 (Quininha) com sorteado_colocacao='01,02'
Quando tabela é carregada
Então coluna Colocação exibe "QUI QUA"
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Tela de listagem de premiados | Must | Controle operacional diário de pagamentos |
| Filtro por data / área / extração / vendedor / pagos | Must | Navegação gerencial básica |
| Modal de pagamento individual | Must | Fluxo operacional crítico |
| Pagar todos em lote (por NSU) | Must | Eficiência operacional — bilhetes com múltiplos sorteios |
| Busca por NSU | Must | Verificação de bilhete específico |
| Auditoria de pagamento (`register_log`) | Must | Rastreabilidade — exigência operacional |
| Tratamento de colocação Quininha/Seninha | Should | Legibilidade para o operador |
| Controle de acesso por perfil Gerente | Must | Segurança de dados |
| Combo dinâmico de tipo por cfg_parametros | Should | Só relevante se múltiplos jogos ativos |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/premiacaojb/premiacaojb.component.html` | Java |
| `allsystem/.../webapp/app/entities/premiacaojb/premiacaojb.component.ts` | Java |
| `allsystem/.../webapp/app/entities/premiacaojb/premiacaojb-paga.component.html` | Java |
| `allsystem/.../webapp/app/entities/premiacaojb/premiacaojb-paga.component.ts` | Java |
| `allsystem/.../webapp/app/entities/premiacaojb/premiacaojb.service.ts` | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/premiacao/PremiacaoList.php` | Lista com filtros + totalização + link de pagamento |
| `app/control/premiacao/PremiacaoForm.php` | Modal de confirmação de pagamento (individual) |
| `app/service/rest/PremiacaoRestService.php` | REST endpoint para pagamento via app móvel (futuro) |
