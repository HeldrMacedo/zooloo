# User Stories — Jogos Especiais

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Módulos cobertos: Bilhetinho, Quininha, Seninha, Lotinha, Milhar Premiada —
> configuração de modalidades e ciclo de apostas/resultado

---

## Contexto

O Zooloo suporta cinco jogos além do Jogo do Bicho clássico. Cada jogo tem
sua própria estrutura de modalidade, regras de cotação e trigger de cálculo
de ganhadores. Este documento cobre a perspectiva de **configuração** e
**ciclo operacional** de cada jogo especial — a perspectiva do vendedor
(app móvel) está em `us-app-movel.md`.

---

## US-JE-01: Configurar Modalidade de Bilhetinho

**Como** administrador do sistema  
**Quero** configurar os multiplicadores de prêmio do Bilhetinho por colocação  
**Para que** o sistema calcule corretamente os prêmios nas 5 colocações do sorteio

### Critérios de Aceitação

```gherkin
# Happy path — listar configurações de bilhetinho
Dado que acesso a tela de Bilhetinho
Quando clico em Buscar
Então o datagrid exibe as configurações agrupadas por: jogo, extração, modalidade
E cada linha mostra os multiplicadores das 5 colocações (col_1 a col_5)
E os dados vêm de cad_modalidade_bilhetinho (PK composta: jogo_id + extracao_id + modalidade_id)

# Happy path — salvar configuração
Dado que seleciono jogo=BIL, extração=Tarde, modalidade=Milhar
E preencho multiplicador_1=4000, multiplicador_2=2000, multiplicador_3=1000, multiplicador_4=500, multiplicador_5=250
Quando clico em Salvar
Então os 5 valores são gravados: uma linha por colocação (colocacao=1..5) em cad_modalidade_bilhetinho
E o UI apresenta isso como campos separados de uma tela-formulário única 🟡

# Regra — PK composta sem auto-increment
Dado que cad_modalidade_bilhetinho tem PK = (jogo_id, extracao_id, modalidade_id, colocacao)
Então não há campo de ID serial — a entidade PHP usa IDPOLICY='manual' ou equivalente 🔴
E uma atualização exige DELETE + INSERT ou UPDATE explícito

# Falha — valores negativos
Dado que preencho multiplicador_1=-1
Quando clico em Salvar
Então validação rejeita valores negativos
E mensagem: "Multiplicador deve ser um valor positivo"

# Falha — cobertura incompleta
Dado que cfg_parametros.ativo_bilhetinho=true
E cad_modalidade_bilhetinho não tem configuração para uma extração ativa
Quando vendedor tenta apostar nessa extração
Então o sistema de apostas não deve exibir a opção Bilhetinho para aquela extração 🟡
```

### Regras de Negócio Relacionadas

- `cad_modalidade_bilhetinho` tem PK composta — sem IDPOLICY='serial' 🔴
- Colocações 1–5 são linhas distintas no banco mas apresentadas como campos únicos na UI 🟡
- `cfg_parametros.ativo_bilhetinho` controla se o jogo aparece no sistema 🟢
- `BilhetinhoRestService` não implementado — apostas via app não funcionam 🔴

### Dependências

- `cad_modalidade_bilhetinho` (sem entidade PHP Active Record implementada) 🔴
- `cfg_parametros.ativo_bilhetinho`
- `int_jogo` (tipo BIL)

### Prioridade

**Must** — sem configuração, o jogo não pode ser apostado mesmo com a feature flag ativa.

---

## US-JE-02: Configurar Modalidade de Quininha

**Como** administrador do sistema  
**Quero** configurar os multiplicadores de prêmio da Quininha (Quina, Quadra, Terno)  
**Para que** o sistema calcule automaticamente os prêmios ao encerrar o sorteio

### Critérios de Aceitação

```gherkin
# Happy path — visualizar configuração
Dado que acesso a tela de Quininha
Quando clico em Buscar
Então vejo as configurações de modalidade de Quininha com: extracao, quina_valor, quadra_valor, terno_valor
E os dados vêm de cad_modalidade via jogo_id='QUI'

# Happy path — editar multiplicadores
Dado que seleciono a extração "Tarde" na configuração da Quininha
Quando altero quina_valor=500000, quadra_valor=5000, terno_valor=500
E clico em Salvar
Então a linha correspondente em cad_modalidade é atualizada
E na próxima apuração, os ganhadores recebem esses valores

# Regra — 5 dezenas exatas
Dado que a configuração exige 5 dezenas sorteadas
Quando o operador vai lançar resultado
Então a interface aceita apenas exatamente 5 pares (máscara "00,00,00,00,00")

# Regra — categorias de prêmio da Quininha
| Acertos | Categoria | Prêmio       |
|---------|-----------|--------------|
| 5       | Quina     | quina_valor  |
| 4       | Quadra    | quadra_valor |
| 3       | Terno     | terno_valor  |
E acertou menos de 3 dezenas → sem prêmio

# Falha — feature flag desativada
Dado que cfg_parametros.ativo_quininha=false (ou equivalente)
Quando vendedor acessa o app
Então Quininha não aparece como opção de aposta 🟡
```

### Regras de Negócio Relacionadas

- `cad_modalidade` com `jogo_id='QUI'` armazena os multiplicadores por extração 🟢
- Trigger `trg_mv_sorteio_verifica_ganhadores_qui_sen` compartilhada com Seninha 🟢
- Resultado: exatamente 5 dezenas no formato `DD,DD,DD,DD,DD` 🟢
- Prêmios: Quina (5) / Quadra (4) / Terno (3) 🟢

### Dependências

- `cad_modalidade` (jogo_id='QUI'), `int_jogo`
- `mov_sorteio.numeros_sorteados` (resultado)
- Trigger `trg_mv_sorteio_verifica_ganhadores_qui_sen`
- `mov_jb` (ou tabela específica — verificar) 🟡

### Prioridade

**Must** — sem configuração correta, prêmios da Quininha são calculados incorretamente.

---

## US-JE-03: Configurar Modalidade de Seninha

**Como** administrador do sistema  
**Quero** configurar os multiplicadores da Seninha (Sena, Quina, Quadra)  
**Para que** o sistema calcule automaticamente os prêmios das 6 dezenas sorteadas

### Critérios de Aceitação

```gherkin
# Happy path — visualizar e editar configuração
Dado que acesso a tela de Seninha
Quando clico em Buscar
Então vejo configurações com: extracao, sena_valor, quina_valor, quadra_valor
E os dados vêm de cad_modalidade via jogo_id='SEN'

# Regra — 6 dezenas exatas
Dado que a configuração exige 6 dezenas sorteadas
Quando o operador vai lançar resultado
Então a interface aceita apenas exatamente 6 pares (máscara "00,00,00,00,00,00")

# Regra — categorias de prêmio da Seninha
| Acertos | Categoria | Prêmio      |
|---------|-----------|-------------|
| 6       | Sena      | sena_valor  |
| 5       | Quina     | quina_valor |
| 4       | Quadra    | quadra_valor|
E acertou menos de 4 dezenas → sem prêmio
```

### Diferenças Quininha vs Seninha

| Aspecto | Quininha | Seninha |
|---|---|---|
| `jogo_id` | `'QUI'` | `'SEN'` |
| Dezenas resultado | 5 | 6 |
| Prêmios | Quina/Quadra/Terno | Sena/Quina/Quadra |
| Trigger | `trg_mv_sorteio_verifica_ganhadores_qui_sen` | idem (compartilhada) |
| Mínimo de acertos premiados | 3 | 4 |

### Dependências

- `cad_modalidade` (jogo_id='SEN'), `int_jogo`
- Trigger `trg_mv_sorteio_verifica_ganhadores_qui_sen`

### Prioridade

**Must** — sem configuração correta, prêmios da Seninha não são calculados.

---

## US-JE-04: Configurar Modalidade de Lotinha

**Como** administrador do sistema  
**Quero** configurar a cotação única da Lotinha  
**Para que** o sistema calcule automaticamente os prêmios ao encerrar o sorteio

### Critérios de Aceitação

```gherkin
# Happy path — visualizar e editar configuração
Dado que acesso a tela de Lotinha
Quando clico em Buscar
Então vejo configurações com: extracao, cotacao_unica (sem categorias de acerto)
E os dados vêm de cad_modalidade via jogo_id='LOT'

# Regra — 15 dezenas exatas
Dado que a configuração exige 15 dezenas sorteadas
Quando o operador vai lançar resultado
Então a interface aceita apenas exatamente 15 pares (44 caracteres com separadores)

# Regra — cotação única sem categorias
Dado que o apostador acertou o número sorteado
Quando o sorteio é encerrado
Então ele recebe cotacao_unica vezes o valor apostado
E não há categorias (Quina/Quadra/Terno etc.) — é acerto ou não 🟢

# Regra — invalidConfiguration() bloqueia salvamento
Dado que a configuração da Lotinha está incompleta ou inválida
Quando o operador tenta salvar o resultado
Então validação invalidConfiguration() impede o save com mensagem de erro
E o operador deve corrigir a configuração antes de prosseguir

# Regra — trigger própria
Dado que o sorteio da Lotinha é encerrado
Quando situacao='F' é gravado
Então trigger trg_mv_sorteio_verifica_ganhadores_lotinha executa (não a de Quininha/Seninha)
E o cálculo usa cotacao_unica em vez de múltiplos multiplicadores
```

### Diferenças Lotinha vs Quininha/Seninha

| Aspecto | Quininha/Seninha | Lotinha |
|---|---|---|
| `jogo_id` | `'QUI'` ou `'SEN'` | `'LOT'` |
| Dezenas | 5 ou 6 | **15** |
| Prêmios | Múltiplas categorias | **Cotação única** |
| Validação extra | Não | `invalidConfiguration()` |
| Trigger | `trg_mv_sorteio_verifica_ganhadores_qui_sen` | **`trg_mv_sorteio_verifica_ganhadores_lotinha`** |

### Dependências

- `cad_modalidade` (jogo_id='LOT'), `int_jogo`
- Trigger `trg_mv_sorteio_verifica_ganhadores_lotinha`
- `invalidConfiguration()` — verificar implementação exata 🟡

### Prioridade

**Must** — sem configuração e trigger correta, ganhadores da Lotinha não são calculados.

---

## US-JE-05: Configurar Milhar Premiada

**Como** administrador do sistema  
**Quero** configurar os multiplicadores da Milhar Premiada por colocação (1ª a 5ª)  
**Para que** o sistema aplique multiplicadores distintos por posição do sorteio

### Critérios de Aceitação

```gherkin
# Happy path — visualizar configuração
Dado que acesso a tela de Milhar Premiada
Quando clico em Buscar
Então vejo configurações com: extracao, multiplicadores de 5 colocações
E os dados vêm de cad_modalidade com os campos multiplicadorColocacao01..05

# Happy path — editar multiplicadores por colocação
Dado que seleciono extração=Tarde para Milhar Premiada
Quando configuro: col_01=5000, col_02=3000, col_03=2000, col_04=1000, col_05=500
E clico em Salvar
Então a linha em cad_modalidade é atualizada com os 5 multiplicadores

# Decisão de design — multiplicadorColocacao01 como padrão
Dado que Modalidade.php mapeia todos os 5 campos multiplicador_colocacao_01..05 🟢
E ModalidadeForm.php expõe apenas multiplicadorColocacao01 (como "Valor Palpite")
Então por decisão de design, apenas o campo 01 é utilizado atualmente 🟡
E os campos 02..05 estão reservados para uso futuro quando regras de colocação múltipla forem necessárias
E a trigger trg_mv_jb_sorteio_previsao usa multiplicadorColocacao01 para Bilhetinho, Quininha e Seninha 🟢

# Lacuna — sem register_log
Dado que a API de Milhar Premiada é chamada
Então register_log não é chamado ao contrário das demais configurações 🔴
E o audit trail desta operação fica incompleto

# Falha — cobertura de colocações incompleta
Dado que extração tem premiacao_maxima=5
E apenas 3 multiplicadores estão configurados
Quando ganhador de 4ª colocação é encontrado
Então prêmio pode ser calculado como 0 (multiplicador ausente = 0) 🔴
```

### Regras de Negócio Relacionadas

- `cad_modalidade` armazena até 5 multiplicadores de colocação 🟢
- `Modalidade.php` (Active Record) mapeia todos os 5 campos `multiplicador_colocacao_01..05` 🟢
- `ModalidadeForm.php` expõe apenas campo 01 ("Valor Palpite") na UI — 02..05 reservados para uso futuro 🟡
- `register_log` não chamado nas operações de Milhar Premiada 🔴
- Milhar Premiada usa o mesmo fluxo de resultado JB (trigger `trg_mv_sorteio_verifica_ganhadores`) 🟡

### Dependências

- `cad_modalidade` (jogo_id='MBP' ou similar), `Modalidade.php`
- Trigger `trg_mv_sorteio_verifica_ganhadores`
- `api/registerLog` (ausente) 🔴

### Prioridade

**Must** — lacuna no Active Record causa perda silenciosa de configuração de colocações 4 e 5.

---

## Ciclo de Vida por Jogo Especial

```
CONFIGURAÇÃO (admin)
    │
    ├── Bilhetinho: cad_modalidade_bilhetinho (5 colocações × modalidade × extração)
    ├── Quininha:   cad_modalidade (jogo_id='QUI', 3 multiplicadores)
    ├── Seninha:    cad_modalidade (jogo_id='SEN', 3 multiplicadores)
    ├── Lotinha:    cad_modalidade (jogo_id='LOT', cotacao_unica)
    └── Milhar Premiada: cad_modalidade (5 multiplicadores, mas 04+05 sem mapeamento PHP) 🔴

APOSTA (vendedor via app)
    │
    ├── Bilhetinho: BilhetinhoRestService → NÃO IMPLEMENTADO 🔴
    ├── Quininha:   mov_jb (confirmado ou tabela própria?) 🟡
    ├── Seninha:    mov_jb (confirmado ou tabela própria?) 🟡
    ├── Lotinha:    mov_jb (confirmado ou tabela própria?) 🟡
    └── Milhar Premiada: BilheteRestService (mesmo fluxo JB) 🟡

RESULTADO (operador)
    │
    ├── Bilhetinho: sem tela de resultado própria 🔴
    ├── Quininha:   tela ResultadoQuininha; 5 dezenas; trigger qui_sen
    ├── Seninha:    tela ResultadoSeninha; 6 dezenas; trigger qui_sen
    ├── Lotinha:    tela ResultadoLotinha; 15 dezenas; trigger lotinha
    └── Milhar Premiada: ResultadoForm.php (mesmo que JB) 🟡

ENCERRAMENTO → Triggers calculam ganhadores → mov_jb ou tabela específica
```

---

## Matriz de Implementação por Jogo

| Jogo | Feature Flag | Config UI | Entidade PHP | REST Aposta | Resultado UI | Trigger |
|---|---|---|---|---|---|---|
| Bilhetinho | `ativo_bilhetinho` | 🔴 GAP | 🔴 sem AR | 🔴 GAP | 🔴 GAP | — |
| Quininha | 🟡 inferido | 🟡 inferido | 🟢 `Modalidade.php` (jogo QUI) | 🟡 inferido | 🟢 implementado | 🟢 `trg_qui_sen` |
| Seninha | 🟡 inferido | 🟡 inferido | 🟢 `Modalidade.php` (jogo SEN) | 🟡 inferido | 🟢 implementado | 🟢 `trg_qui_sen` |
| Lotinha | 🟡 inferido | 🟡 inferido | 🟢 `Modalidade.php` (jogo LOT) | 🟡 inferido | 🟢 implementado | 🟢 `trg_lotinha` |
| Milhar Premiada | — | 🟢 implementado | 🔴 col04+05 ausentes | 🟢 `BilheteRestService` | 🟢 `ResultadoForm` | 🟢 `trg_ganhadores` |

---

## Rastreabilidade de Código

| User Story | Controller / Service | Model | Spec SDD |
|---|---|---|---|
| US-JE-01 | (a criar) `BilhetinhoConfigForm.php` | (a criar) `CadModalidadeBilhetinho.php` | `sdd/openapi/spec-jogos-especiais.yaml` |
| US-JE-02 | 🟡 `app/control/modalidade/ModalidadeForm.php` (jogo QUI) | `app/model/entities/Modalidade.php` | `sdd/openapi/spec-jogos-especiais.yaml` |
| US-JE-03 | 🟡 `app/control/modalidade/ModalidadeForm.php` (jogo SEN) | `app/model/entities/Modalidade.php` | `sdd/openapi/spec-jogos-especiais.yaml` |
| US-JE-04 | 🟡 `app/control/modalidade/ModalidadeForm.php` (jogo LOT) | `app/model/entities/Modalidade.php` | `sdd/openapi/spec-jogos-especiais.yaml` |
| US-JE-05 | 🟡 `app/control/modalidade/ModalidadeForm.php` (jogo MBP) | `app/model/entities/Modalidade.php` | `sdd/openapi/spec-jogos-especiais.yaml` |
