# Reconstruction Plan — zooloo

**Stack:** PHP 8.2 + Adianti Framework 8.1 + PostgreSQL 15
**Gerado em:** 2026-05-02
**Status:** 27 tarefas | 22 concluídas | 4 em revisão | 1 pendente

> **Contexto:** O sistema zooloo já existe parcialmente. Este plano marca as tarefas concluídas
> (código funcional e coberto pelas specs) e lista as pendentes em ordem bottom-up.
> A cada execução, marque a tarefa como `done` ao concluir.

---

## Alertas de pré-voo

> Revise estes pontos antes de iniciar. Gaps marcados com ⚠️ bloqueiam a tarefa associada.

- ⚠️ **GAP-04** — `refreshToken` aceita tokens expirados (sem verificação de `$decoded['expires'] < time()`) — bloqueia Tarefa 20 (segurança crítica antes de ir a produção)
- ⚠️ **GAP-10 / ADR-003** — MD5 sem salt para senhas — bloqueia Tarefas 07 e 08 em produção (migração para bcrypt aprovada em 2026-05-01)
- ⚠️ **GAP-07** — `ResultadoForm::onSave()` sem validação de `hora_sorteio` — bloqueia Tarefa 16 (TODO documentado no CLAUDE.md)
- ⚠️ **GAP-02** — `BilhetinhoRestService` não implementado — bloqueia Tarefa 25 (Won't por enquanto; ativar quando `cfg_parametros.ativo_bilhetinho=true` for necessário)
- ⚠️ **GAP-11** — `ResultadoList.php` usa `cad_extracao` em vez de `vw_sorteio` — bloqueia Tarefa 18 (view precisa ser criada no banco `applications` primeiro — Tarefa 01)

---

## Tarefas

### Tarefa 01 — View vw_sorteio no banco applications
**Status:** done
**Lê:** `_reversa_sdd/flowcharts/resultado.md`, `_reversa_sdd/user-stories/us-resultado-sorteio.md` (US-RES-05)
**Constrói:** DDL — `CREATE VIEW vw_sorteio` no banco `applications` (espelhando a view do banco `jb`)
**Pronto quando:** `SELECT * FROM vw_sorteio WHERE data_sorteio = CURRENT_DATE` retorna apenas sorteios abertos do dia

---

### Tarefa 02 — Active Records ausentes (Bilhetinho)
**Status:** done
**Lê:** `_reversa_sdd/data-dictionary.md`, `_reversa_sdd/user-stories/us-jogos-especiais.md` (US-JE-01)
**Constrói:** `app/model/entities/CadModalidadeBilhetinho.php`, `app/model/entities/MovBilhetinho.php`, `app/model/entities/MovBilhetinhoSorteio.php`
**Pronto quando:** As 3 entidades estendem `TRecord` com `TABLENAME`, `PRIMARYKEY` e `IDPOLICY='max'` corretos
**Alerta:** ⚠️ Bilhetinho é Won't por enquanto — implementar ARs aqui mas não expor na UI/API ainda

---

### Tarefa 03 — Bugs confirmados: GerenteForm + ExtracaoForm
**Status:** done
**Lê:** `_reversa_sdd/flowcharts/gerente.md`, `_reversa_sdd/flowcharts/extracao.md`
**Constrói:** Fix em `app/control/gerente/GerenteForm.php` (typo `'form_ferente'`) e `app/control/extracao/ExtracaoForm.php` (método `cleat()` inexistente)
**Pronto quando:** Salvar um Gerente atualiza o `SystemUser` correspondente sem erro; Salvar uma Extração limpa o form corretamente

---

### Tarefa 04 — Módulo Área
**Status:** done
**Lê:** `_reversa_sdd/sdd/area.md`, `_reversa_sdd/user-stories/us-cadastros.md` (US-CAD-01)
**Constrói:** `AreaForm.php`, `AreaList.php`, `Area.php` (entity)
**Pronto quando:** CRUD completo de Área com ativo S/N e filtro por descrição funcionam

---

### Tarefa 05 — Módulo Modalidade
**Status:** done
**Lê:** `_reversa_sdd/sdd/modalidade.md`, `_reversa_sdd/user-stories/us-cadastros.md` (US-CAD-04), `_reversa_sdd/user-stories/us-jogos-especiais.md` (US-JE-05)
**Constrói:** `ModalidadeForm.php`, `ModalidadeList.php`, `Modalidade.php` (entity)
**Pronto quando:** CRUD de Modalidade funciona; entidade mapeia todos os 5 `multiplicador_colocacao_01..05`
**Obs:** Campo UI expõe apenas `multiplicadorColocacao01` — comportamento intencional (US-JE-05 🟡)

---

### Tarefa 06 — Módulo Extração
**Status:** review
**Lê:** `_reversa_sdd/sdd/extracao.md`, `_reversa_sdd/user-stories/us-cadastros.md` (US-CAD-03)
**Constrói:** `ExtracaoForm.php`, `ExtracaoList.php`, `Extracao.php` (entity)
**Pronto quando:** CRUD de Extração funciona; bug `cleat()` corrigido (ver Tarefa 03)
**Alerta:** Bug confirmado `$this->form->cleat()` — corrigido na Tarefa 03

---

### Tarefa 07 — Módulo Gerente
**Status:** review
**Lê:** `_reversa_sdd/sdd/gerente.md`, `_reversa_sdd/user-stories/us-cadastros.md` (US-CAD-02), `_reversa_sdd/adrs/006-dual-entity-usuario-gerente-vendedor.md`
**Constrói:** `GerenteForm.php`, `GerenteList.php`, `Gerente.php` (entity)
**Pronto quando:** Dual-Entity (SystemUser + Gerente) criada atomicamente; bug typo corrigido (Tarefa 03); senha via bcrypt (Tarefa 19)
**Alerta:** ⚠️ Senha MD5 — migrar para bcrypt na Tarefa 19 antes de produção

---

### Tarefa 08 — Módulo Vendedor
**Status:** review
**Lê:** `_reversa_sdd/sdd/vendedor.md`, `_reversa_sdd/user-stories/us-cadastros.md` (US-CAD-05), `_reversa_sdd/adrs/006-dual-entity-usuario-gerente-vendedor.md`
**Constrói:** `VendedorForm.php`, `VendedorList.php`, `Vendedor.php` (entity)
**Pronto quando:** Dual-Entity (SystemUser + Vendedor) criada com `pode_cancelar_tempo` e `pode_cancelar_qtde` mapeados; senha via bcrypt (Tarefa 19)
**Alerta:** ⚠️ Senha MD5 — migrar para bcrypt na Tarefa 19 antes de produção

---

### Tarefa 09 — Módulo Área Extração
**Status:** done
**Lê:** `_reversa_sdd/sdd/area-extracao.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-01), `_reversa_sdd/adrs/005-insert-delete-para-area-extracao.md`
**Constrói:** `AreaExtracaoList.php`, `AreaExtracao.php` (entity)
**Pronto quando:** Ativar/desativar extração por área via INSERT/DELETE (sem soft delete)

---

### Tarefa 10 — Módulo Área Cotação
**Status:** done
**Lê:** `_reversa_sdd/sdd/area-cotacao.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-02)
**Constrói:** `AreaCotacaoForm.php`, `AreaCotacaoList.php`, `AreaCotacao.php`
**Pronto quando:** CRUD de cotação por área/extração/modalidade funciona com unicidade (area_id + extracao_id + modalidade_id)

---

### Tarefa 11 — Módulo Área Limite
**Status:** done
**Lê:** `_reversa_sdd/sdd/area-limite.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-03)
**Constrói:** `AreaLimiteForm.php`, `AreaLimiteList.php`, `AreaLimite.php`
**Pronto quando:** CRUD de limite por área/modalidade; unicidade validada no create

---

### Tarefa 12 — Módulo Área Comissão Modalidade
**Status:** done
**Lê:** `_reversa_sdd/sdd/area-comissao-modalidade.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-04)
**Constrói:** `AreaComissaoModalidadeForm.php`, `AreaComissaoModalidadeList.php`, `AreaComissaoModalidade.php`
**Pronto quando:** CRUD de comissão por área/modalidade funciona

---

### Tarefa 13 — Módulo Palpite Cotado
**Status:** done
**Lê:** `_reversa_sdd/sdd/palpite-cotado.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-05)
**Constrói:** `PalpiteCotadoForm.php`, `PalpiteCotadoList.php`, `PalpiteCotado.php`
**Pronto quando:** CRUD de cotação especial por palpite específico funciona

---

### Tarefa 14 — Módulo Extração Descarga
**Status:** done
**Lê:** `_reversa_sdd/sdd/extracao-descarga.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-06)
**Constrói:** `ExtracaoDescargaForm.php`, `ExtracaoDescargaList.php`, `ExtracaoDescarga.php`
**Pronto quando:** CRUD de limite de descarga por extração/modalidade funciona

---

### Tarefa 15 — Módulo Parâmetros
**Status:** done
**Lê:** `_reversa_sdd/sdd/parametros.md`, `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-07)
**Constrói:** `ParametrosForm.php`, `ParametrosList.php`, `Parametros.php`
**Pronto quando:** Parâmetros globais (nome_banca, features) editáveis

---

### Tarefa 16 — Resultado JB: validação de hora_sorteio
**Status:** done
**Lê:** `_reversa_sdd/sdd/resultado.md`, `_reversa_sdd/user-stories/us-resultado-sorteio.md` (US-RES-01), `_reversa_sdd/flowcharts/resultado.md`
**Constrói:** Fix em `app/control/resultado/ResultadoForm.php::onSave()` — validar `hora_atual >= hora_sorteio` antes de gravar
**Pronto quando:** Tentar salvar resultado antes de `hora_sorteio` exibe alerta "O sorteio só pode ser lançado após às HH:MM:SS" e não grava
**Alerta:** ⚠️ TODO documentado no CLAUDE.md — esta é a implementação da validação pendente

---

### Tarefa 17 — ResultadoList: usar vw_sorteio
**Status:** done
**Dependência:** Tarefa 01 (view deve existir antes)
**Lê:** `_reversa_sdd/user-stories/us-resultado-sorteio.md` (US-RES-05), `_reversa_sdd/sdd/gap-resultadojb.md`
**Constrói:** Fix em `app/control/resultado/ResultadoList.php` — substituir query `cad_extracao` por `vw_sorteio` filtrada por data atual
**Pronto quando:** Combo de extração exibe apenas extrações com sorteio aberto no dia corrente

---

### Tarefa 18 — Resultado Quininha / Seninha / Lotinha
**Status:** done
**Lê:** `_reversa_sdd/user-stories/us-resultado-sorteio.md` (US-RES-02, US-RES-03, US-RES-04), `_reversa_sdd/sdd/gap-quininha-seninha-lotinha.md`
**Constrói:** `app/control/resultado/QuininhaResultadoForm.php`, `SeninhaResultadoForm.php`, `LotinhaResultadoForm.php`
**Pronto quando:** Cada form valida máscara (5/6/15 dezenas), hora_sorteio, e dispara a trigger correta ao encerrar

---

### Tarefa 19 — Segurança: migração MD5 → bcrypt
**Status:** done
**Lê:** `_reversa_sdd/adrs/003-md5-para-senhas.md`
**Constrói:** Fix em `GerenteForm.php`, `VendedorForm.php` (hash) e `ApplicationAuthenticationRestService.php` (verify)
**Pronto quando:** Novas senhas usam `password_hash($senha, PASSWORD_BCRYPT)`; login usa `password_verify()`; senhas existentes migradas na primeira mudança de senha
**Alerta:** ⚠️ Migração aprovada pelo stakeholder em 2026-05-01 (ADR-003)

---

### Tarefa 20 — REST Auth: fix refreshToken + expires_at
**Status:** done
**Lê:** `_reversa_sdd/adrs/002-jwt-para-api-movel.md`, `_reversa_sdd/user-stories/us-app-movel.md` (US-APP-01), `_reversa_sdd/flowcharts/rest-api.md`
**Constrói:** Fix em `ApplicationAuthenticationRestService.php::refreshToken()` — adicionar `if ($decoded['expires'] < time()) { throw ... }` na linha 187
**Pronto quando:** Token expirado recebe HTTP 401 ao tentar renovar; token válido continua sendo renovado normalmente
**Alerta:** ⚠️ GAP-04 — vulnerabilidade crítica de segurança

---

### Tarefa 21 — REST Sorteio + Modalidade
**Status:** done
**Lê:** `_reversa_sdd/flowcharts/rest-api.md`, `_reversa_sdd/user-stories/us-app-movel.md` (US-APP-02)
**Constrói:** `SorteioRestService.php`, `ModalidadeRestService.php`
**Pronto quando:** `GET /api/sorteio/abertos` e `GET /api/modalidade/disponiveis` respondem com dados corretos

---

### Tarefa 22 — REST Bilhete JB
**Status:** done
**Lê:** `_reversa_sdd/flowcharts/rest-api.md`, `_reversa_sdd/user-stories/us-app-movel.md` (US-APP-03)
**Constrói:** `BilheteRestService.php`
**Pronto quando:** `POST /api/bilhete/registrar` aceita `{terminal_id, jogos[]}` e retorna `{jb_id, bilhete_numero, string_autorizacao, total_bilhete, data_hora, vendedor_nome}`; cancelamento respeita `pode_cancelar_tempo` e `pode_cancelar_qtde`

---

### Tarefa 23 — REST Vendedor / Terminal / Caixa / Resultado
**Status:** done
**Lê:** `_reversa_sdd/flowcharts/rest-api.md`, `_reversa_sdd/user-stories/us-app-movel.md` (US-APP-04, US-APP-05)
**Constrói:** `VendedorRestService.php`, `TerminalRestService.php`, `CaixaRestService.php`, `ResultadoRestService.php`
**Pronto quando:** Endpoints `/me`, `/resumo`, `/registrar` (resultado) respondem corretamente

---

### Tarefa 24 — OpenAPI: Specs resultado + descarrego + relatórios
**Status:** done
**Lê:** `_reversa_sdd/openapi/spec-resultado.yaml`, `_reversa_sdd/openapi/spec-descarrego.yaml`, `_reversa_sdd/openapi/spec-relatorios-vendas.yaml`, `_reversa_sdd/openapi/spec-relatorios-financeiros.yaml`
**Constrói:** (documentação) — implementação dos endpoints cobertos
**Pronto quando:** Todos os endpoints descritos nas specs respondem conforme os contratos

---

### Tarefa 25 — REST BilhetinhoRestService
**Status:** pending (Won't — aguardando programação)
**Lê:** `_reversa_sdd/user-stories/us-app-movel.md` (US-APP-04), `_reversa_sdd/user-stories/us-jogos-especiais.md` (US-JE-01), `_reversa_sdd/sdd/gap-bilhetinho.md`
**Constrói:** `app/service/rest/BilhetinhoRestService.php` + Active Records da Tarefa 02
**Pronto quando:** `POST /api/bilhetinho/registrar` registra aposta e retorna bilhete; cancelamento funciona
**Alerta:** ⚠️ GAP-02 — implementar somente quando `cfg_parametros.ativo_bilhetinho=true` for necessário. ARs já criados na Tarefa 02.

---

### Tarefa 26 — Jogos Especiais: Quininha / Seninha / Lotinha (config)
**Status:** review
**Lê:** `_reversa_sdd/user-stories/us-jogos-especiais.md` (US-JE-02, US-JE-03, US-JE-04), `_reversa_sdd/openapi/spec-jogos-especiais.yaml`
**Constrói:** Verificação de configuração de `cad_modalidade` para `jogo_id = QUI/SEN/LOT`; triggers documentadas em `flowcharts/resultado.md`
**Pronto quando:** Modalidades Quininha/Seninha/Lotinha configuradas corretamente com multiplicadores e triggers corretas

---

### Tarefa 27 — Grade de Comissão (Should)
**Status:** done
**Lê:** `_reversa_sdd/user-stories/us-configuracoes.md` (US-CFG-08), `_reversa_sdd/sdd/gap-grade-comissao.md`
**Constrói:** `app/model/entities/CfgGradeComissao.php`, `app/model/entities/CfgGradeComissaoItens.php`, `CfgGradeComissaoForm.php`, `CfgGradeComissaoList.php`
**Pronto quando:** Grade de comissão editável via UI; integração com cálculo de comissão do vendedor
**Alerta:** Prioridade Should — implementar após todas as tarefas Must estarem done

---

## Resumo de Status

| Categoria | Total | ✅ done | 🔄 review | ⏳ pending |
|---|---|---|---|---|
| Infraestrutura (schema, ARs, bugs) | 3 | 3 | 0 | 0 |
| Cadastros | 5 | 2 | 3 | 0 |
| Configurações | 7 | 7 | 0 | 0 |
| Resultado / Operacional | 3 | 3 | 0 | 0 |
| Segurança | 2 | 2 | 0 | 0 |
| REST API | 4 | 3 | 0 | 1 |
| OpenAPI docs | 1 | 1 | 0 | 0 |
| Jogos Especiais | 1 | 0 | 1 | 0 |
| Grade de Comissão | 1 | 1 | 0 | 0 |
| **Total** | **27** | **22** | **4** | **1** |

> **Legenda:** `done` = código existente cobre a spec | `review` = existe mas tem bug/alerta | `pending` = não implementado
