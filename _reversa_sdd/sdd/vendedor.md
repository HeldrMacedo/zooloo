# Vendedor — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Vendedor** gerencia os pontos de venda de bilhetes da banca. Um vendedor é vinculado a uma área e a um gerente (setorista), possui credenciais de acesso ao app móvel e um conjunto detalhado de permissões operacionais (cancelar, pagar, reimprimir). Assim como o Gerente, segue o **padrão Dual-Entity**: cria simultaneamente um `SystemUser` e um registro `Vendedor` (`cad_vendedor`) na mesma transação. O `VendedorList` aplica filtro de área com base nas permissões do usuário autenticado.

---

## Responsabilidades

- Criar vendedor com criação simultânea de SystemUser (Dual-Entity) 🟢
- Editar dados pessoais, endereço, configurações financeiras e permissões 🟢
- Filtrar combo de gerente (setorista) dinamicamente por área via AJAX (`onChangeArea`) 🟢
- Listar vendedores com filtro de área baseado em permissões do usuário autenticado 🟢
- Ativar/desativar vendedores 🟢
- Exportar listagem em CSV, PDF e XML 🟢

---

## Interface

### VendedorForm — Seções e Campos

#### Dados Pessoais
| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `vendedor_id` | `TEntry` | `cad_vendedor.vendedor_id` | — | Não editável; auto MAX+1 |
| `nome` | `TEntry` | `cad_vendedor.nome` | Sim | Validado server-side |

#### Endereço
| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `cep` | `TEntry` | `cad_vendedor.cep` | Não | — |
| `rua` | `TEntry` | `cad_vendedor.rua` | Não | — |
| `numero` | `TEntry` | `cad_vendedor.numero` | Não | — |
| `bairro` | `TEntry` | `cad_vendedor.bairro` | Não | — |
| `cidade` | `TEntry` | `cad_vendedor.cidade` | Não | — |
| `uf` | `TCombo` | `cad_vendedor.uf` | Não | 27 UFs do Brasil |

#### Hierarquia
| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `area_id` | `TDBCombo` | `cad_vendedor.area_id` | Sim | Dispara `onChangeArea` ao mudar |
| `coletor_id` | `TDBCombo` | `cad_vendedor.coletor_id` | Sim | Desabilitado até área ser selecionada; filtrado por `area_id` + `ativo='S'` |

#### Configurações Financeiras
| Campo | Widget | Tabela.Coluna | Obrigatório | Padrão |
|---|---|---|---|---|
| `comissao` | `TNumeric` | `cad_vendedor.comissao` | Sim | — |
| `limite_venda` | `TNumeric` | `cad_vendedor.limite_venda` | Sim | — |
| `exibe_premiacao` | `TCombo` | `cad_vendedor.exibe_premiacao` | Sim | `S` (S/N/U=Último) |
| `exibe_comissao` | `TCombo` | `cad_vendedor.exibe_comissao` | Não | `S` |

#### Permissões Operacionais
| Campo | Widget | Tabela.Coluna | Padrão | Notas |
|---|---|---|---|---|
| `pode_cancelar` | `TCombo` | `cad_vendedor.pode_cancelar` | `N` | S/N |
| `pode_cancelar_tempo` | `TTime` | `cad_vendedor.pode_cancelar_tempo` | `00:00:00` | Janela de tempo para cancelamento |
| `pode_cancelar_qtde` | `TEntry` | `cad_vendedor.pode_cancelar_qtde` | `0` | Quantidade máxima |
| `pode_pagar` | `TCombo` | `cad_vendedor.pode_pagar` | `S` | — |
| `pode_pagar_outro` | `TCombo` | `cad_vendedor.pode_pagar_outro` | `N` | — |
| `pode_reimprimir` | `TCombo` | `cad_vendedor.pode_reimprimir` | `N` | — |
| `pode_reimprimir_qtde` | `TEntry` | `cad_vendedor.pode_reimprimir_qtde` | `0` | — |
| `pode_reimprimir_tempo` | `TTime` | `cad_vendedor.pode_reimprimir_tempo` | `00:00:00` | — |

#### Acesso ao Sistema
| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `login` | `TEntry` | `system_users.login` | Sim | Único; validado server-side |
| `password` | `TPassword` | `system_users.password` | Sim (novo) | MD5 sem salt ⚠️ |
| `repassword` | `TPassword` | — | Sim (se senha) | Confirmação |
| `ativo` | `TCombo` | `cad_vendedor.ativo` | Não | `S` |

**Campos na entidade sem widget no form atual:**

| Campo | Notas |
|---|---|
| `tipo_limite` | Comentado no form — `D`=Diário / `A`=Acumulado 🔴 |
| `treinamento` | Comentado no form 🔴 |
| `pode_reimprimir_sort_naopg` | Sem widget 🔴 |
| `pode_reimprimir_sort_pago` | Sem widget 🔴 |
| `pode_reimprimir_outro` | Sem widget 🔴 |
| `pode_reimprimir_sort_naopg_outro` | Sem widget 🔴 |
| `pode_reimprimir_sort_pago_outro` | Sem widget 🔴 |
| `reimprimir_data` | Sem widget 🔴 |
| `reimprimir_qtde` | Sem widget 🔴 |

---

## Regras de Negócio

- **RN-VE-01** `nome`, `login`, `area_id`, `coletor_id`, `comissao`, `limite_venda` e `exibe_premiacao` são obrigatórios 🟢
- **RN-VE-02** Login deve ser único em `system_users` — verificado via `SystemUser::newFromLogin()` apenas na criação 🟢
- **RN-VE-03** Em criação, `password` é obrigatória; em edição, apenas se preenchida 🟢
- **RN-VE-04** `password` e `repassword` devem ser iguais — validação server-side 🟢
- **RN-VE-05** Senha armazenada como **MD5 sem salt** — vulnerabilidade de segurança idêntica à do Gerente 🟢
- **RN-VE-06** Email do SystemUser é fabricado: `login@zooloo.com` — não é email real 🟢
- **RN-VE-07** O vendedor recebe automaticamente o grupo `VENDEDOR_GROUP_ID` no SystemUser 🟢
- **RN-VE-08** `SystemUser.function_name = SystemUser::FUNCTION_VENDEDOR` — identifica o tipo de usuário no sistema 🟢
- **RN-VE-09** `coletor_id` (setorista) é **desabilitado** na UI até que `area_id` seja selecionado via `onChangeArea` 🟢
- **RN-VE-10** `onChangeArea` recarrega o combo de setoristas filtrando por `area_id = X` e `ativo = 'S'` 🟢
- **RN-VE-11** `pode_cancelar_tempo` e `pode_reimprimir_tempo` com valor vazio são normalizados para `'00:00:00'` antes de persistir 🟢
- **RN-VE-12** `pode_cancelar_qtde` e `pode_reimprimir_qtde` com valor vazio são normalizados para `0` 🟢
- **RN-VE-13** `vendedor_id` usa `IDPOLICY='max'` — `MAX(vendedor_id) + 1` 🟢
- **RN-VE-14** Em modo edição, o campo `login` é carregado do `SystemUser` vinculado via `usuario_id` 🟢
- **RN-VE-15** `TForm::sendData('form_vendedor', {vendedor_id})` repopula o ID no form após salvar — sem o bug de typo presente no Gerente 🟢
- **RN-VE-16** `VendedorList::onReload` aplica filtro de área quando o usuário autenticado é Gerente: apenas vendedores da área do gerente são exibidos 🟢

---

## Fluxo Principal — Criar Vendedor

1. Usuário abre `VendedorList` → clica em "+"
2. `VendedorForm` abre no painel direito; campo `coletor_id` **desabilitado**
3. Usuário seleciona área → `onChangeArea` via AJAX:
   - Filtra gerentes ativos da área → recarrega combo `coletor_id` → habilita campo
4. Usuário preenche todos os campos e seleciona setorista
5. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - Validações customizadas server-side (nome, login, area_id, coletor_id, senha)
   - `new SystemUser()` → define `name`, `login`, `email`, `function_name`, `active`
   - `md5($password)` → `user->store()`
   - `new Vendedor()` → `fromArray($data)` → normaliza tempos/qtdes → `object->store()`
   - `user->addSystemUserGroup(new SystemGroup(VENDEDOR_GROUP_ID))`
   - `TForm::sendData('form_vendedor', {vendedor_id})`
   - `TTransaction::close()`

---

## Fluxo Alternativo — onChangeArea (AJAX)

```
Evento: usuário seleciona uma área no combo area_id
Chamada: VendedorForm::onChangeArea(['area_id' => X])
1. TTransaction::open('permission')
2. TRepository('Gerente')::load(criteria: area_id=X AND ativo='S')
3. Monta array $options[coletor_id] => nome
4. TCombo::reload('form_vendedor', 'coletor_id', $options)
5. TQuickForm::enableField('form_vendedor', 'coletor_id')
6. TTransaction::close()
```

---

## Fluxo Alternativo — Editar Vendedor

1. Usuário clica em "Editar" → `onEdit(['key' => $id])`:
   - `TTransaction::open('permission')`
   - `new Vendedor($key)` → se `usuario_id`: `new SystemUser($usuario_id)` → `object->login = user->login`
   - `form->setData($object)` — popula todos os campos
   - `TTransaction::close()`
2. Campo `coletor_id` fica habilitado pois a área já está preenchida (nota: `onChangeArea` não é re-disparado automaticamente ao editar) 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `Vendedor` (Active Record) | Entidade principal — `cad_vendedor` |
| `SystemUser` | Entidade de autenticação — criada na mesma transação |
| `SystemGroup` | Grupo `VENDEDOR_GROUP_ID` atribuído ao criar |
| `Gerente` | FK `coletor_id` — setorista responsável; filtrado por área no combo |
| `Area` | FK `area_id` — área de operação |
| `TStandardList` | Herança em VendedorList |
| `BilheteRestService` | Usa `Vendedor` para validar permissões de cancelamento, reimpressão e pagamento |
| `VendedorRestService` | REST `GET /vendedor/me` — retorna dados do vendedor autenticado |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Segurança | MD5 sem salt — VULNERABILIDADE CRÍTICA | `VendedorForm.php:305` | 🟢 |
| Segurança | Verificação de login duplicado antes de criar | `VendedorForm.php:290-293` | 🟢 |
| Consistência | Dual-entity em transação única — rollback total em falha | `VendedorForm.php:235-346` | 🟢 |
| Usabilidade | Combo setorista desabilitado até área ser selecionada | `VendedorForm.php:189`, `onChangeArea` | 🟢 |
| Acesso | VendedorList restringe visibilidade por área do gerente autenticado | `VendedorList — checkUserPermissions` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar vendedor
Dado que o usuário está em VendedorList
Quando clica em "+" e seleciona área=1, aguarda combo setorista carregar,
  seleciona coletor_id=2, preenche nome="João", login="joao", senha="123",
  confirmar="123", comissao=5.00, limite_venda=1000, ativo="S" e salva
Então um SystemUser é criado com login="joao", password=MD5("123"), email="joao@zooloo.com"
E um Vendedor é criado em cad_vendedor com usuario_id vinculado
E o vendedor recebe o grupo VENDEDOR_GROUP_ID
E TForm::sendData popula vendedor_id no formulário

# Happy path — onChangeArea
Dado que o usuário seleciona area_id=3
Quando onChangeArea é chamado via AJAX
Então o combo coletor_id é recarregado apenas com gerentes ativos da área 3
E o campo coletor_id é habilitado

# Happy path — editar sem alterar senha
Dado que existe o vendedor vendedor_id=5
Quando o usuário abre para edição, altera apenas nome="João Silva" e salva
Então apenas nome é atualizado em cad_vendedor e system_users
E a senha em system_users permanece inalterada

# Falha — coletor_id sem área
Dado que o usuário não selecionou area_id
Então o combo coletor_id permanece desabilitado
E o submit lança exceção "O campo Setorista é obrigatório"

# Falha — login duplicado
Dado que já existe um SystemUser com login="joao"
Quando tenta criar vendedor com login="joao"
Então exceção "Já existe um usuário com este login" é lançada
E nenhum registro é criado (rollback)

# Restrição de área na lista
Dado que o usuário autenticado é um Gerente da área 2
Quando acessa VendedorList
Então apenas vendedores com area_id=2 são exibidos na grid
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar vendedor (Dual-Entity) | Must | Sem vendedor não há ponto de venda |
| Permissões operacionais (cancelar/pagar/reimprimir) | Must | Usadas por BilheteRestService em cada aposta |
| onChangeArea — filtro de setorista | Must | UX obrigatória para evitar vincular vendedor ao gerente errado |
| Editar vendedor | Must | Manutenção de dados e credenciais |
| Correção MD5 → bcrypt | Must | Vulnerabilidade de segurança crítica |
| Restrição de área na lista | Must | Segurança de dados multi-área |
| Campos comentados (tipo_limite, treinamento) | Could | Funcionalidade parcial — sem UI atual |
| Campos sem widget (reimprimir_sort_*) | Could | Granularidade extra de permissão — sem UI atual |
| Exportação CSV/PDF/XML | Should | Relatórios administrativos |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/vendedor/VendedorForm.php` | `VendedorForm::__construct` | 🟢 |
| `app/control/vendedor/VendedorForm.php` | `VendedorForm::onSave` | 🟢 |
| `app/control/vendedor/VendedorForm.php` | `VendedorForm::onEdit` | 🟢 |
| `app/control/vendedor/VendedorForm.php` | `VendedorForm::onChangeArea` | 🟢 |
| `app/model/entities/Vendedor.php` | `Vendedor` (TRecord → `cad_vendedor`) | 🟢 |
| `app/model/entities/Vendedor.php` | `Vendedor::get_area`, `get_coletor`, `get_usuario` | 🟢 |
| `_reversa_sdd/flowcharts/vendedor.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/adrs/006-dual-entity-usuario-gerente-vendedor.md` | ADR Dual-Entity | 🟢 |
| `_reversa_sdd/adrs/003-md5-para-senhas.md` | ADR vulnerabilidade MD5 | 🟢 |
