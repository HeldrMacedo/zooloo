# User Stories — Resultado e Sorteio

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Módulos cobertos: ResultadoJB, Quininha, Seninha, Lotinha — lançamento de resultado
> e ciclo de vida do sorteio

---

## US-RES-01: Registrar Resultado do Jogo do Bicho

**Como** operador de resultado  
**Quero** lançar os números sorteados do JB para uma extração  
**Para que** o sistema calcule automaticamente os bilhetes ganhadores

### Critérios de Aceitação

```gherkin
# Happy path — localizar sorteio
Dado que seleciono data=2026-05-01 e extração=Tarde no combo (populado via vwsorteio)
Quando clico em Buscar
Então os dados do sorteio são carregados: Nº Sorteio, Data, Hora, Situação=Aberto
E os campos de prêmio 1 a premiacao_maxima ficam habilitados
E os campos acima de premiacao_maxima ficam desabilitados

# Happy path — digitar prêmios com feedback automático
Dado que estou com o campo premio_1 em foco
Quando digito "1234"
Então grupo_1 exibe automaticamente "09" (COBRA)
E descricao_grupo_1 exibe "COBRA"
E o foco avança automaticamente para o campo premio_2 (tab automático)

# Happy path — salvar resultado
Dado que preenchi os prêmios 1 a 5 para uma extração com premiacao_maxima=5
E a hora atual é >= hora_sorteio
Quando clico em Salvar
Então numeros_sorteados="NNNN,NNNN,NNNN,NNNN,NNNN" é gravado em mov_sorteio
E mov_sorteio.situacao permanece 'A' (ainda não encerrado)
E os botões Limpar e Encerrar ficam habilitados

# Happy path — encerrar sorteio
Dado que numeros_sorteados já foi salvo para o sorteio
Quando clico em Encerrar e confirmo o alerta "Esta ação não poderá ser desfeita"
Então mov_sorteio.situacao='F'
E PostgreSQL dispara trg_mv_sorteio_verifica_ganhadores (JB)
E PostgreSQL dispara trg_mv_sorteio_verifica_ganhadores_lotinha (Lotinha)
E PostgreSQL dispara trg_mv_sorteio_verifica_ganhadores_qui_sen (Quininha/Seninha)
E todos os campos e botões ficam desabilitados

# Caso especial — dezena "00" → grupo VACA
Dado que digito "1200" no campo premio_1
Quando chkGrupoDescricao executa
Então dezmilhar = int("00") → normaliza para 100 → grupo="25" → "VACA"

# Falha — salvar antes da hora do sorteio
Dado que hora_sorteio da extração é 16:00:00 e agora são 15:59:00
Quando tento salvar
Então alerta "O sorteio só pode ser lançado após às 16:00:00"
E nenhum dado é gravado

# Falha — encerrar sem números
Dado que numeros_sorteados está vazio
Quando clico em Encerrar
Então erro "Não é possível encerrar um sorteio sem números sorteados"

# Falha — tentar editar sorteio encerrado
Dado que mov_sorteio.situacao='F'
Quando localizo o sorteio
Então todos os campos aparecem desabilitados
E os botões Salvar, Limpar e Encerrar estão ocultos

# Happy path — limpar resultado
Dado que numeros_sorteados está preenchido e situacao='A'
Quando clico em Limpar e confirmo
Então numeros_sorteados='', campos de prêmio ficam vazios
```

### Regras de Negócio Relacionadas

- RN-RE-01: Sorteios com `situacao='F'` são imutáveis via UI 🟢
- RN-RE-03: `premiacao_maxima` da extração controla quantos campos de prêmio são habilitados 🟢
- RN-RE-04: `numeros_sorteados` é a fonte de verdade ao salvar 🟢
- RN-RE-05: Triggers calculam ganhadores atomicamente ao encerrar 🟢
- RN-RE-09: Validação de `hora_sorteio` ausente no PHP atual — TODO crítico 🔴
  - Nota: `hora_limite` é o prazo para APOSTAS; `hora_sorteio` é quando o resultado pode ser lançado 🟢
- RN-RJB-10: Sorteio fechado não pode ser reaberto pela UI — usar permissão do sistema Adianti (a definir) 🟡

### Algoritmo Grupo JB (deve ser implementado em JS e PHP)

```
dezmilhar = int(numero[2:4])
if dezmilhar == 0: dezmilhar = 100
grupo = ceil(dezmilhar / 4)  # grupos 01–25
```

### Dependências

- `mov_sorteio`, `cad_extracao` (`premiacao_maxima`, `hora_sorteio`, `calc_sorteio_id`)
- Triggers: `trg_mv_sorteio_verifica_ganhadores*` (3 triggers)
- `vw_sorteio` (view para combo de extração — GAP: PHP usa `cad_extracao` diretamente)

### Prioridade

**Must** — sem resultado lançado, ganhadores não são calculados e prêmios não são pagos.

---

## US-RES-02: Lançar Resultado da Quininha

**Como** operador de resultado  
**Quero** lançar as 5 dezenas sorteadas da Quininha  
**Para que** o sistema calcule automaticamente os prêmios (Quina / Quadra / Terno)

### Critérios de Aceitação

```gherkin
# Happy path — lançar resultado
Dado que acesso a tela de Resultado Quininha e o sorteio está aberto
Quando digito "01,15,22,34,47" no campo com máscara "00,00,00,00,00"
E a hora atual é >= hora_sorteio
Quando clico em Salvar
Então numeros_sorteados="01,15,22,34,47" é salvo em mov_sorteio
E auditoria registrada: {acao:'Quininha Resultado', historico:'Salvar Resultado'}

# Falha — menos de 5 dezenas
Dado que preencho apenas "01,15,22" (3 dezenas)
Quando tento salvar
Então validação frontend impede o save (máscara não está completa)

# Falha — hora não atingida
Dado que hora_sorteio é 14:00:00 e agora são 13:59:00
Quando tento salvar
Então alerta "O sorteio só pode ser lançado após às 14:00:00"

# Happy path — fechar sorteio (dispara trigger)
Dado que resultado já foi salvo para a Quininha
Quando clico em Fechar e confirmo
Então mov_sorteio.situacao='F'
E PostgreSQL dispara trg_mv_sorteio_verifica_ganhadores_qui_sen
E prêmios calculados: Quina (5 acertos) / Quadra (4) / Terno (3)
E auditoria registrada: {historico:'Encerrar Resultado'}

# Happy path — limpar resultado
Quando clico em Limpar e confirmo
Então numeros_sorteados='' e os botões Fechar e Limpar ficam desabilitados
E auditoria registrada: {historico:'Limpar Resultado'}

# Happy path — histórico de resultados
Dado que estou na tela de resultado
Então os últimos 10 resultados são exibidos à direita (split screen)
E usuário allsystem Administrator vê botão Editar no histórico
```

### Regras de Negócio Relacionadas

- RN-QSL-02: Resultado lançado apenas após hora_sorteio 🟢
- RN-QSL-03: Exatamente 5 dezenas de 2 dígitos (`DD,DD,DD,DD,DD`) 🟢
- RN-QSL-06: Fechar sorteio é irreversível via tela 🟢
- RN-QSL-08: Botão Editar no histórico restrito ao usuário `allsystem Administrator` 🟢
- RN-QSL-10: Trigger `trg_mv_sorteio_verifica_ganhadores_qui_sen` compartilhada com Seninha 🟢

### Dependências

- `mov_sorteio` (compartilhada com JB), `cad_modalidade` (configuração Quininha)
- Trigger `trg_mv_sorteio_verifica_ganhadores_qui_sen`
- `api/registerLog` (auditoria)

### Prioridade

**Must** — sem lançamento de resultado, ganhadores da Quininha não são calculados.

---

## US-RES-03: Lançar Resultado da Seninha

**Como** operador de resultado  
**Quero** lançar as 6 dezenas sorteadas da Seninha  
**Para que** o sistema calcule automaticamente os prêmios (Sena / Quina / Quadra)

### Critérios de Aceitação

```gherkin
# Happy path — lançar resultado
Dado que digito "01,15,22,34,47,58" com máscara "00,00,00,00,00,00" (6 pares)
E hora atual >= hora_sorteio
Quando salvo
Então numeros_sorteados="01,15,22,34,47,58" salvo em mov_sorteio

# Falha — menos de 6 dezenas
Quando preencho apenas 5 dezenas
Então máscara não está completa e botão Salvar permanece inválido

# Happy path — fechar sorteio
Quando fecho o sorteio da Seninha
Então mesma trigger trg_mv_sorteio_verifica_ganhadores_qui_sen executa
E prêmios calculados: Sena (6 acertos) / Quina (5) / Quadra (4)
```

### Diferenças em relação à Quininha

| Aspecto | Quininha | Seninha |
|---|---|---|
| Dezenas | 5 | 6 |
| Máscara | `00,00,00,00,00` (14 chars) | `00,00,00,00,00,00` (17 chars) |
| Prêmio máximo | Quina (5 acertos) | Sena (6 acertos) |
| Trigger | `trg_mv_sorteio_verifica_ganhadores_qui_sen` | idem (compartilhada) |

### Prioridade

**Must** — operação diária sem ela prêmios da Seninha não são calculados.

---

## US-RES-04: Lançar Resultado da Lotinha

**Como** operador de resultado  
**Quero** lançar as 15 dezenas sorteadas da Lotinha  
**Para que** o sistema calcule automaticamente os prêmios (cotação única)

### Critérios de Aceitação

```gherkin
# Happy path — lançar resultado
Dado que digito 15 dezenas com a máscara de 15 pares (44 chars)
E hora atual >= hora_sorteio
E a configuração da modalidade é válida (invalidConfiguration() retorna false)
Quando salvo
Então numeros_sorteados salvo com 15 dezenas CSV

# Falha — configuração inválida
Dado que a configuração de modalidade Lotinha está incompleta
Quando clico Salvar
Então validação invalidConfiguration() bloqueia o save com mensagem de erro

# Falha — menos de 15 dezenas
Quando preencho menos de 15 dezenas
Então máscara não completa impede o save

# Happy path — fechar sorteio (trigger diferente)
Quando fecho o sorteio da Lotinha
Então trigger trg_mv_sorteio_verifica_ganhadores_lotinha executa
E prêmios calculados com cotação única (não há Quina/Quadra/Terno)
```

### Diferenças em relação à Quininha/Seninha

| Aspecto | Quininha/Seninha | Lotinha |
|---|---|---|
| Dezenas | 5 ou 6 | **15** |
| Prêmios por colocação | 3 (Quina/Quadra/Terno ou Sena/Quina/Quadra) | **1** (cotação única) |
| Validação extra | Não | `invalidConfiguration()` antes de salvar |
| Trigger | `trg_mv_sorteio_verifica_ganhadores_qui_sen` | **`trg_mv_sorteio_verifica_ganhadores_lotinha`** |

### Prioridade

**Must** — sem lançamento, ganhadores da Lotinha não são calculados.

---

## US-RES-05: Consultar Sorteios Abertos (Combo de Extração)

**Como** operador de resultado  
**Quero** ver somente as extrações que têm sorteios abertos hoje no combo de extração  
**Para que** não precise filtrar manualmente extrações irrelevantes

### Critérios de Aceitação

```gherkin
# Happy path — combo populado via vwsorteio
Dado que hoje é 2026-05-01 e há sorteios abertos para Tarde e Noite
Quando acesso a tela de resultado JB
Então o combo de extração exibe apenas "Tarde" e "Noite" (ordenado alfabeticamente)
E extrações sem sorteio aberto hoje não aparecem

# GAP atual no Zooloo
Dado que ResultadoList.php usa cad_extracao diretamente (não a vw_sorteio)
Quando acesso a tela
Então todas as extrações cadastradas aparecem (incluindo as sem sorteio hoje)
E a implementação deveria usar GET api/vwsorteio para filtrar apenas as relevantes 🔴
```

### Regras de Negócio Relacionadas

- RN-RJB-11: Combo deve usar `vwsorteio` (view de sorteios abertos), não `cad_extracao` 🟢
- GAP: PHP atual usa `cad_extracao` diretamente — corrigir usando `vw_sorteio` do banco legado 🟡 (view existe no banco `jb`, falta criar no banco `applications`)

### Prioridade

**Should** — melhora UX; sem o fix, operador vê extrações desnecessárias mas pode operar.

---

## Ciclo de Vida do Sorteio

```
CRIADO (trigger ao salvar Extração)
    │
    ▼
ABERTO (situacao='A', numeros_sorteados='')
    │
    ├──[Salvar resultado]──► ABERTO COM RESULTADO (numeros_sorteados preenchido)
    │                               │
    │                               ├──[Limpar]──► ABERTO (numeros_sorteados='')
    │                               │
    │                               └──[Encerrar]──► FECHADO (situacao='F')
    │                                                      │
    │                                                      ▼
    │                                          TRIGGERS DISPARADAS (DB calcula ganhadores)
    │
    └──[PERM_ALTERAR_SORTEIO_FECHADO]──► FECHADO pode ser reaberto (GAP no Zooloo) 🔴
```

---

## Rastreabilidade de Código

| User Story | Controller / Service | Model | Spec SDD |
|---|---|---|---|
| US-RES-01 | `app/control/resultado/ResultadoForm.php` | `app/model/entities/MovSorteio.php` | `sdd/resultado.md` + `sdd/gap-resultadojb.md` |
| US-RES-02 | (a criar) `QuininhaResultadoForm.php` | `MovSorteio.php` | `sdd/gap-quininha-seninha-lotinha.md` |
| US-RES-03 | (a criar) `SeninhaResultadoForm.php` | `MovSorteio.php` | `sdd/gap-quininha-seninha-lotinha.md` |
| US-RES-04 | (a criar) `LotinhaResultadoForm.php` | `MovSorteio.php` | `sdd/gap-quininha-seninha-lotinha.md` |
| US-RES-05 | `app/control/resultado/ResultadoList.php` | `MovSorteio.php` (via `vw_sorteio`) | `sdd/gap-resultadojb.md` |
