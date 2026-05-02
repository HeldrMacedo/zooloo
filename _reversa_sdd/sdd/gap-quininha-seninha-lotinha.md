# GAP: Quininha, Seninha e Lotinha — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — três tipos de jogo com módulos completos no Java mas **completamente ausentes** no Zooloo PHP (sem telas de configuração, sem telas de resultado)

---

## Visão Geral

Quininha, Seninha e Lotinha são três tipos de jogo de apostas em dezenas que compartilham a mesma tabela de configuração (`cad_modalidade`) com o JB, mas usam campos específicos (`multiplicador_colocacao_01..03`, `limite_palpite`) e têm telas de lançamento de resultado **separadas** do JB com validações e máscaras de entrada distintas.

| Jogo | Código (`int_jogo`) | Dezenas Sorteadas | Prêmios | Mask de entrada |
|---|---|---|---|---|
| Quininha | `QUI` | 5 | Quina (5) / Quadra (4) / Terno (3) | `00,00,00,00,00` |
| Seninha | `SEN` | 6 | Sena (6) / Quina (5) / Quadra (4) | `00,00,00,00,00,00` |
| Lotinha | `LOT` | 15 | Cotação única | `00,00,00,...,00` (15 pares) |

No Zooloo, o campo `ativo_quininha`, `ativo_seninha` e `ativo_lotinha` em `cfg_parametros` indica que esses jogos são suportados, mas **não há telas web** para configuração de modalidades nem para lançamento de resultados.

---

## Estrutura de Dados Compartilhada: `cad_modalidade`

Os três jogos usam a **mesma tabela** `cad_modalidade` (já mapeada por `Modalidade.php` no Zooloo), mas com campos adicionais específicos que precisam ser visíveis nas telas dedicadas:

| Campo | Tipo | Usado por | Significado |
|---|---|---|---|
| `multiplicador_colocacao_01` | `double` | Quininha/Seninha/Lotinha | Prêmio da 1ª colocação (Quina/Sena/Cotação) |
| `multiplicador_colocacao_02` | `double` | Quininha/Seninha | Prêmio da 2ª colocação (Quadra/Quina) |
| `multiplicador_colocacao_03` | `double` | Quininha/Seninha | Prêmio da 3ª colocação (Terno/Quadra) |
| `limite_palpite` | `double` | Lotinha (visível); Quininha/Seninha (implícito) | Limite de aposta por palpite |
| `multiplicador` | `integer` | Todos | Quantidade de números apostados |

> **Nota Java:** `QuininhaModalidade.java`, `SeninhaModalidade.java` e `LotinhaModalidade.java` mapeiam para `@Table(name = "CAD_MODALIDADE")` — são projeções filtradas por `jogo_id`, não tabelas separadas. 🟢

---

## Módulo: Quininha

### Tela de Configuração (`quininha-modalidade`)

**Colunas da lista:**

| Coluna | Dado |
|---|---|
| Números Apostados | `apresentacao (uppercase) - multiplicador_colocacao_01 (BRL)` |
| Quantidade | `multiplicador` (qtd de números apostados) |
| Prêmio Quina | `multiplicador_colocacao_01` (BRL) — 5 acertos |
| Prêmio Quadra | `multiplicador_colocacao_02` (BRL) — 4 acertos |
| Prêmio Terno | `multiplicador_colocacao_03` (BRL) — 3 acertos |
| Ações | Editar (rota `/quininha-modalidade/{id}/edit`) |

### Tela de Lançamento de Resultado (`quininha-resultado`)

**Layout:** split screen — formulário à esquerda, lista dos últimos 10 resultados à direita.

**Campos do formulário:**

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| Sorteio | `number` readonly | — | `sorteio_numero` |
| Data Extração | `date` readonly | — | `data_sorteio` |
| Números Sorteados | `text` com mask `00,00,00,00,00` | **Sim** | 5 dezenas de 2 dígitos |

**Validação de hora:** o resultado só pode ser salvo se `now() >= hora_sorteio`. Caso contrário, alerta "O sorteio só pode ser lançado após às HH:mm:ss". 🟢

**Validação de dezenas:** `save()` verifica que o campo tem 14 chars (`DD,DD,DD,DD,DD`) e exatamente 5 pares. 🟢

**Botões:**

| Botão | Condição | Ação |
|---|---|---|
| Salvar | form válido | Salva números + ativa triggers de premiação |
| Limpar | habilitado após salvar | Abre confirmação → limpa `numeros_sorteados` |
| Fechar | habilitado após salvar (ou já fechado/sem números → disabled) | Abre confirmação → fecha sorteio |
| Editar (lista) | somente usuário `allsystem` admin | Abre popup de edição avançada |

**Auditoria:** Cada ação (Salvar, Limpar, Fechar) registra em `register_log`.

---

## Módulo: Seninha

### Tela de Configuração (`seninha-modalidade`)

**Colunas da lista:**

| Coluna | Dado |
|---|---|
| Números Apostados | `apresentacao (uppercase) - multiplicador_colocacao_01 (BRL)` |
| Quantidade | `multiplicador` |
| Prêmio Sena | `multiplicador_colocacao_01` (BRL) — 6 acertos |
| Prêmio Quina | `multiplicador_colocacao_02` (BRL) — 5 acertos |
| Prêmio Quadra | `multiplicador_colocacao_03` (BRL) — 4 acertos |
| Ações | Editar |

### Tela de Lançamento de Resultado (`seninha-resultado`)

Idêntica à Quininha exceto:

| Aspecto | Quininha | Seninha |
|---|---|---|
| Mask | `00,00,00,00,00` (5 pares) | `00,00,00,00,00,00` (6 pares) |
| Prêmio máximo | Quina (5 acertos) | Sena (6 acertos) |
| Trigger de resultado | `trg_mv_sorteio_verifica_ganhadores_qui_sen` (compartilhada) | idem |
| Auditoria `acao` | `'Quininha Resultado'` | `'Seninha Resultado'` (inferido) |

---

## Módulo: Lotinha

### Tela de Configuração (`lotinha-modalidade`)

**Colunas da lista — diferentes de Quininha/Seninha:**

| Coluna | Dado |
|---|---|
| Números Apostados | `apresentacao (uppercase) - multiplicador_colocacao_01 (BRL)` |
| Quantidade | `multiplicador` |
| Cotação | `multiplicador_colocacao_01` (BRL) — **único prêmio** |
| Limite Palpite | `limite_palpite` (BRL) |
| Ações | Editar |

> **Diferença-chave:** Lotinha tem cotação única (não há Quina/Quadra/Terno) e exibe `limite_palpite` na lista. 🟢

### Tela de Lançamento de Resultado (`lotinha-resultado`)

Idêntica à Quininha exceto:

| Aspecto | Quininha | Lotinha |
|---|---|---|
| Mask | `00,00,00,00,00` (5) | `00,00,00,00,00,00,00,00,00,00,00,00,00,00,00` (15 pares) |
| Dezenas | 5 | 15 |
| Trigger de resultado | `trg_mv_sorteio_verifica_ganhadores_qui_sen` | `trg_mv_sorteio_verifica_ganhadores_lotinha` |
| Invalidação de config | Não tem `invalidConfiguration()` | Tem `invalidConfiguration()` adicional |
| Botão Editar | somente `allsystem` admin | idem |

---

## Regras de Negócio

- **RN-QSL-01** As três modalidades reusam `cad_modalidade` — são diferenciadas por `int_jogo.codigo` (`QUI`/`SEN`/`LOT`) 🟢
- **RN-QSL-02** Resultado só pode ser lançado após a hora do sorteio (`hora_sorteio`) — validado no frontend 🟢
- **RN-QSL-03** Quininha exige exatamente 5 dezenas de 2 dígitos no campo `numeros_sorteados` 🟢
- **RN-QSL-04** Seninha exige exatamente 6 dezenas 🟡
- **RN-QSL-05** Lotinha exige exatamente 15 dezenas 🟡
- **RN-QSL-06** Fechar sorteio é irreversível via tela — após fechado, botões Limpar/Fechar ficam desabilitados 🟢
- **RN-QSL-07** Limpar números exige confirmação — limpa `numeros_sorteados` sem fechar o sorteio 🟢
- **RN-QSL-08** Botão Editar no histórico de resultados só aparece para o usuário `allsystem` Administrator 🟢
- **RN-QSL-09** `ativo_quininha`, `ativo_seninha`, `ativo_lotinha` em `cfg_parametros` habilitam os jogos para o app móvel 🟢
- **RN-QSL-10** Triggers do banco calculam automaticamente os prêmios ao fechar o sorteio: `trg_mv_sorteio_verifica_ganhadores_qui_sen` (Quininha/Seninha) e `trg_mv_sorteio_verifica_ganhadores_lotinha` (Lotinha) 🟢
- **RN-QSL-11** Colocações de Quininha são exibidas como QUI/QUA/TER no app; Seninha como SEN/QUI/QUA — mapeamento já documentado em `ResultadoRestService` 🟢

---

## Fluxo de Resultado (igual para os três jogos)

```
1. Gestor acessa tela de resultado do jogo (Quininha/Seninha/Lotinha)
2. Frontend carrega sorteio aberto atual (GET api/quininha|seninha|lotinha)
3. Exibe: Nº Sorteio (readonly), Data (readonly), campo Números Sorteados
4. Gestor digita dezenas com máscara
5. Clica Salvar:
   a. Frontend valida hora atual >= hora_sorteio
   b. Frontend formata dezenas em CSV (DD,DD,DD,...)
   c. Frontend valida quantidade correta de dezenas
   d. PUT api/quininha|seninha|lotinha → UPDATE mov_sorteio.numeros_sorteados
   e. Registro em register_log
   f. Botões Limpar e Fechar tornam-se habilitados
6. (Opcional) Limpar: com confirmação → limpa numeros_sorteados
7. Fechar: com confirmação → fecha sorteio → trigger calcula prêmios
```

---

## Dependências

| Componente | Relação |
|---|---|
| `cad_modalidade` | Configuração de modalidades (mesmo `Modalidade.php` do Zooloo) |
| `int_jogo` | Diferencia QUI/SEN/LOT |
| `mov_sorteio` | Tabela de sorteios compartilhada com JB |
| `trg_mv_sorteio_verifica_ganhadores_qui_sen` | Trigger DB para Quininha/Seninha |
| `trg_mv_sorteio_verifica_ganhadores_lotinha` | Trigger DB para Lotinha |
| `cfg_parametros` | Feature flags `ativo_quininha`, `ativo_seninha`, `ativo_lotinha` |
| `register_log` | Auditoria de lançamento de resultado |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela configuração Quininha Modalidade | Angular presente | Ausente | 🔴 |
| Tela resultado Quininha | Angular presente | Ausente | 🔴 |
| Tela configuração Seninha Modalidade | Angular presente | Ausente | 🔴 |
| Tela resultado Seninha | Angular presente | Ausente | 🔴 |
| Tela configuração Lotinha Modalidade | Angular presente | Ausente | 🔴 |
| Tela resultado Lotinha | Angular presente | Ausente | 🔴 |
| Triggers DB para Quininha/Seninha | `trg_mv_sorteio_verifica_ganhadores_qui_sen` | Existe ✅ | — |
| Trigger DB para Lotinha | `trg_mv_sorteio_verifica_ganhadores_lotinha` | Existe ✅ | — |
| `cfg_parametros.ativo_quininha/seninha/lotinha` | Presente | ✅ campos existem | — |
| `cad_modalidade` com campos `multiplicador_colocacao_*` | Presente | `Modalidade.php` parcial | 🟡 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Quininha — lançar resultado
Dado que existe sorteio aberto quininha para hoje às 14:00
Quando gestor acessa tela de resultado e digita "01 02 03 04 05" às 14:05
Então sistema formata como "01,02,03,04,05" e salva no mov_sorteio.numeros_sorteados

# Seninha — validação de hora
Dado que sorteio seninha é às 16:00
Quando gestor tenta salvar às 15:59
Então alerta "O sorteio só pode ser lançado após às 16:00:00"

# Lotinha — 15 dezenas
Quando gestor digita menos de 15 dezenas para Lotinha
Então botão Salvar permanece inválido (form validation)

# Fechar quininha
Dado que numeros_sorteados já foi salvo para a quininha
Quando gestor clica Fechar → confirma
Então mov_sorteio.situacao='F'
E trigger trg_mv_sorteio_verifica_ganhadores_qui_sen executa automaticamente
E prêmios calculados em mov_jb_sorteio

# Configuração Quininha — visualizar prêmios
Quando gestor acessa QuininhaModalidadeList
Então tabela exibe modalidades com Prêmio Quina / Quadra / Terno em BRL
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Telas de resultado Quininha + Seninha | Must | Operação diária sem ela prêmios não são calculados |
| Telas de resultado Lotinha | Must | Idem |
| Telas de config modalidade (3 jogos) | Should | Permite ajuste de prêmios sem banco direto |
| Validação de hora no resultado | Must | Evitar lançamentos antecipados |
| Auditoria register_log | Must | Rastreabilidade de lançamentos |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Jogo | Sistema |
|---|---|---|
| `allsystem/.../domain/QuininhaModalidade.java` | Quininha | Java — `@Table("CAD_MODALIDADE")` |
| `allsystem/.../domain/SeninhaModalidade.java` | Seninha | Java |
| `allsystem/.../domain/LotinhaModalidade.java` | Lotinha | Java |
| `allsystem/.../webapp/app/entities/quininha-modalidade/` | Quininha | Java |
| `allsystem/.../webapp/app/entities/quininha-resultado/` | Quininha | Java |
| `allsystem/.../webapp/app/entities/seninha-modalidade/` | Seninha | Java |
| `allsystem/.../webapp/app/entities/seninha-resultado/` | Seninha | Java |
| `allsystem/.../webapp/app/entities/lotinha-modalidade/` | Lotinha | Java |
| `allsystem/.../webapp/app/entities/lotinha-resultado/` | Lotinha | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Jogo | Descrição |
|---|---|---|
| `app/control/quininha/QuininhaModalidadeList.php` | Quininha | Config modalidades com 3 prêmios |
| `app/control/quininha/QuininhaResultadoForm.php` | Quininha | Lançamento com mask 5 dezenas |
| `app/control/seninha/SeninhaModalidadeList.php` | Seninha | Config modalidades com 3 prêmios |
| `app/control/seninha/SeninhaResultadoForm.php` | Seninha | Lançamento com mask 6 dezenas |
| `app/control/lotinha/LotinhaModalidadeList.php` | Lotinha | Config modalidades com cotação única |
| `app/control/lotinha/LotinhaResultadoForm.php` | Lotinha | Lançamento com mask 15 dezenas |
