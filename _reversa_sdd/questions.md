# Perguntas para Validação — Zooloo

> Gerado pelo Revisor em 2026-05-01
> Responda cada pergunta e me avise quando terminar (basta digitar `continuar`).
> Revisão cruzada: não realizada (Codex indisponível nesta sessão)

---

## Pergunta 1

**Contexto:** `app/service/rest/BilheteRestService.php:173-180` — response do `registrar()`
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-03)
**Pergunta:** A spec diz que o response de `BilheteRestService::registrar` retorna `{nsu, bilhete_numero, total, previsao_premio}`, mas o código real retorna `{jb_id, bilhete_numero, string_autorizacao, total_bilhete, data_hora, vendedor_nome}`. Não há `nsu` nem `previsao_premio`. O que é `string_autorizacao` exatamente — é o NSU do sistema Java traduzido? O app móvel usa `jb_id` como identificador do bilhete?
**Impacto:** Contrato da API documentado incorretamente. A spec precisa ser corrigida com o response real.

**Resposta:** jb_id é o identificador do bilhete e é o id da tabela mov_jb. string_autorizacao é jb_id em formato de md5, retorne o que está no código real, o app móvel ainda não foi criado, irei criar depois que terminar o projeto zooloo, que também será o backend do app móvel.

---

## Pergunta 2

**Contexto:** `app/service/rest/BilheteRestService.php:19-31` — body do `registrar()`
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-03)
**Pergunta:** A spec documenta o body como `{sorteio, modalidade, palpite, valor}` (simplificado), mas o código real espera `{terminal_id, nome_cliente, fone_cliente, jogos: [{sorteio_id, modalidade_id, palpites[], colocacao_inicial, colocacao_final, valor_palpite}]}`. O app móvel usa exatamente esta estrutura com array `jogos`? E `colocacao_inicial`/`colocacao_final` precisam ser sempre informados?
**Impacto:** Body da API documentado incorretamente. US-APP-03 e qualquer spec de integração precisam ser corrigidos.

**Resposta:** app móvel ainda não foi criado, deixe o body como está no código real.

---

## Pergunta 3

**Contexto:** `app/service/rest/ApplicationAuthenticationRestService.php:88-89`
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-01)
**Pergunta:** A spec diz que o login retorna `expires_in: 3600` (inteiro, segundos), mas o código real retorna `expires_at: "YYYY-MM-DD HH:MM:SS"` (string datetime). Qual campo o app móvel realmente usa para controlar a expiração do token?
**Impacto:** Contrato da API incorreto em US-APP-01. Corrigir para `expires_at`.

**Resposta:** O app móvel irá seguir o padrão JWT.

---

## Pergunta 4

**Contexto:** `app/service/rest/ApplicationAuthenticationRestService.php:267-276` — método `getToken()`
**Spec afetada:** `user-stories/us-app-movel.md`, `openapi/spec-resultado.yaml`
**Pergunta:** Existe um método legado `getToken()` (alias de `login()`) no service de autenticação, com comentário "usado pelos exemplos em /rest". Este endpoint ainda é usado por algum cliente (app móvel, integração, dashboard)? Deve ser documentado nas specs ou está destinado à remoção?
**Impacto:** Se ainda em uso, precisa constar nas specs de autenticação como endpoint legado.

**Resposta:** se ele não for usado, pode ser removido das specs.

---

## Pergunta 5

**Contexto:** `app/control/modalidade/ModalidadeForm.php:53` — apenas `multiplicadorColocacao01` no formulário
**Spec afetada:** `user-stories/us-jogos-especiais.md` (US-JE-05), `traceability/code-spec-matrix.md`
**Pergunta:** `Modalidade.php` (Active Record) mapeia todos os 5 campos `multiplicador_colocacao_01..05` corretamente. Mas `ModalidadeForm.php` só expõe `multiplicadorColocacao01` na UI (como "Valor Palpite"). Como são preenchidos os campos `multiplicadorColocacao02..05` em produção — via INSERT direto no banco, migration, ou existe outro formulário?
**Impacto:** A 🔴 estava errada sobre o AR (que está correto). A lacuna real é na UI do formulário, não na entidade.

**Resposta:** Por enquanto só irei usar o multiplicador 1, pois é o valor padrão, porém futuramente pode ser necessário usar os outros multiplicadores.

---

## Pergunta 6

**Contexto:** `app/service/rest/BilheteRestService.php:131` — `$jbSorteio->comissao_sorteio = 0; // trigger calcula`
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-03), `flowcharts/rest-api.md`
**Pergunta:** A comissão em `mov_jb_sorteio.comissao_sorteio` é zerada no INSERT e calculada por uma trigger. Qual é o nome desta trigger? Ela opera sobre `mov_jb_sorteio` no momento do INSERT? Esta trigger não está documentada nas specs (apenas as três de cálculo de ganhadores foram documentadas).
**Impacto:** Uma trigger de comissão não documentada — pode ser crítica para o negócio.

**Resposta:** Não sei bem  qual é a trigger de comissão, mas você pode ver no banco de dados referência, entrando com as credentials do banco de dados 
    'host'  =>  "postgres",
    'port'  =>  "5432",
    'name'  =>  "jb",
    'user'  =>  "postgres",
    'pass'  =>  "postgres",

---

## Pergunta 7

**Contexto:** `app/service/rest/BilheteRestService.php:77` — `'colocao_inicial'` (falta o 'c' em colocacao)
**Spec afetada:** `traceability/code-spec-matrix.md`
**Pergunta:** O código PHP e a inserção em `mov_jb_sorteio` usam o campo `colocao_inicial` e `colocao_final` (sem o 'c' de 'colocacao'). Este é o nome real das colunas no banco de dados PostgreSQL, ou é um bug no PHP que deveria ser `colocacao_inicial`?
**Impacto:** Se for o nome real no banco, a spec está correta. Se for bug, há risco de falha silenciosa no INSERT (coluna não encontrada ou valor perdido).

**Resposta:** O nome das colunas no banco de dados é esse mesmo, sem o 'c' de 'colocacao'. quando foi criado o banco de dados, o nome das colunas era sem o 'c' de 'colocacao'.

---

## Pergunta 8

**Contexto:** `app/service/rest/BilheteRestService.php:153-163` — `jogou_colocacao_01..10` e `premio_colocacao_01..05`
**Spec afetada:** `user-stories/us-app-movel.md`, `data-dictionary.md`
**Pergunta:** `MovJbSortPalpite` tem campos `jogou_colocacao_01..10` (10 colocações jogadas) mas apenas `premio_colocacao_01..05` (5 prêmios). O sistema suporta apostas nas colocações 6 a 10, mas os prêmios só vão até a 5ª. Qual é a regra de negócio para apostas nas colocações 6-10 — são sem premiação ou usam outra tabela?
**Impacto:** Regra de negócio não documentada que afeta o cálculo de prêmios.

**Resposta:** usa colocação do 1 ao 10, vai depender da configuração do sistema.

---

## Pergunta 9

**Contexto:** `app/service/rest/BilheteRestService.php:222-235` — `pode_cancelar_tempo` e `pode_cancelar_qtde`
**Spec afetada:** `user-stories/us-app-movel.md` (US-APP-03), `data-dictionary.md`
**Pergunta:** O cancelamento de bilhete valida dois critérios: `pode_cancelar_tempo` (tempo máximo após emissão, formato HH:MM:SS) e `pode_cancelar_qtde` (quantidade máxima de cancelamentos por dia). Esses campos estão no `cad_vendedor`? A spec documenta apenas `permite_cancelar` booleano, sem mencionar os limites de tempo e quantidade.
**Impacto:** A spec de cancelamento (US-APP-03) está incompleta — faltam estes dois campos de controle.

**Resposta:**  pode adicionar os campos que estão faltando.

---

## Pergunta 10

**Contexto:** Múltiplos specs — `adrs/003-md5-para-senhas.md`, `us-cadastros.md` (US-CAD-02, US-CAD-05)
**Spec afetada:** `adrs/003-md5-para-senhas.md`
**Pergunta:** MD5 sem salt para senhas de gerentes e vendedores é uma vulnerabilidade documentada (ADR-003). Existe algum prazo ou plano para migrar para bcrypt ou Argon2? O `ApplicationAuthenticationService::authenticate()` usa MD5 atualmente?
**Impacto:** Se houver plano de migração, o ADR-003 precisa ser atualizado com a decisão e estratégia de transição.

**Resposta:** altere para bcrypt.

---

## Pergunta 11

**Contexto:** `us-resultado-sorteio.md` (US-RES-01), `flowcharts/resultado.md`
**Spec afetada:** `user-stories/us-resultado-sorteio.md`
**Pergunta:** A spec documenta "PERM_ALTERAR_SORTEIO_FECHADO" como uma permissão que permitiria reabrir sorteios fechados (GAP). Esta permissão existe no sistema de permissões do Adianti (tabela `system_permissions`)? Ou como o admin atualmente reabre um sorteio fechado por engano — direto no banco?
**Impacto:** Se não existir, o GAP é total — não há forma de reabrir. Confirmar o fluxo real de suporte/correção.

**Resposta:** PERM_ALTERAR_SORTEIO_FECHADO é do sistema original, não existe no Zooloo, use alguma do sistema adianti.

---

## Pergunta 12

**Contexto:** `us-jogos-especiais.md` (US-JE-01), `user-stories/us-app-movel.md` (US-APP-04)
**Spec afetada:** Múltiplas specs
**Pergunta:** `BilhetinhoRestService.php` não existe — apostas de Bilhetinho via app móvel não funcionam atualmente. Quando está planejada a implementação? Existe alguma especificação funcional do sistema Java para o Bilhetinho que sirva de base?
**Impacto:** Se `cfg_parametros.ativo_bilhetinho=true`, o app vai tentar usar um endpoint inexistente.

**Resposta:** Bilhetinho ainda não tem previsão de implementação. Deixe off.

---

## Pergunta 13

**Contexto:** `us-resultado-sorteio.md` (US-RES-05), `code-spec-matrix.md`
**Spec afetada:** `user-stories/us-resultado-sorteio.md`
**Pergunta:** `ResultadoList.php` usa `cad_extracao` diretamente no combo de extração em vez de `vw_sorteio` (view de sorteios abertos). O fix está no backlog? Existe a view `vw_sorteio` no banco de dados, ou também precisa ser criada?
**Impacto:** Sem o fix, o operador vê todas as extrações cadastradas, não só as com sorteio aberto no dia.

**Resposta:** essa view existe no banco de dados original, falta implementar no Zooloo.

---

## Pergunta 14

**Contexto:** `us-configuracoes.md` (US-CFG-08)
**Spec afetada:** `user-stories/us-configuracoes.md`
**Pergunta:** Grade de Comissão (`cfg_grade_comissao` e `cfg_grade_comissao_itens`) foi classificada como "Won't" — não implementada no Zooloo. Esta é uma decisão definitiva? O sistema Java tinha grade de comissão implementada?
**Impacto:** Se for Won't definitivo, pode ser removida do backlog. Se for deferrido, deve estar no roadmap.

**Resposta:** implemente no zooloo também

---

## Pergunta 15

**Contexto:** `us-resultado-sorteio.md` (US-RES-01), `CLAUDE.md` (TODO)
**Spec afetada:** `user-stories/us-resultado-sorteio.md`
**Pergunta:** `ResultadoForm.php` não valida a `hora_limite` da extração antes de salvar o resultado — isso está no TODO do CLAUDE.md. Existe alguma lógica de validação de horário em outro lugar (banco, trigger, middleware)? Ou o operador pode salvar resultado qualquer hora?
**Impacto:** Se não houver validação em nenhum lugar, há risco operacional real.

**Resposta:** hora_limite é a hora limite que a extração pode receber apostas.

