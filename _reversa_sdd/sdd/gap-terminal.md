# GAP: Terminal — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP parcial — entidade PHP existe (`Terminal.php`) mas sem controller, sem tela administrativa; gerenciado indiretamente pelo `TerminalRestService`

---

## Visão Geral

O módulo **Terminal** cadastra os dispositivos físicos pelos quais os vendedores registram apostas (smartphones, PDVs, PCs). Cada terminal é vinculado a um vendedor e possui serial único por dispositivo. O `BilheteRestService` valida que o `terminal_id` informado no bilhete pertence ao vendedor autenticado e está ativo antes de aceitar a aposta.

No Zooloo, terminais são criados e atualizados automaticamente pelo app via `TerminalRestService.registrar` (upsert por serial + vendedor). **Não existe tela administrativa** para o gestor visualizar, editar ou desativar terminais diretamente pela interface web.

---

## Estrutura de Dados

### `cad_terminal` — PHP (`Terminal.php`)

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `terminal_id` | PK | — | `IDPOLICY='max'` — MAX+1 |
| `vendedor_id` | FK | Sim | Referência a `cad_vendedor` |
| `tipo` | `varchar` | Sim | Valores: `PC`, `SMART`, `POS`, `MOBILE`, `APP` |
| `serial` | `varchar` | Sim | Identificador único do dispositivo |
| `multi_usuario` | `varchar` | Sim | `S`/`N` — indica se múltiplos usuários podem usar o terminal |
| `ativo` | `varchar` | Sim | `S`/`N` |

> **Campo ausente no PHP:** `area_id` existe na entidade Java (`@JoinColumn(name="AREA_ID")`) mas **não está mapeado** em `Terminal.php`. O Zooloo deriva a área a partir do vendedor (`vendedor->area_id`). 🔴

### `cad_terminal` — Java (allsystem)

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `terminal_id` | PK | — | Sequência PostgreSQL |
| `serial` | `varchar` | Sim (NOT NULL) | |
| `multi_usuario` | `boolean` | Sim (NOT NULL) | Convertido S/N pelo `BooleanToStringConverter` |
| `tipo` | `TipoTerminal` enum | Sim (NOT NULL) | PC, SMART, POS, MOBILE (TODOS é enum mas não usado no form) |
| `ativo` | `boolean` | Sim (NOT NULL) | |
| `area_id` | FK `cad_area` | Sim (NOT NULL) | Presente no Java; ausente no PHP |
| `vendedor_id` | FK `cad_vendedor` | Sim (NOT NULL) | |

---

## Interface no Sistema Java (allsystem — referência)

### TerminalList — Filtros

| Filtro | Campo |
|---|---|
| `serial` | `serial` (contém) |
| `tipo` | `tipo` = PC/SMART/POS/MOBILE |
| `area` | `area_id` (select; recarrega combo de vendedores) |
| `vendedor` | `vendedor_id` (dependente da área selecionada) |
| `ativo` | `ativo` = true/false/null (TODOS) |

**Colunas:** serial, multiploUsuario, tipo, ativo, area.descricao, vendedor.nome

**Ações:** Visualizar, Editar, Excluir

### TerminalForm — Campos

| Campo | Tipo | Obrigatório | Notas |
|---|---|---|---|
| `serial` | `text` | Sim | Identificador do dispositivo |
| `tipo` | `select` | Sim | PC / SMART / POS / MOBILE |
| `area` | `select` | Sim | Ao alterar: recarrega combo de vendedores (AJAX) |
| `vendedor` | `select` | Sim | Filtrado pela área selecionada |
| `multiploUsuario` | `checkbox` | Sim | Múltiplos usuários no terminal |
| `ativo` | `checkbox` | Sim | |

---

## TerminalRestService (Zooloo — implementado)

O único mecanismo de gestão de terminais no Zooloo é via API REST:

### `registrar` — Upsert por serial + vendedor

```php
// Busca terminal existente para o vendedor com aquele serial
Criteria: vendedor_id = X AND serial = Y

if (existe) {
    // Atualiza tipo e reativa
    $terminal->tipo = $tipo;
    $terminal->ativo = 'S';
    $terminal->store();
} else {
    // Cria novo
    $terminal->vendedor_id = $vendedor->vendedor_id;
    $terminal->serial      = $serial;
    $terminal->tipo        = $tipo; // padrão: 'APP'
    $terminal->multi_usuario = 'N';
    $terminal->ativo       = 'S';
    $terminal->store();
}
```

**Parâmetros:** `token` (JWT), `data.serial` (obrigatório), `data.tipo` (padrão: `'APP'`)

**Retorno:** `terminal_id`, `vendedor_id`, `serial`, `tipo`

> **Tipo padrão `APP`:** O Zooloo usa `'APP'` como tipo padrão no `TerminalRestService`, enquanto o enum Java tem apenas PC/SMART/POS/MOBILE/TODOS. `APP` é um tipo adicional não previsto no Java. 🟡

---

## Integração com BilheteRestService

```php
private static function validarTerminal($terminal_id, $vendedor_id)
{
    Criteria: terminal_id = X AND vendedor_id = Y AND ativo = 'S'
    if (empty) → throw new Exception('Terminal inválido para este vendedor');
}
```

O `terminal_id` é obrigatório no payload de `BilheteRestService::registrar`. Sem terminal válido, nenhum bilhete pode ser registrado. 🟢

---

## Responsabilidades Faltantes no Zooloo

| Responsabilidade | Status |
|---|---|
| Criar terminal via app (upsert) | ✅ Implementado (`TerminalRestService`) |
| Listar terminais pelo gestor web | 🔴 Ausente |
| Ativar/desativar terminal pelo gestor | 🔴 Ausente |
| Editar serial ou tipo pelo gestor | 🔴 Ausente |
| Vincular terminal a área (campo `area_id`) | 🔴 Ausente na entidade PHP |
| Gestão de `multi_usuario` | 🔴 Fixo em `'N'` via API; sem UI |
| Visualizar terminais por vendedor/área | 🔴 Ausente |

---

## Regras de Negócio

- **RN-TM-01** `serial` é obrigatório e identifica univocamente o dispositivo por vendedor 🟢
- **RN-TM-02** A combinação (`vendedor_id`, `serial`) deve ser única — enforçado implicitamente pelo upsert 🟢
- **RN-TM-03** `ativo='N'` impede que bilhetes sejam registrados pelo terminal — verificado em `BilheteRestService` 🟢
- **RN-TM-04** `tipo` padrão via API é `'APP'` — Java usava enum PC/SMART/POS/MOBILE 🟡
- **RN-TM-05** `multi_usuario` é sempre `'N'` via API — campo sem gestão 🟡
- **RN-TM-06** `area_id` presente no banco (legado Java) mas não mapeado na entidade PHP 🔴
- **RN-TM-07** `IDPOLICY='max'` — MAX(terminal_id)+1 para novos registros 🟢

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Entidade `Terminal.php` | `Terminal.java` | Existe (parcial) | Campo `area_id` faltando |
| Tela de listagem | Angular CRUD | Ausente | 🔴 |
| Tela de criação/edição | Angular CRUD | Ausente | 🔴 |
| Upsert via API | Ausente | `TerminalRestService` | ✅ |
| Filtro por área + vendedor | Presente (AJAX) | Ausente | 🔴 |
| Campo `area_id` | Sim | Não mapeado | 🔴 |
| Tipo `APP` | Não previsto | Padrão na API | Extensão |
| Desativação administrativa | Sim | Somente indireta | 🔴 |

---

## Critérios de Aceitação (propostos para implementação)

```gherkin
# Happy path — registrar terminal via API
Dado que vendedor autenticado tem usuario_id=5 e não há terminal com serial="ABC123"
Quando POST TerminalRestService::registrar {serial: "ABC123", tipo: "APP"}
Então novo terminal criado com vendedor_id=X, serial="ABC123", ativo='S'
E terminal_id é retornado

# Happy path — reutilizar terminal existente
Dado que já existe terminal com serial="ABC123" para o vendedor
Quando POST TerminalRestService::registrar novamente
Então terminal existente é atualizado (tipo + ativo='S')
E o mesmo terminal_id é retornado

# Falha — terminal de outro vendedor
Dado que terminal_id=10 pertence ao vendedor_id=3
Quando BilheteRestService tenta registrar bilhete com terminal_id=10 para vendedor_id=5
Então exceção "Terminal inválido para este vendedor"

# Falha — terminal inativo
Dado que terminal_id=10 tem ativo='N'
Quando BilheteRestService valida
Então exceção "Terminal inválido para este vendedor"

# Lacuna — desativar terminal
Dado que um dispositivo foi perdido/roubado
Quando o gestor quer desativar o terminal via UI
Então não há tela disponível — só possível via banco direto (lacuna)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Upsert via `TerminalRestService` | Must | Já implementado — necessário para bilhetes |
| Adicionar `area_id` ao PHP `Terminal.php` | Should | Consistência com schema do banco legado |
| Tela de listagem de terminais (por vendedor/área) | Must | Gestor precisa visualizar dispositivos ativos |
| Ativar/desativar terminal pela UI | Must | Sem isso, dispositivos perdidos só desativáveis via banco |
| Gestão de `multi_usuario` via UI | Should | Atualmente hardcoded em 'N' |
| Filtro por área (AJAX vendedor dependente) | Should | Padrão do Java; melhora navegação |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Sistema |
|---|---|---|
| `app/model/entities/Terminal.php` | `Terminal` (TRecord → `cad_terminal`) | Zooloo ✅ |
| `app/service/rest/TerminalRestService.php` | `registrar` | Zooloo ✅ |
| `app/service/rest/BilheteRestService.php` | `validarTerminal` | Zooloo ✅ |
| `allsystem/.../domain/Terminal.java` | `Terminal` (JPA → `CAD_TERMINAL`) | Java |
| `allsystem/.../domain/enumeration/TipoTerminal.java` | Enum PC/SMART/POS/MOBILE/TODOS | Java |
| `allsystem/.../web/rest/TerminalResource.java` | REST CRUD | Java |
| `allsystem/.../webapp/app/entities/terminal/` | Angular CRUD | Java |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/terminal/TerminalList.php` | Lista com filtros por serial, tipo, área, vendedor, ativo |
| `app/control/terminal/TerminalForm.php` | Form com área → vendedor AJAX (como VendedorForm) |
