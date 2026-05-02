# GAP: Movimentos — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — entidades PHP existem mas sem telas administrativas no Zooloo

---

## Visão Geral

O módulo **Movimentos** abrange todas as entidades de apostas registradas pelo app móvel: bilhetes JB (`mov_jb`), detalhe por sorteio (`mov_jb_sorteio`) e detalhe por palpite (`mov_jb_sort_palpite`). O sistema Java (allsystem) possuía telas completas de consulta e gerenciamento operacional dessas tabelas. O Zooloo tem as entidades PHP criadas e usadas internamente pelo `BilheteRestService`, mas **não tem nenhuma tela web** para o gestor consultar, anular ou gerenciar bilhetes manualmente.

---

## Estrutura de Dados

### `mov_jb` — Cabeçalho do Bilhete JB

| Campo | Tipo | Notas |
|---|---|---|
| `jb_id` | PK (IDPOLICY max) | ID do bilhete |
| `area_id` | FK `cad_area` | Área do vendedor na hora da aposta |
| `coletor_id` | FK `cad_coletor` | Coletor do vendedor |
| `terminal_id` | FK `cad_terminal` | Dispositivo usado |
| `vendedor_id` | FK `cad_vendedor` | Vendedor que registrou |
| `bilhete_numero` | int | Número sequencial diário por vendedor |
| `sorteios_ids` | varchar | IDs dos sorteios apostados (CSV) |
| `sorteios_quantidade` | int | Contagem de sorteios |
| `data_hora` | timestamp | Momento do registro |
| `data_hora_servidor` | timestamp | Hora do servidor (pode diferir do dispositivo) |
| `nome_cliente` | varchar | Opcional |
| `fone_cliente` | varchar | Opcional |
| `total_bilhete` | decimal | Soma total apostada |
| `comissao_valor` | decimal | Comissão calculada (trigger/job) |
| `comissao_pago` | `S`/`N` | Comissão já paga ao vendedor |
| `string_autorizacao` | varchar | Código `date('ymd') + 6 chars md5(uniqid)` |
| `cancelado` | `S`/`N` | Bilhete cancelado |
| `cancelado_motivo` | varchar | Motivo do cancelamento |
| `data_cancelamento` | timestamp | Quando foi cancelado |
| `reimpressao` | int | Contador de reimpressões |
| `data_reimpressao` | timestamp | Última reimpressão |

### `mov_jb_sorteio` — Detalhe por Sorteio × Modalidade

| Campo | Tipo | Notas |
|---|---|---|
| `jb_sorteio_id` | PK (IDPOLICY max) | |
| `jb_id` | FK `mov_jb` | |
| `sorteio_id` | FK `mov_sorteio` | |
| `modalidade_id` | FK `cad_modalidade` | |
| `palpites` | varchar | CSV dos números apostados |
| `palpites_quantidade` | int | Contagem de palpites |
| `colocao_inicial` | int | Primeira colocação jogada |
| `colocao_final` | int | Última colocação jogada |
| `valor_palpites` | decimal | Valor por palpite |
| `total_sorteio` | decimal | `valor_palpites × palpites_quantidade` |
| `comissao_sorteio` | decimal | `0` no registro; calculado por trigger |
| `sorteado` | `S`/`N` | Preenchido pela trigger de resultado |
| `sorteado_colocacao` | varchar | Colocação em que ganhou |
| `sorteado_valor` | decimal | Valor do prêmio |
| `sorteado_pago` | `S`/`N` | Prêmio já pago |
| `previsao_premio` | decimal | Previsão calculada |
| `sorteado_valor_pago` | decimal | Valor efetivamente pago |

### `mov_jb_sort_palpite` — Detalhe por Número Apostado

| Campo | Tipo | Notas |
|---|---|---|
| `jb_palpites_id` | PK (IDPOLICY max) | |
| `jb_sorteio_id` | FK `mov_jb_sorteio` | |
| `jb_id` | FK `mov_jb` | |
| `sorteio_id` | FK `mov_sorteio` | |
| `modalidade_id` | FK `cad_modalidade` | |
| `palpite` | varchar | Número apostado (4 dígitos) |
| `valor_palpite` | decimal | Valor desta aposta |
| `jogou_colocacao_01..10` | `S`/`N` | Flags de colocações jogadas |
| `premio_colocacao_01..05` | decimal | Prêmio por colocação (triggers preenchem) |
| `ganhou_premio_total` | decimal | Total ganho |
| `pago_premio_total` | `S`/`N` | Prêmio pago |

---

## Tela Java de Consulta de Bilhetes (`bilhete`)

### Filtros

| Filtro | Campo |
|---|---|
| Data Inicial | `mov_jb.data_hora >= dataInicial` |
| Área | `mov_jb.area_id = X` |
| Extração | `mov_jb_sorteio.sorteio → extracao_id = Y` |

### Colunas exibidas

| Coluna | Dado | Notas |
|---|---|---|
| Extração | `extracao.descricao` | |
| Vendedor | `vendedor.nome` | |
| NSU | `jb_id` zero-padded 6 dígitos | NSU = número sequencial único |
| Poule | `bilhete_numero` zero-padded 6 dígitos | Número diário do vendedor |
| Data | `data_hora` | `dd/MM/yyyy HH:mm:ss` |
| Apostado | `modalidade + palpites` | Combinado por sorteio |
| Total Apostado | `total_sorteio` | Por linha de jogo |
| Total Geral | `total_bilhete` | Linha de totalização |

---

## Responsabilidades Faltantes no Zooloo

| Responsabilidade | Status |
|---|---|
| Registrar bilhete via API | ✅ `BilheteRestService::registrar` |
| Cancelar bilhete via API | ✅ `BilheteRestService::cancelar` |
| Detalhe/reimpressão via API | ✅ `BilheteRestService::detalhe` |
| Lista de bilhetes do vendedor via API | ✅ `BilheteRestService::lista` |
| Consulta gerencial de bilhetes (all vendedores) | 🔴 Ausente |
| Cancelamento administrativo (gestor cancela bilhete) | 🔴 Ausente |
| Visualização de detalhes de `mov_jb_sorteio` | 🔴 Ausente |
| Visualização de `mov_jb_sort_palpite` | 🔴 Ausente |
| Estorno de cancelamento | 🔴 Ausente |

---

## Regras de Negócio

- **RN-MV-01** `mov_jb` é criado atomicamente com `mov_jb_sorteio` e `mov_jb_sort_palpite` em uma única transação 🟢
- **RN-MV-02** `cancelado='S'` bloqueia o bilhete mas não o exclui — histórico preservado 🟢
- **RN-MV-03** `comissao_sorteio` em `mov_jb_sorteio` é inicializado em 0 — trigger ou job calcula posteriormente 🟡
- **RN-MV-04** `sorteado`, `sorteado_valor`, `premio_colocacao_*` são preenchidos pelas triggers de resultado — nunca pelo PHP 🟢
- **RN-MV-05** `string_autorizacao` é gerado no registro e não pode ser alterado depois 🟢
- **RN-MV-06** `bilhete_numero` é sequencial por vendedor por dia (`MAX+1` para o dia corrente) 🟢
- **RN-MV-07** `data_hora_servidor` registra o timestamp do servidor — pode diferir de `data_hora` (dispositivo) 🟡
- **RN-MV-08** Bilhetes de sorteios encerrados não podem mais ser cancelados pelo vendedor (sorteio `situacao='F'`), mas podem ser anulados administrativamente 🟡

---

## Fluxo de Dados (ciclo de vida do bilhete)

```
1. App → BilheteRestService::registrar
   INSERT mov_jb + mov_jb_sorteio + mov_jb_sort_palpite

2. Sorteio encerrado (ResultadoForm::onConfirmCloseDraw)
   UPDATE mov_sorteio SET situacao='F'
   → trigger trg_mv_sorteio_verifica_ganhadores
     UPDATE mov_jb_sorteio SET sorteado='S', sorteado_valor=X, sorteado_colocacao=Y
     UPDATE mov_jb_sort_palpite SET premio_colocacao_*=X, ganhou_premio_total=X

3. Pagamento de prêmio (não implementado no Zooloo)
   UPDATE mov_jb_sorteio SET sorteado_pago='S', sorteado_valor_pago=X

4. Cancelamento (vendedor via API ou gestor via tela)
   UPDATE mov_jb SET cancelado='S', data_cancelamento=now(), cancelado_motivo=X
```

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Entidade `MovJb.php` | `MovJb.java` (inferido) | Existe ✅ | — |
| Entidade `MovJbSorteio.php` | Existe | Existe ✅ | — |
| Entidade `MovJbSortPalpite.php` | Existe | Existe ✅ | — |
| Tela de consulta de bilhetes | `bilhete` Angular | Ausente | 🔴 |
| Filtro por área + extração | Presente | Ausente | 🔴 |
| Cancelamento administrativo | Implícito | Ausente | 🔴 |
| Relatório de apuração por extração | `apuracao`/`apuracaojb` Angular | Ausente | 🔴 |
| Pagamento de prêmios | `premiacaojb` Angular | Ausente | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — consultar bilhetes (gestor)
Dado que existem bilhetes para data=2026-04-30, area_id=1
Quando o gestor acessa BilheteList e filtra por data e área
Então lista exibe extração, vendedor, NSU, poule, data, apostado, total
E linha de totalização exibe soma de total_bilhete

# Happy path — visualizar detalhe de bilhete
Quando o gestor clica em um bilhete
Então exibe mov_jb_sorteio (modalidade, palpites, colocações, valor, sorteado, prêmio)
E exibe mov_jb_sort_palpite (palpite por palpite, colocações jogadas, prêmios)

# Happy path — cancelamento administrativo
Dado que jb_id=50 não foi premiado
Quando gestor cancela com motivo="Erro de digitação"
Então mov_jb.cancelado='S', cancelado_motivo registrado

# Lacuna — pagamento de prêmio
Dado que mov_jb_sorteio.sorteado='S' e sorteado_pago='N'
Quando gestor tenta marcar como pago
Então não há tela disponível (lacuna)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Tela de consulta de bilhetes (gestor) | Must | Operação diária — gestor precisa verificar apostas |
| Filtro por data / área / extração / vendedor | Must | Navegação gerencial básica |
| Detalhe do bilhete (sorteios + palpites) | Must | Rastreabilidade completa |
| Cancelamento administrativo de bilhetes | Must | Correção de erros operacionais |
| Visualização de bilhetes premiados | Must | Base para pagamento de prêmios |
| Pagamento de prêmios (marcar sorteado_pago) | Must | Fluxo operacional crítico |
| Exportação CSV/PDF da lista | Should | Auditoria e controle |
| Estorno de cancelamento | Could | Raro; pode ser feito via banco |

---

## Rastreabilidade de Código

| Arquivo | Classe | Sistema |
|---|---|---|
| `app/model/entities/MovJb.php` | `MovJb` → `mov_jb` | Zooloo ✅ |
| `app/model/entities/MovJbSorteio.php` | `MovJbSorteio` → `mov_jb_sorteio` | Zooloo ✅ |
| `app/model/entities/MovJbSortPalpite.php` | `MovJbSortPalpite` → `mov_jb_sort_palpite` | Zooloo ✅ |
| `app/service/rest/BilheteRestService.php` | CRUD via API | Zooloo ✅ |
| `allsystem/.../webapp/app/entities/bilhete/` | Consulta de bilhetes JB | Java |
| `allsystem/.../webapp/app/entities/apuracaojb/` | Apuração por sorteio | Java |
| `allsystem/.../webapp/app/entities/premiacaojb/` | Premiações JB | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/bilhete/BilheteList.php` | Lista gerencial com filtro data/área/extração/vendedor |
| `app/control/bilhete/BilheteDetail.php` | Detalhe de bilhete com sorteios e palpites |
| `app/control/bilhete/BilheteForm.php` | Cancelamento + visualização (readonly com ação de cancelar) |
| `app/control/premiacao/PremiacaoList.php` | Lista de bilhetes premiados com opção de marcar pago |
