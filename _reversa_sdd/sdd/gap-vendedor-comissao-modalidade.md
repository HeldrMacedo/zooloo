# GAP: Vendedor Comissão Modalidade — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — módulo presente no Java (allsystem) mas **não implementado** no Zooloo PHP (sem entidade, sem controller, sem tela)

---

## Visão Geral

O módulo **Vendedor Comissão Modalidade** (`cfg_vendedor_mod_comissao`) permite configurar percentuais de comissão com granularidade de **vendedor individual** por modalidade — um nível mais específico que o `cfg_area_comissao_modalidade` (que configura comissão por área).

O modelo é flexível: qualquer combinação de `área`, `vendedor` e `modalidade` pode ser nula, criando regras de escopo variável. A única restrição é que ao menos um dos três campos deve estar preenchido.

No Zooloo, apenas o módulo `AreaComissaoModalidade` está implementado. A tabela `cfg_vendedor_mod_comissao` existe no banco mas **não tem entidade PHP, controller nem tela**.

---

## Estrutura de Dados

### `cfg_vendedor_mod_comissao` — Java (`VendedorComissaoModalidade.java`)

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `vendedor_mod_comissao_id` | `bigint` PK | — | Sequência PostgreSQL `cfg_vendedor_mod_comissao_vendedor_mod_comissao_id_seq1` |
| `comissao` | `double` | **Sim** (`@NotNull`) | Percentual de comissão |
| `modalidade_id` | FK `cad_modalidade` | Não (nullable) | Nulo = aplica a TODAS as modalidades |
| `area_id` | FK `cad_area` | Não (nullable) | Nulo = aplica a TODAS as áreas |
| `vendedor_id` | FK `cad_vendedor` | Não (nullable) | Nulo = aplica a TODOS os vendedores |

### Modelo de escopo das regras

| area_id | vendedor_id | modalidade_id | Significado |
|---|---|---|---|
| X | Y | Z | Comissão específica do vendedor Y na área X para modalidade Z |
| X | null | Z | Comissão para todos os vendedores da área X na modalidade Z |
| X | Y | null | Comissão do vendedor Y na área X em todas as modalidades |
| null | null | Z | Comissão para todos os vendedores em todas as áreas na modalidade Z |
| X | null | null | Comissão para todos os vendedores da área X em todas as modalidades |

> **Restrição:** Pelo menos um campo (area, vendedor ou modalidade) deve ser não nulo — `invalidSelection()` bloqueia salvar quando todos são null. 🟢

---

## Relação com `cfg_area_comissao_modalidade`

| Módulo | Tabela | Granularidade | Status Zooloo |
|---|---|---|---|
| Área Comissão Modalidade | `cfg_area_comissao_modalidade` | Área + Modalidade | ✅ Implementado |
| Vendedor Comissão Modalidade | `cfg_vendedor_mod_comissao` | Vendedor + Área + Modalidade | 🔴 Ausente |

> **Hierarquia de precedência** (inferida): quando existe uma regra em `cfg_vendedor_mod_comissao` para um vendedor específico, ela deve sobrescrever a regra genérica de `cfg_area_comissao_modalidade`. A lógica de priorização não está documentada no código — provavelmente implementada na trigger ou job de cálculo de comissão. 🔴

---

## Interface no Sistema Java (allsystem — referência)

### Lista (`vendedor-comissao-modalidade.component.html`)

**Título:** "Comissao Área / Modalidade / Vendedor"

**Permissão de criação:** `PERM_VENDEDOR_COMISSAO_MODALIDADE`

**Colunas:**

| Coluna | Dado | Notas |
|---|---|---|
| Area | `area.descricao` em uppercase | Exibe "TODAS" se `area = null` |
| Vendedor | `vendedor.nome` em uppercase | Exibe "TODOS" se `vendedor = null` |
| Modalidade | `modalidade.intJogo.descricao` em uppercase | Exibe "TODAS" se `modalidade = null` |
| Comissao | `comissao` + "%" | |
| Ações | Editar + Excluir | Sem botão de visualizar |

**Sem filtro de busca** na lista — exibe todos os registros paginados. 🟡

### Formulário (`vendedor-comissao-modalidade-update.component.html`)

**Campos:**

| Campo | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Modalidade | `select` | Não | Sem opção "TODAS" no dropdown — campo pode ficar sem seleção |
| Area | `select` | Sim (form `required`) | Ao alterar área → AJAX recarrega lista de vendedores |
| Vendedor | `select` | Não | "TODOS" como opção explícita |
| Comissao | `number` | **Sim** | Sem validação de intervalo (0–100) no Java 🔴 |

> **Inconsistência Java:** `area` tem atributo `required` no HTML mas o modelo permite `area = null`. A validação real é a `invalidSelection()` que verifica se pelo menos um dos três campos foi preenchido. 🟡

**AJAX:** Ao selecionar área, lista de vendedores é recarregada via `areaVendedor(area.id)` — mesmo padrão do VendedorForm. 🟢

**Auditoria:** Salvar cria registro em `register_log` com ação "Comissão Área / Modalidade / Vendedor" e "Cadastrar" ou "Editar". 🟢

---

## Fluxo Principal

### Criar regra de comissão

```
1. Usuário acessa lista e clica "+"
2. VendedorComissaoModalidadeForm abre
3. Seleciona modalidade (opcional), área (obrigatório para AJAX vendedor), vendedor (opcional)
4. Preenche comissao (%)
5. Se TODOS os três são null → botão Salvar desabilitado
6. POST api/vendedorcomissaomodalidades
7. INSERT cfg_vendedor_mod_comissao
8. Log register_log
9. Redireciona para lista com alerta "Área / Modalidade / Vendedor Salva"
```

### Editar regra

```
1. Clique em "Editar" na linha
2. Form carregado com dados existentes
3. Alterações salvas via PUT api/vendedorcomissaomodalidades
4. Log register_log com "Editar"
5. Redireciona para lista com alerta "Área / Modalidade / Vendedor Editada"
```

### Excluir regra

```
1. Clique em "Excluir" → modal de confirmação (delete-dialog.component.html)
2. DELETE api/vendedorcomissaomodalidades/{id}
3. Redireciona para lista
```

---

## Regras de Negócio

- **RN-VC-01** `comissao` é obrigatório — sem percentual o registro não pode ser salvo 🟢
- **RN-VC-02** Pelo menos um de `area`, `vendedor` ou `modalidade` deve ser não nulo 🟢
- **RN-VC-03** `comissao` não tem validação de intervalo no Java — ao implementar no Zooloo validar 0 ≤ comissao ≤ 100 🔴
- **RN-VC-04** Ao selecionar área no form, lista de vendedores é recarregada via AJAX 🟢
- **RN-VC-05** Salvar e Editar registram auditoria em `register_log` com usuário e IP 🟢
- **RN-VC-06** A hierarquia de precedência entre `cfg_vendedor_mod_comissao` e `cfg_area_comissao_modalidade` não está documentada — presumivelmente o registro mais específico (vendedor > área > global) prevalece 🔴
- **RN-VC-07** Permissão `PERM_VENDEDOR_COMISSAO_MODALIDADE` necessária para criação (apenas oculta o botão no Java — não bloqueia a API) 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `cfg_vendedor_mod_comissao` | Tabela alvo — existe no banco |
| `cad_modalidade` | FK `modalidade_id` — modalidade com comissão personalizada |
| `cad_area` | FK `area_id` — restrição por área |
| `cad_vendedor` | FK `vendedor_id` — vendedor com comissão individual |
| `cfg_area_comissao_modalidade` | Módulo alternativo — relação de hierarquia não definida |
| `register_log` | Auditoria de criação e edição |
| `PERM_VENDEDOR_COMISSAO_MODALIDADE` | Permissão para criar/editar |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tabela `cfg_vendedor_mod_comissao` | `VendedorComissaoModalidade.java` | Existe no banco | 🔴 (sem entidade PHP) |
| Entidade PHP | N/A | Ausente | 🔴 |
| CRUD completo (List + Form + Delete) | Angular presente | Ausente | 🔴 |
| AJAX área → vendedor | Presente | Ausente | 🔴 |
| Auditoria register_log | Presente | Ausente | 🔴 |
| Validação comissao 0–100 | Ausente no Java | N/A | 🔴 (a corrigir) |
| Hierarquia de precedência com AreaComissaoModalidade | Não documentada | Não documentada | 🔴 |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — criar regra por vendedor específico
Dado que existe vendedor_id=5 na área_id=1 e modalidade_id=3
Quando usuário preenche area=1, vendedor=5, modalidade=3, comissao=7.5 e salva
Então registro criado em cfg_vendedor_mod_comissao
E log de auditoria gerado

# Happy path — criar regra para todos os vendedores de uma área
Quando usuário preenche area=1, vendedor=TODOS, modalidade=3, comissao=5.0 e salva
Então registro criado com area_id=1, vendedor_id=NULL, modalidade_id=3

# Falha — comissão acima de 100
Dado que comissao=150
Quando usuário tenta salvar
Então alerta "Comissão deve estar entre 0% e 100%"

# Falha — todos os campos vazios
Dado que area, vendedor e modalidade são nulos
Quando usuário tenta salvar
Então botão Salvar permanece desabilitado

# AJAX — recarregar vendedores por área
Dado que área=1 tem 3 vendedores e área=2 tem 5 vendedores
Quando usuário altera combo área de 1 para 2
Então combo vendedor recarrega com os 5 vendedores da área 2

# Auditoria
Dado que usuário "admin" com IP "192.168.1.1" cria uma regra
Então register_log recebe { acao: "Comissão Área / Modalidade / Vendedor", usuario: "admin", historico: "Cadastrar...", ip: "192.168.1.1" }
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Entidade PHP `VendedorComissaoModalidade` → `cfg_vendedor_mod_comissao` | Must | Base para o CRUD |
| CRUD List + Form (create/edit/delete) | Should | Permite configurar comissões individuais |
| AJAX área → vendedor no form | Should | Padrão UX do sistema |
| Validação comissão 0–100 | Must | Corrige lacuna do Java |
| Definir hierarquia de precedência com `cfg_area_comissao_modalidade` | Must | Sem isso a tabela não tem efeito prático |
| Auditoria register_log | Should | Rastreabilidade de configurações sensíveis |
| Controle de permissão `PERM_VENDEDOR_COMISSAO_MODALIDADE` | Should | Segurança de acesso |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../domain/VendedorComissaoModalidade.java` | Java — `@Table(name = "CFG_VENDEDOR_MOD_COMISSAO")` |
| `allsystem/.../web/rest/VendedorComissaoModalidadeResource.java` | Java — REST CRUD |
| `allsystem/.../webapp/app/entities/vendedor-comissao-modalidade/*.component.*` | Java — Angular |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/model/entities/VendedorComissaoModalidade.php` | TRecord → `cfg_vendedor_mod_comissao` |
| `app/control/vendedor-comissao/VendedorComissaoModalidadeList.php` | Lista com colunas área/vendedor/modalidade/comissão |
| `app/control/vendedor-comissao/VendedorComissaoModalidadeForm.php` | Form com AJAX área→vendedor + validação comissão 0–100 |
