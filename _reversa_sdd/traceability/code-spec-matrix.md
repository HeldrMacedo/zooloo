# Code-Spec Matrix — Rastreabilidade Código ↔ Specs

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Cobertura: 🟢 Coberto | 🟡 Parcial | — Não coberto (infra/admin)

---

## Legenda

| Símbolo | Significado |
|---|---|
| 🟢 | Arquivo totalmente coberto pelo spec |
| 🟡 | Arquivo parcialmente coberto (regras inferidas ou lacunas) |
| 🔴 | Arquivo com lacunas críticas documentadas |
| — | Fora do escopo (infra Adianti, comunicação, admin) |

---

## Controllers — Módulos de Negócio

| Arquivo | User Story | OpenAPI / SDD | Cobertura |
|---|---|---|---|
| `control/area/AreaForm.php` | US-CAD-01 | `flowcharts/area.md` | 🟢 |
| `control/area/AreaList.php` | US-CAD-01 | `flowcharts/area.md` | 🟢 |
| `control/gerente/GerenteForm.php` | US-CAD-02 | `flowcharts/gerente.md`, `adrs/006-dual-entity.md` | 🟡 bug typo confirmado |
| `control/gerente/GerenteList.php` | US-CAD-02 | `flowcharts/gerente.md` | 🟢 |
| `control/extracao/ExtracaoForm.php` | US-CAD-03 | `flowcharts/extracao.md` | 🟡 bug typo confirmado |
| `control/extracao/ExtracaoList.php` | US-CAD-03 | `flowcharts/extracao.md` | 🟢 |
| `control/modalidade/ModalidadeForm.php` | US-CAD-04, US-JE-02..05 | `flowcharts/modalidade.md`, `spec-jogos-especiais.yaml` | 🔴 col04+05 ausentes |
| `control/modalidade/ModalidadeList.php` | US-CAD-04 | `flowcharts/modalidade.md` | 🟢 |
| `control/vendedor/VendedorForm.php` | US-CAD-05 | `flowcharts/vendedor.md`, `adrs/006-dual-entity.md` | 🟢 |
| `control/vendedor/VendedorList.php` | US-CAD-05 | `flowcharts/vendedor.md` | 🟢 |
| `control/area-extracao/AreaExtracaoList.php` | US-CFG-01 | `flowcharts/area-extracao.md` | 🟢 |
| `control/area-cotacao/AreaCotacaoForm.php` | US-CFG-02 | `flowcharts/area-cotacao.md` | 🟢 |
| `control/area-cotacao/AreaCotacaoList.php` | US-CFG-02 | `flowcharts/area-cotacao.md` | 🟢 |
| `control/area-limite/AreaLimiteForm.php` | US-CFG-03 | `flowcharts/area-limite.md` | 🟡 uniqueness só no create |
| `control/area-limite/AreaLimiteList.php` | US-CFG-03 | `flowcharts/area-limite.md` | 🟢 |
| `control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php` | US-CFG-04 | `flowcharts/area-comissao-modalidade.md` | 🟡 uniqueness só no create |
| `control/AreaComissaoModalidade/AreaComissaoModalidadeList.php` | US-CFG-04 | `flowcharts/area-comissao-modalidade.md` | 🟢 |
| `control/palpite-cotado/PalpiteCotadoForm.php` | US-CFG-05 | `flowcharts/palpite-cotado.md` | 🟢 |
| `control/palpite-cotado/PalpiteCotadoList.php` | US-CFG-05 | `flowcharts/palpite-cotado.md` | 🟢 |
| `control/ExtracaoDescarga/ExtracaoDescargaForm.php` | US-CFG-06 | `flowcharts/extracao-descarga.md` | 🟢 |
| `control/ExtracaoDescarga/ExtracaoDescargaList.php` | US-CFG-06 | `flowcharts/extracao-descarga.md` | 🟢 |
| `control/parametros/ParametrosForm.php` | US-CFG-07 | `flowcharts/parametros.md` | 🟢 |
| `control/parametros/ParametrosList.php` | US-CFG-07 | `flowcharts/parametros.md` | 🟢 |
| `control/resultado/ResultadoForm.php` | US-RES-01 | `flowcharts/resultado.md`, `spec-resultado.yaml` | 🔴 hora_limite não validada |
| `control/resultado/ResultadoList.php` | US-RES-05 | `flowcharts/resultado.md` | 🔴 usa cad_extracao em vez de vwsorteio |

---

## Controllers — Infra Adianti (fora do escopo do negócio)

| Arquivo | Cobertura |
|---|---|
| `control/admin/**` (31 arquivos) | — infra Adianti |
| `control/communication/**` (22 arquivos) | — módulo comunicação padrão |
| `control/log/**` (7 arquivos) | — logs Adianti |
| `control/public/**` (2 arquivos) | — páginas públicas |
| `control/CommonPage.php` | — infra |
| `control/WelcomeView.php` | — infra |
| `control/SearchBox.php` | — infra |

---

## Entities (Active Records)

| Arquivo | User Story | SDD | Cobertura |
|---|---|---|---|
| `model/entities/Area.php` | US-CAD-01 | `data-dictionary.md`, `erd-complete.md` | 🟢 |
| `model/entities/Gerente.php` | US-CAD-02 | `data-dictionary.md`, `erd-complete.md` | 🟢 |
| `model/entities/Extracao.php` | US-CAD-03 | `data-dictionary.md`, `erd-complete.md` | 🟢 |
| `model/entities/Modalidade.php` | US-CAD-04, US-JE-02..05 | `data-dictionary.md`, `erd-complete.md` | 🔴 multiplicadorColocacao04+05 ausentes |
| `model/entities/Vendedor.php` | US-CAD-05 | `data-dictionary.md`, `erd-complete.md` | 🟢 |
| `model/entities/AreaExtracao.php` | US-CFG-01 | `data-dictionary.md`, `adrs/005-insert-delete.md` | 🟢 |
| `model/entities/AreaCotacao.php` | US-CFG-02, US-APP-02 | `data-dictionary.md` | 🟢 |
| `model/entities/AreaLimite.php` | US-CFG-03, US-APP-03 | `data-dictionary.md` | 🟢 |
| `model/entities/AreaComissaoModalidade.php` | US-CFG-04 | `data-dictionary.md` | 🟢 |
| `model/entities/PalpiteCotado.php` | US-CFG-05, US-APP-02 | `data-dictionary.md` | 🟢 |
| `model/entities/ExtracaoDescarga.php` | US-CFG-06 | `data-dictionary.md`, `flowcharts/extracao-descarga.md` | 🟢 |
| `model/entities/Parametros.php` | US-CFG-07 | `data-dictionary.md`, `flowcharts/parametros.md` | 🟢 |
| `model/entities/MovSorteio.php` | US-RES-01..05 | `data-dictionary.md`, `state-machines.md` | 🟢 |
| `model/entities/IntJogo.php` | US-JE-01..05 | `data-dictionary.md` | 🟢 |
| `model/entities/IntCalculoSorteio.php` | US-RES-01 | `data-dictionary.md` | 🟡 papel no calc_sorteio_id inferido |
| `model/entities/Terminal.php` | US-APP-05 | `data-dictionary.md` | 🟢 |
| `model/entities/MovJb.php` | US-APP-03 | `data-dictionary.md`, `erd-complete.md` | 🟡 estrutura parcialmente confirmada |
| `model/entities/MovJbSorteio.php` | US-APP-03 | `data-dictionary.md` | 🟡 parcialmente confirmada |
| `model/entities/MovJbSortPalpite.php` | US-APP-03 | `data-dictionary.md` | 🟡 parcialmente confirmada |

---

## Entities Ausentes (GAPs)

| Entidade esperada | Tabela | User Story | Lacuna |
|---|---|---|---|
| `CadModalidadeBilhetinho.php` | `cad_modalidade_bilhetinho` | US-JE-01 | 🔴 tabela existe, AR não implementado |
| `MovBilhetinho.php` | `mov_bilhetinho` | US-APP-04 | 🔴 tabela existe, AR não implementado |
| `MovBilhetinhoSorteio.php` | `mov_bilhetinho_sorteio` | US-APP-04 | 🔴 tabela existe, AR não implementado |
| `MovCaixa.php` | `mov_caixa` | US-APP-05 | 🟡 pode existir sem ter sido identificado |
| `CfgGradeComissao.php` | `cfg_grade_comissao` | US-CFG-08 | 🔴 Won't — não implementado |

---

## Services REST

| Arquivo | User Story | OpenAPI | Cobertura |
|---|---|---|---|
| `service/auth/ApplicationAuthenticationRestService.php` | US-APP-01 | `spec-auth.yaml` 🟡 | 🟡 refreshToken sem validação 🔴 |
| `service/rest/ModalidadeRestService.php` | US-APP-02 | `flowcharts/rest-api.md` | 🟢 |
| `service/rest/BilheteRestService.php` | US-APP-03 | `flowcharts/rest-api.md` | 🟢 |
| `service/rest/VendedorRestService.php` | US-APP-05 | `flowcharts/rest-api.md` | 🟢 |
| `service/rest/ResultadoRestService.php` | US-APP-05 | `flowcharts/rest-api.md` | 🟢 |
| `service/rest/CaixaRestService.php` | US-APP-05 | `flowcharts/rest-api.md` | 🟢 |
| `service/rest/TerminalRestService.php` | US-APP-05 | `flowcharts/rest-api.md` | 🟢 |
| `service/rest/SorteioRestService.php` | US-APP-02 | `flowcharts/rest-api.md` | 🟡 papel complementar inferido |
| `service/rest/SystemUserRestService.php` | — | — | — infra Adianti |
| `service/rest/SystemUserGroupRestService.php` | — | — | — infra Adianti |

---

## Services Ausentes (GAPs)

| Service esperado | User Story | Lacuna |
|---|---|---|
| `BilhetinhoRestService.php` | US-APP-04 | 🔴 não implementado — app não pode apostar Bilhetinho |

---

## Mapa de Specs por Artefato SDD

| Artefato SDD | Módulos cobertos | Status |
|---|---|---|
| `inventory.md` | Todos (superfície) | 🟢 |
| `dependencies.md` | Todas as dependências | 🟢 |
| `code-analysis.md` | 14 módulos de negócio | 🟢 |
| `data-dictionary.md` | 19 entidades | 🟢 |
| `flowcharts/area.md` | Área | 🟢 |
| `flowcharts/gerente.md` | Gerente | 🟢 |
| `flowcharts/extracao.md` | Extração | 🟢 |
| `flowcharts/modalidade.md` | Modalidade | 🟢 |
| `flowcharts/vendedor.md` | Vendedor | 🟢 |
| `flowcharts/area-cotacao.md` | Área Cotação | 🟢 |
| `flowcharts/area-extracao.md` | Área Extração | 🟢 |
| `flowcharts/area-limite.md` | Área Limite | 🟢 |
| `flowcharts/area-comissao-modalidade.md` | Área Comissão Modalidade | 🟢 |
| `flowcharts/extracao-descarga.md` | Extração Descarga | 🟢 |
| `flowcharts/palpite-cotado.md` | Palpite Cotado | 🟢 |
| `flowcharts/parametros.md` | Parâmetros | 🟢 |
| `flowcharts/resultado.md` | Resultado JB | 🟢 |
| `flowcharts/rest-api.md` | REST API (todos os services) | 🟢 |
| `domain.md` | 25 regras de negócio | 🟢 |
| `state-machines.md` | 6 máquinas de estado | 🟢 |
| `permissions.md` | Matriz de permissões | 🟢 |
| `architecture.md` | Arquitetura geral | 🟢 |
| `erd-complete.md` | ERD 19 entidades | 🟢 |
| `c4-context.md` | Diagrama C4 Contexto | 🟢 |
| `c4-containers.md` | Diagrama C4 Containers | 🟢 |
| `c4-components.md` | Diagrama C4 Componentes | 🟢 |
| `adrs/001-reescrita-java-para-php.md` | Decisão de reescrita | 🟢 |
| `adrs/002-jwt-para-api-movel.md` | Autenticação REST | 🟢 |
| `adrs/003-md5-para-senhas.md` | Vulnerabilidade MD5 | 🟢 |
| `adrs/004-trigger-para-calculo-premios.md` | Triggers PostgreSQL | 🟢 |
| `adrs/005-insert-delete-para-area-extracao.md` | Padrão presença/ausência | 🟢 |
| `adrs/006-dual-entity-usuario-gerente-vendedor.md` | Dual-Entity pattern | 🟢 |
| `traceability/spec-impact-matrix.md` | 5 cenários de alto risco | 🟢 |
| `openapi/spec-resultado.yaml` | REST Resultado JB | 🟢 |
| `openapi/spec-descarrego.yaml` | REST Descarrego JB | 🟢 |
| `openapi/spec-relatorios-vendas.yaml` | REST Relatórios de Venda | 🟢 |
| `openapi/spec-relatorios-financeiros.yaml` | REST Relatórios Financeiros | 🟢 |
| `openapi/spec-jogos-especiais.yaml` | REST Jogos Especiais | 🟢 |
| `user-stories/us-cadastros.md` | Área/Gerente/Extração/Modalidade/Vendedor | 🟢 |
| `user-stories/us-configuracoes.md` | 8 módulos de configuração | 🟢 |
| `user-stories/us-resultado-sorteio.md` | JB/Quininha/Seninha/Lotinha resultado | 🟢 |
| `user-stories/us-operacional.md` | Descarrego/Mapa/Vendas/Apuração/Financeiro | 🟢 |
| `user-stories/us-app-movel.md` | REST API (app móvel) | 🟢 |
| `user-stories/us-jogos-especiais.md` | Bilhetinho/Quininha/Seninha/Lotinha/Milhar Premiada | 🟢 |
| `traceability/code-spec-matrix.md` | Este arquivo | 🟢 |

---

## Resumo de Cobertura

| Categoria | Total | 🟢 Coberto | 🟡 Parcial | 🔴 Lacuna crítica | — Fora de escopo |
|---|---|---|---|---|---|
| Controllers de negócio | 26 | 18 | 4 | 4 | — |
| Controllers de infra | 63 | — | — | — | 63 |
| Entities | 19 | 13 | 5 | 1 | — |
| Entities ausentes (GAP) | 5 | — | — | 5 | — |
| Services REST | 10 | 7 | 1 | — | 2 |
| Services ausentes (GAP) | 1 | — | — | 1 | — |
| **Total negócio** | **61** | **38** | **10** | **11** | — |

### Lacunas Críticas (🔴) — Resumo Executivo

1. **`BilhetinhoRestService.php` ausente** — app móvel não pode registrar apostas de Bilhetinho
2. **`CadModalidadeBilhetinho.php` ausente** — sem Active Record para configurar Bilhetinho
3. **`mov_bilhetinho` / `mov_bilhetinho_sorteio` sem entidade PHP** — Bilhetinho inacessível via Adianti
4. **`Modalidade.php` sem `multiplicadorColocacao04+05`** — Milhar Premiada perde dados silenciosamente
5. **`ResultadoForm.php` sem validação de hora_limite** — pode registrar resultado antes do horário
6. **`ResultadoList.php` usa `cad_extracao` em vez de `vwsorteio`** — UX degradada (exibe extrações sem sorteio)
7. **`GerenteForm.php` typo na chamada de atualização de usuário** — bug confirmado no código
8. **`ExtracaoForm.php` typo no campo de extração** — bug confirmado no código
9. **`ApplicationAuthenticationRestService.php` — `refreshToken` sem validação** — lacuna de segurança
10. **MD5 sem salt para senhas** — vulnerabilidade documentada (ADR-003)
11. **Grade de Comissão (`cfg_grade_comissao`)** — Won't — não implementado no Zooloo
