# Comparativo tela a tela: allsystem × zooloo

**Data da análise:** 2026-07-17  
**Conclusão:** o zooloo **não está 100% implementado** em relação ao allsystem. Cobre bem o esqueleto de cadastros e configurações e tem a maioria das telas operacionais/relatórios **existentes**, mas a **lógica de negócio de várias telas é parcial** — especialmente descarrego, consulta de vendas, premiação, resultado JB (sorteio automático) e todo o stack de **Bilhetinho**.

| Projeto | Caminho | Stack |
|---|---|---|
| **allsystem** (legado) | `C:\desenvolvimento\jballsystem\allsystem\` | Java/Spring Boot + Angular (JHipster) |
| **zooloo** (reescrita) | `C:\desenvolvimento\zooloo\` | PHP + Adianti Framework 8.1 |

---

## Visão geral de cobertura

| Categoria | Telas allsystem | Situação no zooloo |
|---|---|---|
| Cadastros | 10 | 5 ok / 4 parciais / 5 ausentes |
| Configurações | 7 | 6 implementadas / 1 parcial |
| JB / Lotinha / Qui / Sen | 12 | em geral implementadas (Resultado JB parcial) |
| Operacional | 10 | 7 parciais / 3 ausentes |
| Relatórios | 10 | 7 parciais / 3 ausentes |

**Lógica pesada compartilhada (OK):** apuração de ganhadores continua nas **triggers do PostgreSQL** (`trg_mv_sorteio_verifica_ganhadores`, `_lotinha`, `_qui_sen`). Os dois sistemas gravam o resultado; o banco calcula premiados.

### Legenda de status

| Status | Significado |
|---|---|
| **Implementado** | Tela existe e cobre o fluxo principal com paridade de lógica |
| **Parcial** | Tela existe, mas faltam campos, validações, ações ou a lógica diverge |
| **Não implementado** | Sem controller/menu (pode haver apenas model) |

---

## 1. Cadastros

| Tela allsystem | Zooloo | Status | Lógica / gaps principais |
|---|---|---|---|
| **Área** | `AreaList` / `AreaForm` | **Implementado** | CRUD completo; paridade de campos (`descricao`, `ativo`) |
| **Setorista/Coletor** | `GerenteList` / `GerenteForm` | **Parcial** | Cria `SystemUser` + grupo GERENTE. **Faltam no form:** `acesso_web`, `outras_areas`. Bug: campo senha `'password '` (espaço). Multi-área do setorista não existe |
| **Vendedor** | `VendedorList` / `VendedorForm` | **Parcial** | Cria usuário + grupo VENDEDOR; cascade área→coletor. `observacao` existe mas **não vai pro layout**. Flags de reimpressão avançadas sem UI |
| **Extração** | `ExtracaoList` / `ExtracaoForm` | **Parcial** | Dias da semana, hora, calc_sorteio. **Faltam:** `ultimo_sorteio_numero`, `gerar_restante`, `limite_palpite` condicional, validação “ativo ⇒ ≥1 dia”, auto `premiacao_maxima` pelo calc |
| **Modalidade** | `ModalidadeList` / `ModalidadeForm` | **Parcial** | Multiplicador e limites ok. **Faltam:** max prêmio diário/poule; possível bug de bind `multiplicadorColocacao01` vs `multiplicador_colocacao_01` |
| **Terminal** | só model `Terminal.php` | **Não implementado** | Sem Form/List/menu. Model sem `area_id` |
| **Grade Comissão + Itens** | `grade-comissao/*` | **Implementado (fora do menu)** | CRUD + validações; **não está no `menu.xml`** |
| **Vendedor Comissão Modalidade** | — | **Não implementado** | Tabela `cfg_vendedor_mod_comissao` sem tela (diferente de Área Comissão) |
| **Setorista Área** | — | **Não implementado** | `cfg_coletor_area` / multi-área do setorista ausente; quebra hierarquia operacional |
| **Modalidade Bilhetinho** | só model | **Não implementado** | Sem tela de multiplicadores por colocação |

### Detalhamento — Cadastros

#### 1.1 Área — Implementado

| Campo | allsystem | zooloo |
|---|---|---|
| `area_id` | sim | sim |
| `descricao` | obrigatório | obrigatório |
| `ativo` | checkbox | combo S/N |
| `complemento` | comentado no domain | ausente (alinhado) |

Sem side effects relevantes.

#### 1.2 Gerente (Coletor) — Parcial

| Campo | allsystem | zooloo form | zooloo model |
|---|---|---|---|
| `coletor_id`, `nome`, `area_id` | sim | sim | sim |
| login / senha | sim | sim | via `SystemUser` |
| `acesso_web` | editável | **ausente no form** | existe |
| `outras_areas` | editável | **ausente no form** | existe |
| `ativo` | sim | sim | sim |

**Side effects:** allsystem cria User JHipster (`lastName=SETORISTA`); zooloo cria `SystemUser` + grupo GERENTE.

**Gaps:** campos sem UI; bug `password ` com espaço; sem vínculo `cfg_coletor_area`.

#### 1.3 Vendedor — Parcial

- Criação de usuário + grupo VENDEDOR: ok.
- Cascade área → coletores ativos: ok.
- `observacao` instanciado e **não adicionado ao layout**.
- Flags avançadas de reimpressão no model/domain, sem UI nos dois (allsystem também parcial).
- Zooloo expõe mais permissões na UI que o front legado atual (`pode_pagar`, `pode_reimprimir`).

#### 1.4 Extração — Parcial

| Campo | allsystem | zooloo |
|---|---|---|
| descricao, descricao_mobile, hora_limite | sim | sim (mobile sem maxLength 6) |
| premiacao_maxima | auto do CalcSorteio | manual, sem auto |
| calc_sorteio / dias da semana | sim | sim |
| dia_sorteio_inicial | só na criação | sempre required |
| ultimo_sorteio_numero | só na criação | **ausente** |
| gerar_restante / limite_palpite | sim (condicional) | **ausente** |

Trigger DB `trg_mv_cad_extracao_cria_sorteios` compartilhada (schema).

#### 1.5 Modalidade — Parcial

- Desabilitar multi/limites se `informar_valores_modalidade = N`: ok.
- Jogo só uma vez (`NOT IN` modalidades já criadas): ok.
- Max prêmio diário/poule e colocações 02–05: sem UI no zooloo.
- Possível bug de bind: `multiplicadorColocacao01` vs `multiplicador_colocacao_01`.

#### 1.6 Terminal — Não implementado

Campos allsystem: `serial`, `tipo` (PC/SMART/POS/MOBILE), `area`, `vendedor`, `multiploUsuario`, `ativo`.  
Zooloo: apenas model (sem `area_id`).

#### 1.7 Grade Comissão — Implementado (fora do menu)

- Grade: `descricao`; Itens: grade + modalidade + comissão (0–100%, unicidade).
- Não listada em `menu.xml`.

#### 1.8 Vendedor Comissão Modalidade — Não implementado

Tabela `CFG_VENDEDOR_MOD_COMISSAO` (modalidade, área, vendedor opcional = TODOS, comissão).  
Não confundir com **Área Comissão Modalidade** (já existe).

#### 1.9 Setorista Área — Não implementado

- Tabela `cfg_coletor_area`.
- Usado no legado para filtrar áreas do coletor e coletores por área no vendedor.
- Sem isso, `outras_areas` no gerente não tem efeito prático.

#### 1.10 Modalidade Bilhetinho — Não implementado

Model `CadModalidadeBilhetinho.php` existe; sem Form/List. PK composta modalidade + colocação.

---

## 2. Configurações

| Tela | Zooloo | Status | Lógica |
|---|---|---|---|
| **Área Extração** | `AreaExtracaoList` | **Implementado** | Toggle criar/apagar vínculo. Gap: setorista multi-área (só 1 área do gerente) |
| **Área Cotação** | `AreaCotacao*` | **Implementado** | area + modalidade + extração opcional (“TODAS”) + multiplicador; valida duplicata |
| **Área Limite** | `AreaLimite*` | **Implementado** | CRUD + bloqueio de duplicata |
| **Área Comissão Modalidade** | `AreaComissaoModalidade*` | **Implementado** | CRUD + validação 0–100% |
| **Palpite Cotado** | `PalpiteCotado*` | **Implementado** | Cotação especial por palpite; validação 4 dígitos |
| **Extração Descarga** | `ExtracaoDescarga*` | **Implementado** | Limite por extração×modalidade |
| **Parâmetros** | `Parametros*` | **Parcial** | Muitos flags expostos (legado esconde). **Falta no form:** `valor_bilhetinho`. Cascata sena→quadra / quina→terno do Angular **não portada** |

### Detalhamento — Configurações

#### 2.1 Área Extração

- Ativar: INSERT em `cfg_area_extracao`; desativar: DELETE.
- Zooloo lista só extrações ativas; legado lista todas.
- Filtro de área do setorista: legado multi-área; zooloo só `gerente.area_id`.

#### 2.2–2.6 Demais configs

CRUD com paridade de campos e, em vários casos, validações de duplicidade mais rígidas no PHP do que no front legado. Aplicação dos limites/cotações ocorre na venda/API/triggers, não na própria tela.

#### 2.7 Parâmetros — Parcial

Campos no model (`cfg_parametros`): nome_banca, cnpj, telefone, cidade, estado, site, email, mensagem_01..05, valor_milhar_brinde, valor_bilhetinho, ativo_bilhetinho, ativo_modalidade, qtde_num_mi/ci/mci, ativo_instantaneo, ativo_quininha/seninha/lotinha/jb, sena_inc_*, quina_inc_*, limite_extracao, ativo_milharpremiada, valor_milharpremiada.

| Item | allsystem | zooloo |
|---|---|---|
| Flags de jogo (ativo_jb, bilhetinho…) | não editáveis no form Angular | todos expostos |
| Cascata sena/quina | `verificaSena()` / `verificaQuina()` | não implementado |
| `valor_bilhetinho` | no domain | no model, **ausente no Form** |

---

## 3. Resultados e jogos derivados

| Tela | Zooloo | Status | Lógica |
|---|---|---|---|
| **Resultado JB** | `ResultadoList` / `ResultadoForm` | **Parcial** | Digitar 1º–10º, grupo/bicho, salvar/limpar/encerrar, validar situação `A`. **Faltam:** botão **Sortear** automático (`calculo_id` 2/3/6), prêmios derivados calc 4/5, validação **data+hora** completa (hoje só `H:i:s`) |
| **Lotinha** (extr./mod./resultado) | `lotinha/*` | **Implementado** | 15 dezenas; trigger lotinha no DB |
| **Quininha** | `quininha/*` | **Implementado** | 5 dezenas; prêmios quina/quadra/terno |
| **Seninha** | `seninha/*` | **Implementado** | 6 dezenas; prêmios sena/quina/quadra |

### Mapa: onde está a lógica

| Capacidade | PHP (zooloo) | Banco (trigger) | Faltando |
|---|---|---|---|
| Lançar números JB | ✓ | — | — |
| Validar hora sorteio | parcial* | — | data+hora completa |
| Encerrar/limpar sorteio | ✓ | — | — |
| Calcular ganhadores | — | ✓ | — |
| Sortear automático JB | — | — | ✓ |
| Prêmios derivados calc 4/5 | — | — | ✓ |
| Lot/Qui/Sen resultado | ✓ | ✓ | UX menor |

\* só compara `H:i:s` do relógio atual, não a data do sorteio.

---

## 4. Operacional

| Tela allsystem | Zooloo | Status | Gaps de lógica |
|---|---|---|---|
| **Consulta Vendas (vendasjb)** | `ConsultaVendasList` | **Parcial** | Lista `vw_vendajb`. **Sem:** detalhe do bilhete, **cancelar**, limpar impressão, filtro tipo (`filtro_banca`), valor mínimo |
| **Vendas por Sorteio** | `VendasPorSorteioList` | **Parcial** | Histórico 7 dias. **Sem:** alterar data do sorteio, drill-down |
| **Premiação** | `PremiacaoList` | **Parcial** | Paga em nível `MovJbSorteio`. Legado paga **por palpite** + “pagar todos” + flags de colocação. `jogo_id` Quininha diverge (25 vs 32) |
| **Apuração** | `ApuracaoList` | **Parcial (outro conceito)** | Legado = bilhetes do dia (NSU, poule, palpites). Zooloo = **resumo por sorteio** (totais) |
| **Mapa de Apostas** | `MapaApostasList` | **Parcial** | **Sem coluna Bicho/Grupo**; filtros diferentes (sorteio vs data+extração+modalidade) |
| **Listagem de Bilhetes** | `BilheteList` | **Parcial** | Falta **poule**; área não é obrigatória |
| **Descarrego JB** | `DescarregoList` | **Parcial (crítico)** | Só consulta excesso. **Sem processar**, sem colocações 1P–10P, sem histórico, sem PDF |
| **Bilhetinho** | — | **Não implementado** | Só models (`MovBilhetinho*`) |
| **Caixa Vendedor** | — | **Não implementado** | (backend legado já era frágil) |
| **Milhar Premiada** | — | **Não implementado** | Só flags em Parâmetros |

### Detalhamento — Operacional

#### 4.1 Consulta Vendas

- **allsystem:** `VendasJbService` → `VW_VENDAJB`; popup detalhe; cancelar com motivo; limpar impressão; filtros tipo/valor mín/NSU.
- **zooloo:** `SELECT * FROM vw_vendajb ... LIMIT 500`; filtros data/área/extração/vendedor/situação/NSU; sem ações mutáveis.

#### 4.2 Premiação

- **allsystem:** native query em `mov_jb_sort_palpite` com `ganhou_colocacao_01..10`; pagar individual e pagar todos; grava por palpite.
- **zooloo:** `vw_vendajb` com `sorteado = 'S'`; pagar em `MovJbSorteio` (`sorteado_pago`).

#### 4.3 Descarrego (gap crítico)

- **allsystem:** CTE com `jogou_colocacao` / `processado_colocacao`; limite `cfg_extracao_descarga` ou modalidade; Processar / Processar Todas; PDF; grava `data_jb_sort_palpite`.
- **zooloo:** consulta de excesso `SUM(valor) > limite`; sem processar, sem colocações, sem histórico.

#### 4.4 Apuração — conceitos distintos

| | allsystem | zooloo |
|---|---|---|
| Unidade | bilhetes do dia | sorteio |
| Colunas | NSU, poule, vendedor, palpites, valor | nº, data, extração, números, totais, prêmio |
| Título zooloo | — | “Apuração por Sorteio” (dashboard de fechamento) |

---

## 5. Relatórios

| Tela allsystem | Zooloo | Status | Observação |
|---|---|---|---|
| Geral Extração JB | `MovimentoExtracaoList` | **Parcial** | Quebra **área** vs legado **vendedor** |
| Geral Caixa JB | `GeralCaixaJbList` | **Parcial** | Sem `filtro_banca`; menu rotulado de forma confusa (“Movimento Geral Financeiro”) |
| Geral Financeiro | `GeralFinanceiroList` | **Parcial** | **Prêmio pago de terceiros = 0 fixo**; não usa `pago_usuario_id` |
| Geral Comissão | `GeralComissaoList` | **Implementado** | Modos vendedor/área; gaps de perfil |
| Vendas por Modalidade | `MovimentoVendasModalidadeList` | **Parcial** | Agrupamento **invertido** vs service Java |
| Vendas por Vendedor | `MovimentoVendasVendedorList` | **Parcial** | Dimensão diferente do legado |
| Vendas por Extração | `MovimentoVendasExtracaoList` | **Parcial** | Mais métricas; sem filtro vendedor |
| Geral Vendas Área | — | **Não implementado** | |
| Geral Caixa (Bilhetinho) | — | **Não implementado** | Depende de bilhetinho |
| Geral Extração (Bilhetinho) | — | **Não implementado** | Depende de bilhetinho |

### Arquivos de evidência (zooloo)

| Tela | Arquivo |
|---|---|
| Consulta Vendas | `app/control/relatorio/ConsultaVendasList.php` |
| Vendas por Sorteio | `app/control/relatorio/VendasPorSorteioList.php` |
| Premiação | `app/control/premiacao/PremiacaoList.php` |
| Apuração | `app/control/relatorio/ApuracaoList.php` |
| Mapa Apostas | `app/control/relatorio/MapaApostasList.php` |
| Bilhetes | `app/control/relatorio/BilheteList.php` |
| Descarrego | `app/control/descarrego/DescarregoList.php` |
| Mov. Extração | `app/control/relatorio/MovimentoExtracaoList.php` |
| Geral Caixa JB | `app/control/relatorio/GeralCaixaJbList.php` |
| Geral Financeiro | `app/control/relatorio/GeralFinanceiroList.php` |
| Geral Comissão | `app/control/relatorio/GeralComissaoList.php` |
| Vendas Modalidade | `app/control/relatorio/MovimentoVendasModalidadeList.php` |
| Vendas Vendedor | `app/control/relatorio/MovimentoVendasVendedorList.php` |
| Vendas Extração | `app/control/relatorio/MovimentoVendasExtracaoList.php` |
| Menu | `menu.xml` |

### Services Java de referência (allsystem)

| Tela | Service | Resource |
|---|---|---|
| Vendas | `VendasJbService.java` | `VendasJbResource.java` |
| Premiação | `PremiacaoJbService.java` | `PremiacaoJbResource.java` |
| Apuração | `ApuracaoJbService.java` | `ApuracaoJbResource.java` |
| Descarrego | `DescarregojbService.java` | `DescarregojbResource.java` |
| Geral Ext. JB | `GeralExtracaoJbService.java` | `GeralExtracaoJbResource.java` |
| Geral Caixa JB | `GeralCaixaJbService.java` | `GeralCaixaJbResource.java` |
| Geral Financeiro | `GeralFinanceiroService.java` | `GeralFinanceiroResource.java` |
| Comissão | `GeralComissaoService.java` | `GeralComissaoResource.java` |

---

## 6. Gaps transversais

| Tema | allsystem | zooloo |
|---|---|---|
| Filtro tipo de jogo (`filtro_banca`) | Combo JOGOS/QUI/SEN/BIL em várias telas | Quase sempre ausente |
| Restrição por perfil (coletor/setorista) | Áreas filtradas | Combos globais na maioria |
| Multi-área do setorista | `cfg_coletor_area` | Inexistente |
| Export PDF | Descarrego | Não |
| Ações mutáveis | Cancelar, pagar palpite, descarregar, alterar data | Só pagar (sorteio) em Premiação |
| Service layer | `*Service` + native SQL no backend | SQL no próprio controller `TPage` |
| Paginação | lista completa em geral | `LIMIT 200–500` em várias telas |
| REST mobile | APIs Java | Existe REST (`VendasJb`, `Bilhete`, `Terminal`, `Resultado`…), mas telas admin ≠ API mobile |

### TODOs já conhecidos no projeto

- Ao deixar um Gerente inativo, também deixar o usuário do sistema inativo (parcial na list).
- `ResultadoForm`: verificar o horário limite da extração antes de permitir salvar (parcial; falta data+hora completa).

Arquivos: `todo`, `CLAUDE.md`.

---

## 7. REST no zooloo (contexto mobile / API)

Local: `app/service/rest/`

| Service | Observação |
|---|---|
| `ApplicationAuthenticationRestService` | JWT login/logout/refresh |
| `VendasJbRestService` | vendas / cancelamento (API; tela web não espelha tudo) |
| `BilheteRestService` | bilhetes |
| `TerminalRestService` | terminal (API sem tela admin) |
| `ResultadoRestService` | resultado |
| `SorteioRestService` | sorteio |
| `VendedorRestService` | vendedor |
| `ModalidadeRestService` | modalidade |
| `CaixaRestService` | caixa |
| `SystemUserRestService` / `SystemUserGroupRestService` | usuários |

---

## 8. Prioridade sugerida (por impacto operacional)

### Crítico

1. **Descarrego** — processar por colocação + histórico (hoje é só mapa de excesso)
2. **Consulta Vendas** — detalhe + cancelamento + filtros
3. **Resultado JB** — sorteio automático e regras de `calcSorteio`
4. **Premiação** — pagar por palpite + “pagar todos”

### Alto

5. **Terminal** — cadastro completo (Form/List + menu; incluir `area_id`)
6. **Setorista Área** + campos `acesso_web` / `outras_areas` no Gerente
7. **Vendedor Comissão Modalidade**
8. **Geral Financeiro** — prêmio de terceiros / pagador real

### Médio

9. Grade Comissão no `menu.xml`
10. Extração/Modalidade — campos e validações faltantes
11. Apuração — alinhar conceito (bilhetes do dia vs resumo por sorteio)
12. Mapa de Apostas com coluna Bicho
13. Parâmetros (`valor_bilhetinho`, cascatas sena/quina)
14. Corrigir bug senha Gerente (`password ` com espaço)
15. Corrigir bind `multiplicador_colocacao_01` na Modalidade

### Stack Bilhetinho (quando for prioridade)

16. Modalidade Bilhetinho (CRUD multiplicadores)
17. Movimento/consulta Bilhetinho
18. Relatórios Geral Caixa / Geral Extração bilhetinho
19. Milhar Premiada
20. Caixa Vendedor

---

## 9. Resposta objetiva

| Pergunta | Resposta |
|---|---|
| As telas do menu do zooloo existem? | **Sim**, a maior parte do menu atual existe |
| A lógica está 1:1 com o allsystem? | **Não** |
| Cadastros básicos (Área, configs CRUD)? | **Quase** — área e configs boas; gerente/vendedor/extração/modalidade parciais |
| Operacional “de banca”? | **Incompleto** — consulta, descarrego e premiação são os maiores furos |
| Apuração de prêmio (ganhadores)? | **No banco (OK)** — compartilhada via triggers |
| Bilhetinho / Terminal / Setorista multi-área? | **Não implementados** no admin |

---

## 10. Menu atual do zooloo (`menu.xml`)

```
Cadastros
├── Área           → AreaList
├── Gerente        → GerenteList
└── Vendedor       → VendedorList

JB
├── Extração       → ExtracaoList
├── Modalidade     → ModalidadeList
└── Resultado      → ResultadoList

Lotinha / Quininha / Seninha
├── Extração
├── Modalidades
└── Resultado

Operacional
├── Consulta Vendas
├── Vendas por Sorteio
├── Premiações
├── Apuração
├── Mapa de Apostas
├── Listagem de Bilhetes
└── Descarrego

Configurações
├── Área Extração
├── Área Cotação
├── Área Limite
├── Área Comissão Modalidade
├── Palpite Cotado
├── Extração Descarga
└── Parâmetros

Relatórios
├── Movimento Geral Extração
├── Movimento Geral Financeiro  (GeralCaixaJbList)
├── Movimento Geral Caixa       (GeralFinanceiroList)
├── Geral Comissão
├── Vendas por Modalidade
├── Vendas por Vendedor
└── Vendas por Extração
```

**Fora do menu (código existe):** Grade Comissão (`grade-comissao/`).

---

## 11. Side effects de cadastro

| Cadastro | allsystem | zooloo |
|---|---|---|
| Área | nenhum | nenhum |
| Coletor/Gerente | cria/atualiza/deleta User | cria/atualiza SystemUser + grupo GERENTE |
| Vendedor | cria/atualiza/deleta User; treinamento=false | cria/atualiza SystemUser + grupo VENDEDOR |
| Extração | register log; trigger sorteios | store + trigger DB; sem audit log app |
| Modalidade | ordem / jogo único | ordem auto; jogo único |
| Terminal | CRUD | — |
| Grade comissão | CRUD | CRUD (+ validações) |
| Vend. com. mod. | CRUD | — |
| Setorista área | INSERT/DELETE `cfg_coletor_area` | — |
| Mod. bilhetinho | update multiplicador | — |

---

*Documento gerado a partir da comparação de código entre allsystem (entities Angular + services Java) e zooloo (controllers Adianti + models). Atualizar este arquivo quando gaps forem fechados.*
