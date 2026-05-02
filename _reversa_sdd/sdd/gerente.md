# Gerente — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Gerente** (também chamado internamente de "Coletor") gerencia os supervisores/gerentes das áreas da banca. Um gerente possui acesso ao sistema web e é vinculado a uma área. A criação de um gerente implementa o **padrão Dual-Entity**: uma única transação cria simultaneamente um `SystemUser` (autenticação Adianti) e um registro `Gerente` (`cad_coletor`) vinculados pelo `usuario_id`.

> **Atenção:** A tabela de domínio é `cad_coletor` — o nome "Gerente" é uma camada de abstração PHP sobre o conceito de "coletor" do banco legado.

---

## Responsabilidades

- Criar gerente com criação simultânea de SystemUser (Dual-Entity) 🟢
- Editar dados do gerente e credenciais do SystemUser vinculado 🟢
- Listar gerentes com filtro por id, nome, área e status ativo 🟢
- Ativar/desativar gerente **com sincronização** do SystemUser (`active` Y/N) 🟢
- Vincular gerente a uma área (`area_id` FK para `cad_area`) 🟢
- Exportar listagem em CSV, PDF e XML 🟢

---

## Interface

### GerenteForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Padrão | Notas |
|---|---|---|---|---|---|
| `coletor_id` | `TEntry` | `cad_coletor.coletor_id` | — | auto (MAX+1) | Não editável |
| `nome` | `TEntry` | `cad_coletor.nome` | Sim | — | `TRequiredValidator` |
| `login` | `TEntry` | `system_users.login` | Sim | — | Único no sistema; validado server-side |
| `password` | `TPassword` | `system_users.password` | Sim (novo) | — | Campo com espaço no nome: `'password '` ⚠️ |
| `repassword` | `TPassword` | — | Sim (se senha) | — | Confirmação; comparado com `password` |
| `area_id` | `TDBCombo` | `cad_coletor.area_id` | Sim | — | `TRequiredValidator`; lista de `cad_area` |
| `ativo` | `TCombo` | `cad_coletor.ativo` | Não | `S` | S=Sim / N=Não |

> **Bug confirmado:** `TPassword('password ')` contém espaço no nome do campo — pode causar problemas de captura de valor via `$data->password`. 🟢

### GerenteList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `coletor_id` | `=` | `cad_coletor.coletor_id` |
| `nome` | `LIKE` | `cad_coletor.nome` |
| `area_id` | `=` | `cad_coletor.area_id` |
| `ativo` | `=` | `cad_coletor.ativo` |

Ordenação padrão: `nome ASC` 🟢

### GerenteList — Colunas exibidas

| Coluna | Fonte |
|---|---|
| Id | `coletor_id` |
| Nome | `nome` |
| Login | `usuario->login` (lazy via `get_usuario()`) |
| Acesso Web | `acesso_web` (badge S/N) |
| Area | `area->descricao` (lazy via `get_area()`) |
| Ativo | `ativo` (badge verde/vermelho) |

---

## Regras de Negócio

- **RN-GE-01** `nome`, `login` e `area_id` são obrigatórios 🟢
- **RN-GE-02** Login deve ser único em `system_users` — verificado via `SystemUser::newFromLogin()` 🟢
- **RN-GE-03** Em criação, `password` é obrigatória; em edição, apenas se preenchida 🟢
- **RN-GE-04** `password` e `repassword` devem ser iguais — validação server-side 🟢
- **RN-GE-05** Senha é armazenada como **MD5 sem salt** (`md5($password)`) — vulnerabilidade de segurança conhecida 🟢
- **RN-GE-06** Senhas antigas são registradas em `SystemUserOldPassword` (histórico) 🟢
- **RN-GE-07** O `email` do SystemUser é fabricado: `login@zooloo.com` — não é e-mail real do gerente 🟢
- **RN-GE-08** O gerente recebe automaticamente o grupo `GERENTE_GROUP_ID` no SystemUser 🟢
- **RN-GE-09** `SystemUser.active` é `Y` em criação (sempre ativo ao criar) 🟢
- **RN-GE-10** `coletor_id` é gerado por `IDPOLICY='max'` — `MAX(coletor_id) + 1` 🟢
- **RN-GE-11** Toggle ativo sincroniza `cad_coletor.ativo` (`S`/`N`) + `system_users.active` (`Y`/`N`) na mesma transação 🟢
- **RN-GE-12** `acesso_web` e `outras_areas` são atributos da entidade mas não têm campos editáveis no formulário atual 🟡
- **RN-GE-13** TODO documentado: ao inativar gerente no formulário (onSave), o SystemUser **não** é desativado — apenas o List (onTurnOnOff) sincroniza 🟢

---

## Fluxo Principal — Criar Gerente

1. Usuário abre `GerenteList` → clica em "+"
2. `GerenteForm` abre no painel direito
3. Usuário preenche nome, login, senha, confirmação de senha, área e ativo
4. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - `new SystemUser()` → `fromArray($data)` → define `name`, `email` (`login@zooloo.com`), `active`
   - Valida login único via `SystemUser::newFromLogin()`
   - Valida senha obrigatória (nova criação) e confirmação
   - `md5($password)` → `systemUser->store()`
   - `SystemUserOldPassword::validate()` + `register()` (histórico de senhas)
   - `systemUser->getUserGerenteForUser()` → se `null`: `new Gerente()`, senão atualiza existente
   - `gerente->usuario_id = systemUser->id` → `gerente->store()`
   - `systemUser->addSystemUserGroup(new SystemGroup(GERENTE_GROUP_ID))`
   - `TForm::sendData('form_ferente', {id})` ⚠️ **BUG TYPO** — deveria ser `'form_gerente'`
   - `TTransaction::close()`
   - Dispara `GerenteList::onReload` como pós-ação

---

## Fluxo Principal — Editar Gerente

1. Usuário clica em "Editar" na lista
2. `onEdit($param['key'])`:
   - `TTransaction::open('permission')`
   - `new Gerente($key)` → `gerente->get_usuario()->login` para popular campo login
   - `form->setData($gerente)` → popula formulário
   - `TTransaction::close()`
3. Usuário altera campos e salva
4. No `onSave`: detecta `coletor_id` preenchido → `Gerente::find($coletor_id)` → `SystemUser::load(usuario_id)` → atualiza sem criar novo

---

## Fluxo Alternativo — Toggle Ativo

1. Usuário clica em "Ativar/Desativar" na linha da lista
2. `onTurnOnOff($param['coletor_id'])`:
   - `TTransaction::open('permission')`
   - `Gerente::find(coletor_id)` → inverte `ativo` (`S`↔`N`) → `gerente->store()`
   - `SystemUser::find(gerente->usuario_id)` → inverte `active` (`Y`↔`N`) → `user->store()`
   - `TTransaction::close()` → `onReload()`
3. **Ambos os registros** são atualizados na mesma transação 🟢

---

## Bug Documentado — form_ferente

```php
// GerenteForm.php:172
TForm::sendData('form_ferente', $data); // ← TYPO: deveria ser 'form_gerente'
```

**Impacto:** Após salvar, o `coletor_id` gerado **não** é repopulado no formulário. O formulário permanece como "novo" em vez de mudar para modo edição. Salvar novamente criará um segundo gerente duplicado.

**Correção:** Alterar `'form_ferente'` para `'form_gerente'`.

---

## Dependências

| Componente | Relação |
|---|---|
| `Gerente` (Active Record) | Entidade principal — `cad_coletor` |
| `SystemUser` | Entidade de autenticação criada na mesma transação |
| `SystemGroup` | Grupo GERENTE_GROUP_ID atribuído ao criar |
| `SystemUserOldPassword` | Histórico de senhas |
| `Area` | FK `area_id` — combo de seleção de área |
| `TStandardList` | Herança em GerenteList — onReload, onDelete, exportação |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Segurança | MD5 sem salt — VULNERABILIDADE CRÍTICA | `GerenteForm.php:132` | 🟢 |
| Segurança | Verificação de login duplicado antes de criar | `GerenteForm.php:109` | 🟢 |
| Consistência | Dual-entity em transação única — rollback total em falha | `GerenteForm.php:79-183` | 🟢 |
| Consistência | Toggle ativo sincroniza Gerente + SystemUser na mesma transação | `GerenteList.php:264-276` | 🟢 |
| Integridade | Histórico de senhas via SystemUserOldPassword | `GerenteForm.php:136-137,163-165` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar gerente
Dado que o usuário está na tela GerenteList
Quando clica em "+" e preenche nome="Maria", login="maria", senha="abc123",
  confirmar="abc123", area=1, ativo="S" e salva
Então um SystemUser é criado com login="maria", password=MD5("abc123"), email="maria@zooloo.com"
E um Gerente é criado em cad_coletor com usuario_id vinculado ao SystemUser
E o gerente recebe o grupo GERENTE_GROUP_ID
E a lista é recarregada com a nova linha

# Happy path — editar gerente (sem alterar senha)
Dado que existe o gerente coletor_id=3
Quando o usuário clica em "Editar", altera nome="Maria Silva" e salva sem preencher senha
Então apenas o campo nome é atualizado em cad_coletor
E a senha em system_users permanece inalterada

# Happy path — toggle desativar
Dado que o gerente coletor_id=3 tem ativo="S" e usuario_id=10
Quando o usuário clica em "Ativar/Desativar" nessa linha
Então cad_coletor.ativo="N" E system_users.active="N" são atualizados na mesma transação
E a lista é recarregada exibindo o badge vermelho

# Falha — login duplicado
Dado que já existe um SystemUser com login="maria"
Quando o usuário tenta criar um novo gerente com login="maria"
Então uma exceção é lançada com mensagem de login já registrado
E nenhum registro é criado (rollback)

# Falha — senhas não coincidem
Dado que o usuário preenche senha="abc" e confirmar="xyz"
Quando clica em salvar
Então uma exceção é lançada com mensagem "The passwords do not match"
E nenhum registro é criado

# Bug — form_ferente (comportamento atual)
Dado que um novo gerente é criado com sucesso
Então o campo coletor_id NO formulário NÃO é preenchido (bug: sendData('form_ferente'))
E um segundo clique em "Salvar" criará um gerente duplicado
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar gerente (Dual-Entity) | Must | Pré-requisito para acesso administrativo ao sistema |
| Editar gerente | Must | Manutenção de dados e credenciais |
| Toggle ativo com sync SystemUser | Must | Controla acesso ao sistema |
| Listar e filtrar | Must | Navegação principal do módulo |
| Correção do bug form_ferente | Must | Bug causa duplicações de gerente |
| Correção MD5 → bcrypt | Must | Vulnerabilidade de segurança crítica |
| Exportação CSV/PDF/XML | Should | Relatórios administrativos |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/gerente/GerenteForm.php` | `GerenteForm::__construct` | 🟢 |
| `app/control/gerente/GerenteForm.php` | `GerenteForm::onSave` | 🟢 |
| `app/control/gerente/GerenteForm.php` | `GerenteForm::onEdit` | 🟢 |
| `app/control/gerente/GerenteList.php` | `GerenteList::__construct` | 🟢 |
| `app/control/gerente/GerenteList.php` | `GerenteList::onTurnOnOff` | 🟢 |
| `app/model/entities/Gerente.php` | `Gerente` (TRecord → `cad_coletor`) | 🟢 |
| `app/model/entities/Gerente.php` | `Gerente::get_usuario` | 🟢 |
| `app/model/entities/Gerente.php` | `Gerente::get_area` | 🟢 |
| `_reversa_sdd/flowcharts/gerente.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/adrs/006-dual-entity-usuario-gerente-vendedor.md` | ADR decisão Dual-Entity | 🟢 |
| `_reversa_sdd/adrs/003-md5-para-senhas.md` | ADR vulnerabilidade MD5 | 🟢 |
