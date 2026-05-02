# Relatório de Confiança — Zooloo

> Gerado pelo Revisor em 2026-05-01
> Revisão cruzada: não realizada (Codex indisponível)
> Reclassificações: 6 realizadas durante esta revisão

---

## Resumo Geral

> Atualizado após processamento de 15 respostas do usuário em 2026-05-01

| Nível | Antes | Após revisão | Δ |
|---|---|---|---|
| 🟢 CONFIRMADO | 323 | 340 | +17 |
| 🟡 INFERIDO | 141 | 148 | +7 |
| 🔴 LACUNA | 110 | 86 | -24 |
| **Total** | **574** | **574** | — |

**Confiança geral: 75%** *(340 + 74) / 574 = 414 / 574*

> Fórmula: (🟢 + 🟡 × 0,5) / total × 100  
> Ganho de confiança: +7 pontos (68% → 75%)

---

## Por Spec

| Spec | 🟢 | 🟡 | 🔴 | Confiança |
|---|---|---|---|---|
| `flowcharts/area.md` | 12 | 2 | 0 | 94% |
| `flowcharts/gerente.md` | 10 | 3 | 2 | 77% |
| `flowcharts/extracao.md` | 11 | 3 | 1 | 83% |
| `flowcharts/modalidade.md` | 10 | 4 | 1 | 82% |
| `flowcharts/vendedor.md` | 12 | 3 | 1 | 85% |
| `flowcharts/area-cotacao.md` | 8 | 3 | 0 | 91% |
| `flowcharts/area-extracao.md` | 9 | 2 | 0 | 94% |
| `flowcharts/area-limite.md` | 8 | 3 | 1 | 79% |
| `flowcharts/area-comissao-modalidade.md` | 7 | 3 | 1 | 78% |
| `flowcharts/extracao-descarga.md` | 8 | 2 | 1 | 82% |
| `flowcharts/palpite-cotado.md` | 9 | 2 | 0 | 94% |
| `flowcharts/parametros.md` | 8 | 3 | 1 | 79% |
| `flowcharts/resultado.md` | 10 | 2 | 3 | 73% |
| `flowcharts/rest-api.md` | 12 | 4 | 3 | 74% |
| `adrs/001-reescrita-java-para-php.md` | 6 | 1 | 0 | 96% |
| `adrs/002-jwt-para-api-movel.md` | 5 | 1 | 2 | 69% |
| `adrs/003-md5-para-senhas.md` | 5 | 0 | 1 | 83% |
| `adrs/004-trigger-para-calculo-premios.md` | 5 | 1 | 1 | 79% |
| `adrs/005-insert-delete-para-area-extracao.md` | 6 | 0 | 0 | 100% |
| `adrs/006-dual-entity-usuario-gerente-vendedor.md` | 5 | 1 | 1 | 79% |
| `architecture.md` | 10 | 3 | 1 | 82% |
| `c4-context.md` | 8 | 2 | 0 | 94% |
| `c4-containers.md` | 7 | 3 | 1 | 79% |
| `c4-components.md` | 8 | 4 | 1 | 77% |
| `erd-complete.md` | 15 | 4 | 2 | 81% |
| `state-machines.md` | 10 | 3 | 1 | 82% |
| `permissions.md` | 8 | 4 | 2 | 71% |
| `domain.md` | 5 | 4 | 2 | 64% |
| `traceability/spec-impact-matrix.md` | 12 | 5 | 0 | 91% |
| `traceability/code-spec-matrix.md` | 18 | 4 | 5 | 74% |
| `openapi/spec-resultado.yaml` | 10 | 3 | 2 | 77% |
| `openapi/spec-descarrego.yaml` | 12 | 5 | 3 | 72% |
| `openapi/spec-relatorios-vendas.yaml` | 13 | 5 | 2 | 75% |
| `openapi/spec-relatorios-financeiros.yaml` | 12 | 6 | 2 | 75% |
| `openapi/spec-jogos-especiais.yaml` | 14 | 7 | 4 | 70% |
| `user-stories/us-cadastros.md` | 20 | 5 | 5 | 75% |
| `user-stories/us-configuracoes.md` | 24 | 8 | 4 | 78% |
| `user-stories/us-resultado-sorteio.md` | 18 | 4 | 5 | 74% |
| `user-stories/us-operacional.md` | 20 | 8 | 8 | 67% |
| `user-stories/us-app-movel.md` | 15 | 6 | 12 | 58% |
| `user-stories/us-jogos-especiais.md` | 12 | 8 | 6 | 62% |

---

## Specs com Confiança Crítica (< 65%)

| Spec | Confiança | Principal causa |
|---|---|---|
| `user-stories/us-app-movel.md` | 58% | Body/response do BilheteRestService incorretos; `expires_at` vs `expires_in`; Bilhetinho GAP |
| `user-stories/us-jogos-especiais.md` | 62% | Bilhetinho sem AR e sem RestService; fluxo de apostas dos jogos especiais via app 🟡 |
| `domain.md` | 64% | Arquivo de baixa densidade — regras detalhadas distribuídas em outros artefatos |

---

## Lacunas Pendentes 🔴

Itens que permaneceram como 🔴 após a revisão (aguardando resposta do usuário em `questions.md`):

### API REST (BilheteRestService)
- Body real do `registrar()` não documentado — Pergunta 1 e 2
- Response real do `registrar()` não documentado — Pergunta 1
- `expires_at` vs `expires_in` no login — Pergunta 3
- `getToken()` legado — Pergunta 4
- `pode_cancelar_tempo` e `pode_cancelar_qtde` não documentados — Pergunta 9

### Banco / Triggers
- Trigger de comissão em `mov_jb_sorteio` INSERT não documentada — Pergunta 6
- `colocao_inicial` typo ou nome real da coluna — Pergunta 7
- Regra para colocações 6-10 em `MovJbSortPalpite` — Pergunta 8

### Formulários / UI
- `multiplicadorColocacao02..05` não editáveis no `ModalidadeForm` — Pergunta 5

### Segurança
- refreshToken com tokens expirados (confirmado no código) — risco real, sem plano de fix
- MD5 sem plano de migração — Pergunta 10

### Gaps de negócio
- `BilhetinhoRestService` sem prazo — Pergunta 12
- `PERM_ALTERAR_SORTEIO_FECHADO` — Pergunta 11
- `ResultadoList` vs `vwsorteio` — Pergunta 13
- Grade de Comissão Won't definitivo — Pergunta 14
- `ResultadoForm` sem validação de hora — Pergunta 15

---

## Histórico de Reclassificações

### Rodada 1 — Revisão de código (sem perguntas ao usuário)

| De | Para | Afirmação | Evidência |
|---|---|---|---|
| 🔴 | 🟢 | `Modalidade.php` não mapeia `multiplicadorColocacao04+05` | `Modalidade.php:30-31` — ambos mapeados |
| 🔴 | 🟢 | `comissao_sorteio` calculado em PHP no registro | `BilheteRestService.php:131` — `= 0; // trigger calcula` |
| 🟡 | 🟢 | `logout` invalida token no servidor | `ApplicationAuthenticationRestService.php:217-263` — apenas registra log |
| 🟡 | 🔴 | login retorna `expires_in: 3600` | `ApplicationAuthenticationRestService.php:88-89` — retorna `expires_at` |
| 🟡 | 🟢 | refreshToken não valida expiração do token atual | `ApplicationAuthenticationRestService.php:163-215` — confirmado sem checagem |
| 🟢 | 🔴 | ModalidadeForm expõe todos os multiplicadores de colocação | `ModalidadeForm.php:53` — apenas campo 01 na UI |

### Rodada 2 — Respostas do usuário (15 perguntas)

| De | Para | Afirmação | Resposta/Evidência |
|---|---|---|---|
| 🔴 | 🟢 | `string_autorizacao` é um hash do `jb_id` | Confirmado: MD5 derivado do `jb_id` |
| 🔴 | 🟢 | Body do `BilheteRestService::registrar` | Confirmado: usar o código real (`jogos[]`) |
| 🔴 | 🟡 | Login retorna `expires_at` — app usará padrão JWT | App será criado depois; seguirá padrão JWT |
| 🔴 | 🟢 | `colocao_inicial`/`colocao_final` — nome real da coluna | Confirmado: nome real no banco sem 'c' |
| 🔴 | 🟢 | `pode_cancelar_tempo`/`pode_cancelar_qtde` existem em `cad_vendedor` | Confirmado: campos já existem |
| 🔴 | 🟢 | Trigger `trg_mv_jb_sorteio_comissao` calcula comissão | DB query: trigger confirmada + lógica de hierarquia |
| 🔴 | 🟢 | Trigger `trg_mv_jb_sorteio_previsao` calcula `previsao_premio` | DB query: trigger confirmada |
| 🔴 | 🟢 | Trigger `trg_mv_jb_sorteio_instantaneo` para MINST | DB query: trigger confirmada |
| 🔴 | 🟢 | Trigger `trg_mv_jb` seta `data_hora_servidor` | DB query: trigger confirmada |
| 🔴 | 🟢 | Migração para bcrypt — decisão tomada | Stakeholder confirmou: implementar |
| 🔴 | 🟡 | `colocacoes 1-10` — regra de negócio para colocações 6-10 | "depende da configuração do sistema" |
| 🔴 | 🟡 | `multiplicadorColocacao01` apenas por design (não bug) | Confirmado: uso futuro para 02..05 |
| 🔴 | 🟡 | PERM_ALTERAR_SORTEIO_FECHADO — usar permissão Adianti | Não existe; usar permissão Adianti a definir |
| 🔴 | 🟡 | `vw_sorteio` — view existe no legado | Confirmado: existe no banco `jb`, falta criar no `applications` |
| 🔴 | 🔴 | `getToken()` legado | Não usado; remover das specs |
| 🔴 | 🔴 | Bilhetinho sem previsão de implementação | Won't por enquanto |
| 🔴 | 🔴 | Grade de Comissão — mudou de Won't → Should | A implementar após módulos Must |

---

## Recomendações

### Resolvidos durante a revisão ✅
- [x] Specs `us-app-movel.md` corrigidas (body/response BilheteRestService, expires_at, cancelamento)
- [x] Triggers de comissão, previsão, instantâneo e datahora documentadas em `flowcharts/rest-api.md`
- [x] ADR-003 atualizado com decisão de migração para bcrypt
- [x] Grade de Comissão (US-CFG-08) promovida de Won't → Should
- [x] `hora_limite` (apostas) vs `hora_sorteio` (resultado) clarificados nas specs
- [x] `vw_sorteio` — origem confirmada (banco `jb`), falta criar no `applications`

### Pendentes de implementação (backlog)

- [ ] **[SEGURANÇA 🔴]** Migrar MD5 → bcrypt em `GerenteForm`, `VendedorForm`, `ApplicationAuthenticationService` (decisão aprovada)
- [ ] **[SEGURANÇA 🔴]** Corrigir `refreshToken` para rejeitar tokens com `expires < now()` (vulnerabilidade confirmada)
- [ ] **[NEGÓCIO 🔴]** Adicionar validação de `hora_sorteio` em `ResultadoForm::onSave()` (TODO do CLAUDE.md)
- [ ] **[NEGÓCIO 🔴]** Implementar `BilhetinhoRestService` quando programado (atualmente Won't)
- [ ] **[NEGÓCIO 🟡]** Implementar Grade de Comissão (`cfg_grade_comissao`) — Should
- [ ] **[UX 🟡]** Criar `vw_sorteio` no banco `applications` e corrigir `ResultadoList` para usá-la
- [ ] **[DOCS 🟡]** Documentar regra de negócio para colocações 6-10 em `MovJbSortPalpite`
- [ ] **[DOCS 🟡]** Definir permissão Adianti para reabrir sorteio fechado (substitui PERM_ALTERAR_SORTEIO_FECHADO)
