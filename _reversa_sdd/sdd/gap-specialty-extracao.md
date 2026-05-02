# GAP: Extração Quininha, Seninha e Lotinha — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — três telas de configuração de extração para jogos especiais presentes no Java mas **completamente ausentes** no Zooloo PHP. As extrações usam a mesma tabela `cad_extracao` do JB, filtradas por `int_jogo`.

---

## Visão Geral

Quininha, Seninha e Lotinha têm extrações independentes (dias da semana + hora limite) configuradas separadamente das extrações do JB. No Java, há três módulos Angular distintos (`quininha-extracao`, `seninha-extracao`, `lotinha-extracao`) que operam sobre a **mesma tabela `cad_extracao`** — cada módulo lista e edita apenas as extrações do seu respectivo tipo de jogo (`int_jogo.codigo = QUI/SEN/LOT`).

| Módulo | Título da tela | API (list) | API (update) |
|---|---|---|---|
| Quininha | "Quininha Extração" | `GET api/quininha-extracao` 🟡 | `PUT api/extracaos` 🟢 |
| Seninha | "Seninha Extração" | `GET api/seninha-extracao` 🟢 | `PUT api/extracaos` 🟢 |
| Lotinha | "Lotinha Extração" | `GET api/lotinha-extracao` 🟡 | `PUT api/extracaos` 🟢 |

> **Nota de implementação:** Os três módulos partilham o endpoint `PUT api/extracaos` para salvar edições — o mesmo usado pelo módulo de Extração JB. A filtragem por tipo de jogo é feita apenas na listagem. 🟢

---

## Estrutura de Dados

Os três jogos reutilizam `cad_extracao` — sem campos exclusivos além dos já mapeados em `sdd/extracao.md`.

### Campos usados por esses módulos

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `extracao_id` | PK | — | Identificador |
| `descricao` | `varchar` | **Sim** | Nome da extração (forçado uppercase) |
| `descricao_mobile` | `varchar` | **Sim** | Nome exibido no app móvel (uppercase) |
| `hora_limite` | `time` | **Sim** | Hora de corte para apostas e lançamento de resultado |
| `segunda`..`domingo` | `boolean` | Ao menos um se `ativo=true` | Dias da semana em que o sorteio ocorre |
| `ativo` | `boolean` | — | Se inativo, pode salvar sem dias selecionados |
| `int_jogo_id` | FK `int_jogo` | — | Diferencia QUI / SEN / LOT |

> Os campos `premiacao_maxima`, `calc_sorteio_id`, `gerar_restante` (usados por ResultadoJb) **não aparecem** no formulário de extração especializado — esses atributos ficam no contexto JB apenas. 🟡

---

## Interface — Lista (idêntica para os três jogos)

### Colunas

| Coluna | Dado | Notas |
|---|---|---|
| Descrição | `descricao` | |
| Descrição Mobile | `descricaoMobile` | |
| Hora Limite | `horaLimite` | |
| Segunda | `segunda ? "X" : ""` | |
| Terça | `terca ? "X" : ""` | |
| Quarta | `quarta ? "X" : ""` | |
| Quinta | `quinta ? "X" : ""` | |
| Sexta | `sexta ? "X" : ""` | |
| Sábado | `sabado ? "X" : ""` | |
| Domingo | `domingo ? "X" : ""` | |
| Ações | Editar (rota `/{jogo}-extracao/{id}/edit`) | **Sem botão Delete** |

> **Sem botão Criar na lista** — as extrações dos jogos especiais são pré-seeded. O formulário suporta criação via código (`extracaoService.create()`), mas não há botão "+ Nova" visível na lista. 🟡

---

## Interface — Formulário de Edição

### Campos do form

| Campo | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Descrição | `text` | **Sim** | Força uppercase no `keyup` via `maiusculo()` |
| Descrição Mobile | `text` | **Sim** | Força uppercase no `keyup` |
| Hora Limite | `time` | **Sim** | |
| Segunda–Domingo | `checkbox` × 7 | Ao menos um se ativo | Validado por `invalidConfiguration()` |

### Botões

| Botão | Condição de habilitação | Ação |
|---|---|---|
| Salvar | `form.valid && !isSaving && !invalidConfiguration()` | PUT `api/extracaos` → register_log |
| Cancelar | Sempre | `window.history.back()` |

---

## Regras de Negócio

- **RN-SE-01** Uma extração ativa deve ter ao menos um dia da semana selecionado — `invalidConfiguration()` bloqueia salvar se `ativo=true` e todos os dias são `false` 🟢
- **RN-SE-02** Descrição e Descrição Mobile são automaticamente convertidas para maiúsculas durante a digitação 🟢
- **RN-SE-03** Apenas edição via tela — criação de novas extrações não tem botão na UI (registros pré-seeded) 🟡
- **RN-SE-04** Os três módulos usam o mesmo endpoint de atualização (`PUT api/extracaos`) — a diferenciação por tipo de jogo é feita apenas na listagem 🟢
- **RN-SE-05** Auditoria: Quininha e Lotinha registram em `register_log` **antes** do save (no `save()` antes de chamar a API); Seninha registra **após** (`onSaveSuccess()`) — inconsistência entre módulos 🔴
- **RN-SE-06** A trigger `trg_mv_cad_extracao_cria_sorteios` no banco cria sorteios automaticamente ao salvar/editar uma extração ativa 🟢 (mesma trigger do JB)

> **Bug de título na Lotinha:** O HTML de `lotinha-extracao-update.component.html` exibe o heading "Editar **Quininha** Extração" (copy-paste da Quininha sem atualizar o texto). No Zooloo, corrigir para "Editar Lotinha Extração". 🔴

---

## Diferenças entre os três módulos

| Aspecto | Quininha | Seninha | Lotinha |
|---|---|---|---|
| Serviço de lista | `ExtracaoService.queryQE()` | `SeninhaExtracaoService.query()` | `ExtracaoService` (inferido) 🟡 |
| Serviço de update | `ExtracaoService.update()` | `SeninhaExtracaoService.update()` → `PUT api/extracaos` | `ExtracaoService.update()` |
| Auditoria no save | Antes (no `save()`) | Depois (no `onSaveSuccess()`) | Antes (no `save()`) |
| Título do form | "Editar Quininha Extração" | "Editar Seninha Extração" | "Editar **Quininha** Extração" ← **BUG** |
| register_log ação | `'Quininha Extração'` | `'Seninha Extração'` | `'Lotinha Extração'` |

---

## Fluxo Principal

```
1. Gestor acessa tela de extração do jogo (Quininha/Seninha/Lotinha)
2. GET api/{jogo}-extracao → lista extrações filtradas por int_jogo
3. Gestor clica Editar em uma linha
4. Form carregado via router: GET api/extracaos/{id}
5. Gestor edita Descrição, Hora Limite e/ou dias da semana
6. Se ativo=true e nenhum dia selecionado → botão Salvar desabilitado
7. Clica Salvar:
   a. PUT api/extracaos → UPDATE cad_extracao
   b. register_log criado (antes ou depois conforme módulo)
   c. Trigger cria/atualiza sorteios automaticamente
   d. Retorna à lista
```

---

## Dependências

| Componente | Relação |
|---|---|
| `cad_extracao` | Tabela compartilhada — filtrada por `int_jogo` |
| `int_jogo` | `QUI` / `SEN` / `LOT` — diferencia as extrações |
| `mov_sorteio` | Criado automaticamente pela trigger ao salvar a extração |
| `trg_mv_cad_extracao_cria_sorteios` | Trigger de criação automática de sorteios |
| `register_log` | Auditoria de edição |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Lista Quininha Extração | Angular presente | Ausente | 🔴 |
| Form edição Quininha Extração | Angular presente | Ausente | 🔴 |
| Lista Seninha Extração | Angular presente | Ausente | 🔴 |
| Form edição Seninha Extração | Angular presente | Ausente | 🔴 |
| Lista Lotinha Extração | Angular presente | Ausente | 🔴 |
| Form edição Lotinha Extração | Angular presente | Ausente | 🔴 |
| `invalidConfiguration()` (dias vs ativo) | Presente | Ausente nessas telas | 🔴 |
| Auditoria register_log | Presente (inconsistente) | Ausente | 🔴 |
| Bug título Lotinha form | "Editar Quininha Extração" | — | 🔴 (corrigir no Zooloo) |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — editar quininha extração
Dado que extração Quininha "QUI-TARDE" está cadastrada
Quando gestor acessa QuininhaExtracaoList
Então lista exibe: Descrição, Hora Limite, dias da semana (X para ativos)

# Editar hora limite
Quando gestor clica Editar e altera horaLimite de 14:00 para 15:00 e salva
Então cad_extracao atualizado com nova hora
E register_log registra {acao: 'Quininha Extração', historico: 'Editar Extração'}

# Falha — ativo sem dia selecionado
Dado que extração está ativa e nenhum dia está marcado
Quando gestor tenta salvar
Então botão Salvar permanece desabilitado

# Happy path — inativo sem dia selecionado
Dado que extração está inativa (ativo=false)
Quando nenhum dia está marcado
Então botão Salvar fica habilitado (invalidConfiguration retorna false)

# Uppercase automático
Quando gestor digita "quininha tarde" no campo Descrição
Então campo exibe "QUININHA TARDE" automaticamente

# Título correto para Lotinha
Quando gestor acessa LotinhaExtracaoForm
Então título exibe "Editar Lotinha Extração" (não "Quininha Extração")
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Lists Quininha/Seninha/Lotinha Extração | Must | Sem visualização de extrações não é possível configurar os jogos |
| Forms edição Quininha/Seninha/Lotinha | Must | Ajuste de hora limite e dias — operação diária |
| `invalidConfiguration()` — validar dias vs ativo | Must | Impede configuração inválida que quebraria a geração de sorteios |
| Auditoria register_log | Should | Rastreabilidade de configuração |
| Corrigir título Lotinha form | Must | Bug de UX do sistema legado a ser corrigido |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Jogo | Sistema |
|---|---|---|
| `allsystem/.../entities/quininha-extracao/quininha-extracao.component.html` | Quininha | Lista |
| `allsystem/.../entities/quininha-extracao/quininha-extracao-update.component.html` | Quininha | Form |
| `allsystem/.../entities/quininha-extracao/quininha-extracao-update.component.ts` | Quininha | `ExtracaoService.queryQE()` |
| `allsystem/.../entities/seninha-extracao/seninha-extracao.component.html` | Seninha | Lista |
| `allsystem/.../entities/seninha-extracao/seninha-extracao.service.ts` | Seninha | `GET api/seninha-extracao` |
| `allsystem/.../entities/lotinha-extracao/lotinha-extracao.component.html` | Lotinha | Lista |
| `allsystem/.../entities/lotinha-extracao/lotinha-extracao-update.component.html` | Lotinha | Form (bug de título) |
| `allsystem/.../entities/lotinha-extracao/lotinha-extracao-update.component.ts` | Lotinha | `ExtracaoService.update()` |

**Arquivos a criar no Zooloo:**

| Arquivo | Jogo | Descrição |
|---|---|---|
| `app/control/quininha/QuininhaExtracaoList.php` | Quininha | Lista com filtro `int_jogo.codigo = 'QUI'` |
| `app/control/seninha/SeninhaExtracaoList.php` | Seninha | Lista com filtro `int_jogo.codigo = 'SEN'` |
| `app/control/lotinha/LotinhaExtracaoList.php` | Lotinha | Lista com filtro `int_jogo.codigo = 'LOT'` |
| (form compartilhado) | Todos | Reutilizar `ExtracaoForm.php` com parâmetro de tipo ou criar forms dedicados |
