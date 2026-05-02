# User Stories — Configurações

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Módulos cobertos: AreaExtracao, AreaCotacao, AreaLimite, AreaComissaoModalidade,
> PalpiteCotado, ExtracaoDescarga, Parametros, GradeComissao

---

## US-CFG-01: Ativar/Desativar Extração por Área

**Como** administrador  
**Quero** controlar quais extrações estão disponíveis em cada área  
**Para que** eu possa habilitar ou desabilitar apostas por área e extração de forma independente

### Critérios de Aceitação

```gherkin
# Happy path — ativar extração para uma área
Dado que a extração "Tarde" não está ativa para a área "Centro"
Quando clico em "Ativar" para o par (Centro, Tarde) na tela AreaExtracaoList
Então um registro é inserido em cfg_area_extracao com area_id + extracao_id
E o status exibido muda para "Ativa" com badge verde

# Happy path — desativar extração para uma área
Dado que o par (Centro, Tarde) está ativo (registro existe em cfg_area_extracao)
Quando clico em "Desativar"
Então o registro é removido de cfg_area_extracao (hard DELETE — sem campo ativo)
E o status muda para "Inativa" com badge cinza

# Regra — modelo de presença, não de flag
Dado que quero verificar se extração está ativa para uma área
Então a presença do registro em cfg_area_extracao = ativa
E ausência do registro = inativa (não há campo ativo — é o próprio registro que representa o estado)

# Proteção — não remove se há apostas abertas
Dado que há sorteios abertos com apostas para o par (Centro, Tarde)
Quando tento desativar
Então o sistema deve alertar sobre o impacto (comportamento a implementar) 🔴
```

### Regras de Negócio Relacionadas

- RN: Ativação = INSERT em `cfg_area_extracao`; Desativação = DELETE — sem campo `ativo` 🟢
- RN: A API REST `ModalidadeRestService` filtra extrações disponíveis para o app usando `cfg_area_extracao` 🟢
- RN: Uma área pode ter qualquer subconjunto das extrações cadastradas ativadas 🟢

### Dependências

- `cad_area`, `cad_extracao`, `cfg_area_cotacao` (cotação depende da extração estar ativa)

### Prioridade

**Must** — controla diretamente quais apostas são aceitas em cada região.

---

## US-CFG-02: Configurar Cotação por Área, Extração e Modalidade

**Como** administrador  
**Quero** definir o multiplicador de prêmio para cada combinação de área/extração/modalidade  
**Para que** o sistema calcule corretamente o valor a pagar ao apostador ganhador

### Critérios de Aceitação

```gherkin
# Happy path — criar cotação global (sem extração específica)
Dado que seleciono área=Centro, extração=NENHUMA (null), modalidade=Milhar, cotacao=4000
Quando salvo
Então registro criado em cfg_area_cotacao com extracao_id=null
E esta cotação vale para todas as extrações da área "Centro" para Milhar

# Happy path — criar cotação específica por extração
Dado que existe cotação global para (Centro, null, Milhar) = 4000
E seleciono área=Centro, extração=Tarde, modalidade=Milhar, cotacao=3500
Quando salvo
Então registro criado com extracao_id=3
E a cotação específica (3500) sobrepõe a global (4000) para a extração Tarde

# Falha — combinação duplicada
Dado que já existe cotação para (Centro, Tarde, Milhar)
Quando tento criar outra cotação para (Centro, Tarde, Milhar)
Então erro 409 "Cotação já cadastrada para esta combinação"

# Regra — prioridade no app
Dado que existe cotação global E cotação específica para uma área/extração/modalidade
Quando o app consulta a cotação via ModalidadeRestService
Então a cotação específica (extracao_id preenchido) tem prioridade sobre a global (null)
```

### Regras de Negócio Relacionadas

- RN: `extracao_id = null` define cotação global para a área/modalidade 🟢
- RN: Cotação específica (`extracao_id` preenchido) sobrepõe a global — lógica em `ModalidadeRestService` 🟢
- RN: 409 em duplicidade verificado no create 🟢
- RN: `cotacao` deve ser > 0 (multiplicador de prêmio) 🟡

### Dependências

- `cad_area`, `cad_extracao`, `cad_modalidade`, `ModalidadeRestService` (consume esta configuração)

### Prioridade

**Must** — sem cotação configurada, o app não consegue exibir o prêmio potencial ao apostador.

---

## US-CFG-03: Configurar Limite de Aposta por Área e Modalidade

**Como** administrador  
**Quero** definir o valor máximo de aposta por modalidade em cada área  
**Para que** o sistema recuse apostas acima do limite configurado

### Critérios de Aceitação

```gherkin
# Happy path — criar limite
Dado que seleciono área=Centro, modalidade=Milhar, limite=500.00
Quando salvo
Então registro criado em cfg_area_limite
E o app recusa apostas acima de R$ 500,00 para Milhar na área Centro

# Regra — unicidade somente no create
Dado que já existe limite para (Centro, Milhar)
Quando crio novo registro para (Centro, Milhar)
Então erro 409 "Limite já configurado para esta combinação"
E ao editar o registro existente não há verificação de duplicidade (apenas no create)

# Falha — limite inválido
Quando preencho limite=0 ou negativo
Então validação exige valor > 0

# Happy path — editar limite existente
Dado que limite para (Centro, Milhar) é 500.00
Quando edito e altero para 800.00
Então cfg_area_limite atualizado sem verificação de duplicidade
```

### Regras de Negócio Relacionadas

- RN: Uniqueness verificada apenas no **create** — não no edit 🟢
- RN: Ausência de registro = sem limite configurado (apostas ilimitadas) 🟡
- RN: Limite é por área + modalidade; não há granularidade por extração 🟢

### Dependências

- `cad_area`, `cad_modalidade`, `BilheteRestService` (valida limite ao registrar aposta)

### Prioridade

**Must** — controle de risco financeiro essencial.

---

## US-CFG-04: Configurar Comissão por Área e Modalidade

**Como** administrador  
**Quero** definir o percentual de comissão do vendedor por área e modalidade  
**Para que** o sistema calcule automaticamente a comissão ao registrar cada aposta

### Critérios de Aceitação

```gherkin
# Happy path — criar comissão
Dado que seleciono área=Centro, modalidade=Milhar, comissao=10 (%)
Quando salvo
Então registro criado em cfg_area_comissao_modalidade
E apostas de Milhar na área Centro geram 10% de comissão para o vendedor

# Regra — unicidade somente no create
Dado que já existe comissão para (Centro, Milhar)
Quando tento criar outra para (Centro, Milhar)
Então erro 409 "Comissão já configurada para esta combinação"

# Validação de percentual
Quando preencho comissao=150 (acima de 100%)
Então validação rejeita valores fora do intervalo 0–100

# Happy path — editar comissão
Dado que comissão de (Centro, Milhar) é 10%
Quando edito para 12%
Então cfg_area_comissao_modalidade atualizado sem verificação de duplicidade
```

### Regras de Negócio Relacionadas

- RN: `comissao` é percentual (0–100%) 🟢
- RN: Uniqueness verificada apenas no **create** 🟢
- RN: Comissão calculada no momento do registro da aposta com base nesta configuração 🟡

### Dependências

- `cad_area`, `cad_modalidade`, `mov_jb_sorteio` (campo `comissao_sorteio`)

### Prioridade

**Must** — base para cálculo de comissão e relatórios financeiros.

---

## US-CFG-05: Configurar Palpite Cotado (cotação especial por número)

**Como** administrador  
**Quero** definir uma cotação especial para um palpite específico em uma extração  
**Para que** determinados números paguem prêmios diferentes dos demais

### Critérios de Aceitação

```gherkin
# Happy path — criar palpite cotado
Dado que preencho extracao=Tarde, modalidade=Milhar, palpite="1234", cotacao=2000, ativo=true
Quando salvo
Então registro criado em cfg_palpite_cotado
E o app usa cotação 2000 para palpite "1234" na Tarde (em vez da cotação padrão da AreaCotacao)

# Validação — palpite deve ter 4 dígitos
Quando preencho palpite="123" (3 dígitos)
Então validação "Palpite deve ter 4 dígitos" impede o save

# Regra — unicidade no create E no edit
Dado que já existe palpite cotado para (Tarde, Milhar, "1234")
Quando crio outro palpite cotado para (Tarde, Milhar, "1234")
Então erro 409 "Palpite já cotado para esta combinação"
E quando edito o registro existente e altero o palpite para "1234" de um registro diferente
Então erro 409 também é verificado no edit

# Happy path — toggle ativo
Dado que palpite cotado "1234" está ativo
Quando clico em "Inativar"
Então ativo=false e o palpite volta a usar cotação padrão
```

### Regras de Negócio Relacionadas

- RN: Palpite deve ter exatamente 4 dígitos (`^[0-9]{4}$`) 🟢
- RN: Uniqueness verificada no **create E no edit** — diferente de AreaLimite e AreaComissaoModalidade 🟢
- RN: `cotacao` é o multiplicador do prêmio (0–100 ou valor positivo) 🟡
- RN: `ativo` controla se a cotação especial está em vigor 🟢

### Dependências

- `cad_extracao`, `cad_modalidade`, `ModalidadeRestService` (verifica palpite cotado ao exibir cotação)

### Prioridade

**Should** — funcionalidade de diferenciação de prêmios para números específicos.

---

## US-CFG-06: Configurar Descarga de Extração (limite de risco por número)

**Como** administrador  
**Quero** definir o limite de apostas em um mesmo número por extração e modalidade  
**Para que** o sistema identifique apostas que ultrapassaram o limite (descarrego)

### Critérios de Aceitação

```gherkin
# Happy path — criar limite de descarga
Dado que seleciono extracao=Tarde, modalidade=Milhar, limite_descarga=1000.00
Quando salvo
Então registro criado em cfg_extracao_descarga
E apostas no mesmo número que somem > R$ 1000 geram entrada no Descarrego

# Validação — limite deve ser positivo
Quando preencho limite_descarga=0 ou negativo
Então validação "Limite deve ser maior que zero" impede o save

# Regra — unicidade no create E no edit
Dado que já existe descarga para (Tarde, Milhar)
Quando crio outra descarga para (Tarde, Milhar)
Então erro 409 "Descarga já configurada para esta combinação"
E ao editar e tentar trocar para combinação já existente
Então erro 409 também verificado no edit

# Consequência — apostas acima do limite
Dado que limite_descarga=1000 para (Tarde, Milhar)
E total apostado no palpite "1234" na Tarde atingiu R$ 1200
Então o palpite aparece na tela de Descarrego para processamento
```

### Regras de Negócio Relacionadas

- RN: `limite_descarga` > 0 — validação obrigatória 🟢
- RN: Uniqueness verificada no **create E no edit** — mesmo padrão do PalpiteCotado 🟢
- RN: Descarrego é acionado quando `SUM(valor_apostado_por_numero)` > `limite_descarga` 🟡

### Dependências

- `cad_extracao`, `cad_modalidade`, módulo Descarrego (consume esta configuração)

### Prioridade

**Must** — controle de risco crítico; sem ele não há limite para exposição financeira.

---

## US-CFG-07: Configurar Parâmetros Gerais da Banca

**Como** administrador  
**Quero** ajustar os parâmetros globais do sistema (nome da banca, jogos ativos, limites)  
**Para que** o sistema e o app móvel reflitam as configurações corretas da banca

### Critérios de Aceitação

```gherkin
# Happy path — visualizar parâmetros
Dado que acesso a tela ParametrosList
Então o único registro existente em cfg_parametros é exibido
E o formulário abre diretamente em modo de edição (sem tela de listagem separada)

# Happy path — atualizar nome da banca
Dado que nome_banca="Banca Zooloo"
Quando altero para "Banca Zooloo Premium" e salvo
Então cfg_parametros atualizado
E o app móvel exibe o novo nome no cabeçalho (via campo parametro.nomeBanca) 🟢

# Happy path — habilitar/desabilitar jogo
Dado que ativo_quininha=false
Quando altero ativo_quininha=true e salvo
Então o app exibe Quininha no combo de tipos de jogo
E relatórios passam a incluir Quininha no combo Tipo

# Regra — singleton
Dado que existe exatamente 1 registro em cfg_parametros
Quando acesso a tela
Então NÃO há botão "Novo" — somente edição do registro existente
E a API expõe apenas GET (sem POST) para este recurso 🟢

# Regra — limite_global
Dado que limite_global está configurado
Quando apostador tenta apostar acima do limite global
Então a aposta é recusada independente da modalidade 🟡
```

### Regras de Negócio Relacionadas

- RN: Singleton — sempre exatamente 1 registro; sem endpoint de criação 🟢
- RN: Feature flags `ativo_jb`, `ativo_quininha`, `ativo_seninha`, `ativo_bilhetinho` controlam jogos disponíveis no combo Tipo dos relatórios 🟢
- RN: `nome_banca` exibido no cabeçalho do PDF de Descarrego 🟢
- RN: `limite_global` inferido como limite máximo de aposta independente de modalidade 🟡

### Dependências

- App móvel (consume `nome_banca`), relatórios (consomem feature flags), Descarrego PDF

### Prioridade

**Must** — configuração base do sistema; afeta comportamento do app e de todos os relatórios.

---

## US-CFG-08: Configurar Grade de Comissão (GAP)

**Como** administrador  
**Quero** definir grades de comissão progressiva por faixa de valor de vendas  
**Para que** vendedores que vendem mais recebam percentuais maiores de comissão

### Critérios de Aceitação

```gherkin
# Estado atual — GAP
Dado que as tabelas cfg_grade_comissao e cfg_grade_comissao_itens existem no banco
Quando acesso o sistema Zooloo
Então não há tela, entidade PHP, controller ou formulário para grade de comissão
E nenhum FK ativo aponta para essas tabelas

# Comportamento esperado (a implementar)
Dado que crio uma grade com itens: 0–1000=5%, 1001–5000=8%, 5001+=10%
E associo a grade a um vendedor
Quando o sistema calcula a comissão do vendedor no período
Então aplica o percentual da faixa correspondente ao total vendido

# Dependência identificada
Dado que a grade existe no banco (dados legados do Java)
Quando implementar a tela no Zooloo
Então deve criar entidades PHP GradeComissao + GradeComissaoItens com FK correto
```

### Regras de Negócio Relacionadas

- RN: Tabelas `cfg_grade_comissao` e `cfg_grade_comissao_itens` existem no banco mas sem consumidor ativo 🔴
- RN: Estrutura inferida a partir do padrão do sistema Java — sem análise direta do schema 🔴

### Dependências

- `cfg_grade_comissao`, `cfg_grade_comissao_itens` (tabelas DB), `cad_vendedor` (associação esperada)

### Prioridade

**Should** — implementação aprovada pelo stakeholder em 2026-05-01. O sistema Java tinha Grade de Comissão implementada. Implementar após os módulos Must.

---

## Resumo de Padrões de Uniqueness

| Módulo | Verifica no Create | Verifica no Edit |
|---|---|---|
| AreaCotacao | Sim | Sim |
| AreaLimite | Sim | **Não** |
| AreaComissaoModalidade | Sim | **Não** |
| PalpiteCotado | Sim | **Sim** |
| ExtracaoDescarga | Sim | **Sim** |

> Este padrão inconsistente é herança do sistema Java e deve ser documentado na especificação
> de cada módulo para evitar divergências na reimplementação. 🟢

---

## Rastreabilidade de Código

| User Story | Controller | Model | Spec SDD |
|---|---|---|---|
| US-CFG-01 | `app/control/area-extracao/AreaExtracaoList.php` | `app/model/entities/AreaExtracao.php` | `sdd/area-extracao.md` |
| US-CFG-02 | `app/control/area-cotacao/AreaCotacaoForm.php` | `app/model/entities/AreaCotacao.php` | `sdd/area-cotacao.md` |
| US-CFG-03 | `app/control/area-limite/AreaLimiteForm.php` | `app/model/entities/AreaLimite.php` | `sdd/area-limite.md` |
| US-CFG-04 | `app/control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php` | `app/model/entities/AreaComissaoModalidade.php` | `sdd/area-comissao-modalidade.md` |
| US-CFG-05 | `app/control/palpite-cotado/PalpiteCotadoForm.php` | `app/model/entities/PalpiteCotado.php` | `sdd/palpite-cotado.md` |
| US-CFG-06 | `app/control/ExtracaoDescarga/ExtracaoDescargaForm.php` | `app/model/entities/ExtracaoDescarga.php` | `sdd/extracao-descarga.md` |
| US-CFG-07 | `app/control/parametros/ParametrosForm.php` | `app/model/entities/Parametros.php` | `sdd/parametros.md` |
| US-CFG-08 | (a criar) | (a criar) | `sdd/gap-grade-comissao.md` |
