# Gaps — Zooloo

> Gerado pelo Revisor em 2026-05-01
> Severidade: 🔴 Crítico | 🟡 Moderado | ⚪ Cosmético

---

## Críticos 🔴

Lacunas que bloqueiam reimplementação ou representam riscos operacionais/de segurança sérios.

---

### GAP-01 — BilheteRestService: body e response não documentados corretamente

**Severidade:** 🔴 Crítico  
**Módulo:** `app/service/rest/BilheteRestService.php`  
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-03)

O body real do `registrar()` usa `{jogos: [{sorteio_id, modalidade_id, palpites[], colocacao_inicial, colocacao_final, valor_palpite}]}`, não o formato simplificado documentado. O response real retorna `{jb_id, bilhete_numero, string_autorizacao, total_bilhete, data_hora, vendedor_nome}`, sem `nsu` nem `previsao_premio`.

**Impacto:** Qualquer desenvolvedor que implemente o app baseado na spec atual vai integrar errado.  
**Pendente:** Pergunta 1 e 2 em `questions.md`

---

### GAP-02 — BilhetinhoRestService não implementado

**Severidade:** 🔴 Crítico  
**Módulo:** (ausente) `app/service/rest/BilhetinhoRestService.php`  
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-04), `user-stories/us-jogos-especiais.md` (US-JE-01)

Quando `cfg_parametros.ativo_bilhetinho=true`, o app vai tentar acessar um endpoint inexistente. Não há Active Record para `mov_bilhetinho`, `mov_bilhetinho_sorteio` ou `cad_modalidade_bilhetinho`.  
**Pendente:** Pergunta 12 em `questions.md`

---

### GAP-03 — Trigger de comissão não documentada

**Severidade:** 🔴 Crítico  
**Módulo:** `mov_jb_sorteio` (INSERT), `app/service/rest/BilheteRestService.php:131`  
**Spec afetada:** `flowcharts/rest-api.md`, `data-dictionary.md`

O código seta `comissao_sorteio = 0` com comentário "trigger calcula", mas a trigger responsável pelo cálculo de comissão no INSERT de `mov_jb_sorteio` não está documentada. Só as três triggers de cálculo de ganhadores foram mapeadas.  
**Pendente:** Pergunta 6 em `questions.md`

---

### GAP-04 — refreshToken aceita tokens expirados (vulnerabilidade de segurança)

**Severidade:** 🔴 Crítico  
**Módulo:** `app/service/rest/ApplicationAuthenticationRestService.php:163-215`  
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-01), `adrs/002-jwt-para-api-movel.md`

**Confirmado no código:** `refreshToken()` chama `JWT::decode()` mas o payload usa o campo customizado `expires` (não o padrão `exp` do JWT). A biblioteca firebase/jwt NÃO valida campos customizados — portanto tokens expirados passam na decodificação e recebem um novo token válido.  
Evidência: `ApplicationAuthenticationRestService.php:187` — sem checagem de `$decoded['expires'] < time()`.

---

### GAP-05 — login() retorna `expires_at` não `expires_in`

**Severidade:** 🔴 Crítico  
**Módulo:** `app/service/rest/ApplicationAuthenticationRestService.php:88-89`  
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-01)

A spec documenta `expires_in: 3600` (inteiro, segundos), mas o código retorna `expires_at: "YYYY-MM-DD HH:MM:SS"` (string datetime). Contradição direta entre spec e implementação.  
**Pendente:** Pergunta 3 em `questions.md`

---

### GAP-06 — Cancelamento: campos `pode_cancelar_tempo` e `pode_cancelar_qtde` não documentados

**Severidade:** 🔴 Crítico  
**Módulo:** `app/service/rest/BilheteRestService.php:222-248`  
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-03)

A spec documenta apenas `permite_cancelar` booleano, mas o código implementa:
- `pode_cancelar_tempo` (HH:MM:SS) — tempo máximo após emissão
- `pode_cancelar_qtde` (int) — limite de cancelamentos por dia

Dois critérios de controle não documentados.  
**Pendente:** Pergunta 9 em `questions.md`

---

### GAP-07 — ResultadoForm sem validação de hora_limite

**Severidade:** 🔴 Crítico  
**Módulo:** `app/control/resultado/ResultadoForm.php`  
**Spec afetada:** `user-stories/us-resultado-sorteio.md` (RN-RE-09)

Documentado como TODO no CLAUDE.md. Operador pode registrar resultado antes do horário do sorteio. Não há validação em nenhum nível (PHP, trigger, middleware).  
**Pendente:** Pergunta 15 em `questions.md`

---

### GAP-08 — `colocao_inicial`/`colocao_final` — possível typo no nome da coluna

**Severidade:** 🔴 Crítico (se for bug) | 🟡 Moderado (se for o nome real)  
**Módulo:** `app/service/rest/BilheteRestService.php:77,127-128`  
**Spec afetada:** `data-dictionary.md`

O código usa `colocao_inicial` e `colocao_final` (sem o 'c' de "colocacao"). Se o banco tem as colunas com este nome, está correto. Se não, é um bug silencioso onde os campos não são gravados.  
**Pendente:** Pergunta 7 em `questions.md`

---

## Moderados 🟡

Lacunas que impactam clareza ou cobertura mas não bloqueiam operação.

---

### GAP-09 — ModalidadeForm só expõe multiplicadorColocacao01

**Severidade:** 🟡 Moderado  
**Módulo:** `app/control/modalidade/ModalidadeForm.php:53`  
**Spec afetada:** `user-stories/us-jogos-especiais.md` (US-JE-05)

A entidade `Modalidade.php` mapeia corretamente todos os 5 campos `multiplicador_colocacao_01..05`. Mas o formulário só expõe o campo 01 na UI. Os campos 02-05 não são editáveis pela interface web. A spec (código-spec-matrix) marcava erroneamente como 🔴 a ausência no Active Record — correto é 🟢 no AR, mas 🔴 na UI do formulário.  
**Pendente:** Pergunta 5 em `questions.md`

---

### GAP-10 — MovJbSortPalpite: 10 colocações jogadas, 5 prêmios

**Severidade:** 🟡 Moderado  
**Módulo:** `app/service/rest/BilheteRestService.php:153-163`  
**Spec afetada:** `data-dictionary.md`, `user-stories/us-app-movel.md`

`MovJbSortPalpite` tem `jogou_colocacao_01..10` (10 campos) mas `premio_colocacao_01..05` (5 campos). A regra de negócio para apostas nas colocações 6-10 não está documentada.  
**Pendente:** Pergunta 8 em `questions.md`

---

### GAP-11 — ResultadoList usa `cad_extracao` em vez de `vw_sorteio`

**Severidade:** 🟡 Moderado  
**Módulo:** `app/control/resultado/ResultadoList.php`  
**Spec afetada:** `user-stories/us-resultado-sorteio.md` (US-RES-05)

UX degradada: combo de extração exibe todas as extrações cadastradas, não só as com sorteio aberto no dia. Não impede operação mas causa confusão.  
**Pendente:** Pergunta 13 em `questions.md`

---

### GAP-12 — Grade de Comissão: Won't — confirmar decisão

**Severidade:** 🟡 Moderado  
**Módulo:** `cfg_grade_comissao`, `cfg_grade_comissao_itens`  
**Spec afetada:** `user-stories/us-configuracoes.md` (US-CFG-08)

Classificado como "Won't" mas não confirmado explicitamente pelo stakeholder.  
**Pendente:** Pergunta 14 em `questions.md`

---

### GAP-13 — PERM_ALTERAR_SORTEIO_FECHADO inexistente

**Severidade:** 🟡 Moderado  
**Módulo:** `mov_sorteio` (situacao='F')  
**Spec afetada:** `user-stories/us-resultado-sorteio.md` (US-RES-01)

Não há mecanismo documentado para reabrir um sorteio fechado por engano. A spec referencia `PERM_ALTERAR_SORTEIO_FECHADO` como um GAP mas não confirma se a permissão existe.  
**Pendente:** Pergunta 11 em `questions.md`

---

### GAP-14 — MD5 sem plano de migração

**Severidade:** 🟡 Moderado  
**Módulo:** `GerenteForm.php`, `VendedorForm.php`, `ApplicationAuthenticationService.php`  
**Spec afetada:** `adrs/003-md5-para-senhas.md`

Vulnerabilidade confirmada e documentada no ADR-003. Não há prazo ou estratégia de migração para bcrypt/Argon2 documentados.  
**Pendente:** Pergunta 10 em `questions.md`

---

## Cosméticos ⚪

Lacunas de documentação sem impacto operacional imediato.

---

### GAP-15 — `getToken()` legacy não documentado

**Severidade:** ⚪ Cosmético  
**Módulo:** `app/service/rest/ApplicationAuthenticationRestService.php:267-276`

Método legado `getToken()` (alias de `login()`) presente no código mas não documentado em nenhuma spec. Pode estar em uso por integrações antigas.  
**Pendente:** Pergunta 4 em `questions.md`

---

### GAP-16 — `domain.md` insuficiente — 25 regras prometidas, poucas entregues

**Severidade:** ⚪ Cosmético  
**Módulo:** `_reversa_sdd/domain.md`

O checkpoint da fase de interpretação menciona "25 regras de negócio documentadas", mas o arquivo `domain.md` contém apenas um resumo de alto nível com poucos detalhes. As regras detalhadas estão distribuídas nos flowcharts, user stories e ADRs.

---

### GAP-17 — `rest_key` validação não documentada

**Severidade:** ⚪ Cosmético  
**Módulo:** Framework Adianti (AdiantiRestService)

A `rest_key: "zooloo_api_key_2025"` é exigida nas requisições REST mas onde ela é validada não foi documentado — provavelmente no middleware do Adianti, não em código do projeto.

---

## Resumo por Severidade

| Severidade | Quantidade |
|---|---|
| 🔴 Crítico | 8 |
| 🟡 Moderado | 6 |
| ⚪ Cosmético | 3 |
| **Total** | **17** |

## Gaps Já Resolvidos Nesta Revisão

| Gap original | Resolução |
|---|---|
| `Modalidade.php multiplicadorColocacao04+05 ausentes no AR` 🔴 | 🟢 Código confirmado: campos mapeados no AR (linhas 30-31). GAP real está na UI do Form. |
| `comissao_sorteio` calculado no PHP 🟡 | 🟢 Código confirmado: `= 0` com comment "trigger calcula" (linha 131). |
| `logout stateless` 🟡 | 🟢 Código confirmado: apenas registra no AccessLogService, não invalida token. |
