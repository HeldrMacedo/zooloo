# Extração Descarga — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Extração Descarga** configura o limite máximo de apostas acumuladas em um número específico por sorteio para cada combinação de extração e modalidade. "Descarga" é o mecanismo de controle de risco: quando o volume apostado em um número atinge o limite configurado, a banca para de aceitar apostas naquele número — protegendo-se de uma concentração de risco. O `BilheteRestService` verifica este limite em tempo real antes de aceitar cada bilhete.

---

## Responsabilidades

- Criar e editar limites de descarga por combinação extração/modalidade 🟢
- Validar unicidade da combinação (extracao_id + modalidade_id) — tanto na criação quanto na edição 🟢
- Validar que o limite de descarga é maior que zero 🟢
- Normalizar o valor do campo (`limite_descarga`) de formato BR (`1.000,00`) para float antes de persistir 🟢
- Formatar o valor de volta para BR ao carregar no formulário de edição 🟢
- Listar configurações de descarga com filtro por extração e modalidade 🟢

---

## Interface

### ExtracaoDescargaForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `extracao_descarga_id` | `TEntry` | `cfg_extracao_descarga.extracao_descarga_id` | — | Não editável; auto MAX+1 |
| `extracao_id` | `TDBCombo` | `cfg_extracao_descarga.extracao_id` | Sim | Filtra `ativo='S'`; ordenado por `descricao` |
| `modalidade_id` | `TDBCombo` | `cfg_extracao_descarga.modalidade_id` | Sim | Filtra `ativo='S'`; exibe `{jogo->descricao}`; ordenado por `ordem` |
| `limite_descarga` | `TEntry` | `cfg_extracao_descarga.limite_descarga` | Sim | Máscara numérica BR; `TRequiredValidator` + `TNumericValidator` |

> **Diferença em relação a outros módulos:** A validação de unicidade aqui cobre **tanto criação quanto edição** (`extracao_descarga_id != X` na edição), ao contrário de `AreaLimite` e `AreaComissaoModalidade` que só verificam na criação. 🟢

### ExtracaoDescargaList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `extracao_id` | `=` | `cfg_extracao_descarga.extracao_id` |
| `modalidade_id` | `=` | `cfg_extracao_descarga.modalidade_id` |

---

## Regras de Negócio

- **RN-ED-01** `extracao_id`, `modalidade_id` e `limite_descarga` são obrigatórios 🟢
- **RN-ED-02** A combinação (`extracao_id`, `modalidade_id`) deve ser única — verificada antes de toda persistência (criação e edição) 🟢
- **RN-ED-03** `limite_descarga` deve ser `> 0` — validado server-side após conversão para float 🟢
- **RN-ED-04** O valor é recebido no formato BR (`1.000,50`) e normalizado via `str_replace(',', '.')` antes de salvar 🟢
- **RN-ED-05** Ao carregar para edição, o valor é reformatado via `number_format($value, 2, ',', '.')` para exibição no campo mascarado 🟢
- **RN-ED-06** `extracao_descarga_id` usa `IDPOLICY='max'` — `MAX(id) + 1` 🟢
- **RN-ED-07** O combo de extração filtra `ativo='S'`; o combo de modalidade também filtra `ativo='S'` e exibe a descrição do jogo vinculado 🟢
- **RN-ED-08** O `BilheteRestService` verifica `cfg_extracao_descarga` ao registrar um bilhete — se o volume acumulado no número ultrapassar `limite_descarga`, a aposta é recusada 🟡
- **RN-ED-09** A verificação de descarga no BilheteRestService é por número apostado, não pelo total geral da extração/modalidade — cada número tem seu próprio contador de volume 🟡

---

## Fluxo Principal — Criar Limite de Descarga

1. Usuário abre `ExtracaoDescargaList` → clica em "+"
2. `ExtracaoDescargaForm` abre no painel direito
3. Usuário seleciona extração, modalidade e preenche o limite
4. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - Validações server-side: extracao_id, modalidade_id, limite_descarga obrigatórios
   - Converte: `$limite_num = (float) str_replace(',', '.', $data->limite_descarga)`
   - Valida: `$limite_num <= 0` → exceção
   - Constrói `TCriteria` com `extracao_id + modalidade_id`
   - Se `extracao_descarga_id` presente (edição): adiciona `extracao_descarga_id != X`
   - `TRepository('ExtracaoDescarga')::load($criteria)` → se existir: lança exceção de duplicidade
   - `new ExtracaoDescarga()` → `fromArray($data)` → `limite_descarga = $limite_num` → `store()`
   - `TForm::sendData('form_extracao_descarga', {id})`
   - `TTransaction::close()`

---

## Fluxo Alternativo — Editar Limite

1. Usuário clica em "Editar" → `onEdit(['key' => $id])`:
   - `TTransaction::open('permission')`
   - `new ExtracaoDescarga($key)`
   - Formata: `$object->limite_descarga = number_format($object->limite_descarga, 2, ',', '.')`
   - `form->setData($object)`
   - `TTransaction::close()`
2. Usuário altera `limite_descarga` e salva
3. `onSave`: verifica unicidade incluindo exclusão do próprio registro → normaliza → `store()`

---

## Conversão de Formato do Campo `limite_descarga`

```php
// Ao salvar: formato BR → float
$limite_num = (float) str_replace(',', '.', $data->limite_descarga);
// Ex: "1.500,75" → str_replace → "1.500.75" → (float) → 1.500 ← ATENÇÃO: perda de casas!
```

> **Risco:** `str_replace(',', '.')` em um valor como `"1.500,75"` produz `"1.500.75"`, que ao ser convertido para float resulta em `1.5` — não `1500.75`. O separador de milhar (`.`) conflita com o separador decimal após a substituição. 🔴

**Correção sugerida:**
```php
$limite_num = (float) str_replace(['.', ','], ['', '.'], $data->limite_descarga);
// "1.500,75" → remove . → "1500,75" → troca , por . → "1500.75" → float 1500.75
```

---

## Integração com BilheteRestService (Descarga)

```
Ao registrar bilhete:
1. BilheteRestService obtém extracao_id e modalidade_id do sorteio
2. Consulta cfg_extracao_descarga para o limite configurado
3. Soma o volume já apostado no número específico neste sorteio (via mov_jb)
4. Se volume_acumulado + valor_novo > limite_descarga → rejeita o bilhete
```

Esta verificação protege a banca de risco concentrado em números "quentes". 🟡

---

## Dependências

| Componente | Relação |
|---|---|
| `ExtracaoDescarga` (Active Record) | Entidade principal — `cfg_extracao_descarga` |
| `Extracao` | FK `extracao_id` — combo filtra `ativo='S'` |
| `Modalidade` | FK `modalidade_id` — combo filtra `ativo='S'` |
| `BilheteRestService` | Consulta esta tabela para validar descarga em tempo real |
| `TStandardList` | Herança em ExtracaoDescargaList |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Unicidade (extracao+modalidade) enforçada em criação E edição | `ExtracaoDescargaForm.php:107-122` | 🟢 |
| Integridade | Limite deve ser > 0 — proteção contra configuração zero | `ExtracaoDescargaForm.php:101-104` | 🟢 |
| Correção | Bug de conversão de formato BR → float com separador de milhar | `ExtracaoDescargaForm.php:100` | 🔴 |
| Consistência | Transação com rollback em operações de escrita | `ExtracaoDescargaForm.php:80-142` | 🟢 |
| Performance | BilheteRestService consulta esta tabela em cada registro de bilhete | Inferido do fluxo de negócio | 🟡 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar descarga
Dado que não existe descarga para extracao_id=1, modalidade_id=3
Quando o usuário preenche extracao=1, modalidade=3, limite=500,00 e salva
Então um registro é criado em cfg_extracao_descarga com limite_descarga=500.00
E BilheteRestService passa a recusar apostas no mesmo número acima de 500.00

# Falha — duplicidade (criação)
Dado que já existe descarga para extracao_id=1, modalidade_id=3
Quando o usuário tenta criar outra para a mesma combinação
Então exceção "Já existe uma configuração para esta extração e modalidade" é lançada

# Falha — duplicidade (edição)
Dado que existe descarga_id=5 para extracao=1, modalidade=3
E existe descarga_id=6 para extracao=1, modalidade=4
Quando o usuário edita descarga_id=5 e tenta mudar para modalidade=4
Então a verificação detecta conflito com descarga_id=6 e lança exceção

# Falha — limite zero
Dado que o usuário preenche limite_descarga=0
Quando tenta salvar
Então exceção "Limite Descarga deve ser maior que zero" é lançada

# Bug — conversão de formato com milhar
Dado que o usuário preenche limite_descarga="1.500,75"
Quando onSave executa str_replace(',', '.', "1.500,75")
Então o resultado é "1.500.75" que ao converter para float resulta em 1.5
E o valor salvo no banco é 1.50, não 1500.75 (BUG)

# Happy path — carregar para edição
Dado que existe descarga com limite_descarga=750.00 no banco
Quando o usuário clica em Editar
Então o campo exibe "750,00" (reformatado por number_format)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar descarga | Must | Controle de risco essencial para a banca |
| Validação de unicidade (criação e edição) | Must | Duplicatas causariam comportamento indefinido no BilheteRestService |
| Correção do bug de conversão BR→float com milhar | Must | Bug silencioso persiste valor errado no banco |
| Validação limite > 0 | Must | Descarga zero equivale a nenhum controle de risco |
| Integração com BilheteRestService | Must | Sem integração, a configuração não tem efeito |
| Listar e filtrar | Must | Gestão operacional |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/ExtracaoDescarga/ExtracaoDescargaForm.php` | `ExtracaoDescargaForm::__construct` | 🟢 |
| `app/control/ExtracaoDescarga/ExtracaoDescargaForm.php` | `ExtracaoDescargaForm::onSave` | 🟢 |
| `app/control/ExtracaoDescarga/ExtracaoDescargaForm.php` | `ExtracaoDescargaForm::onEdit` | 🟢 |
| `app/model/entities/ExtracaoDescarga.php` | `ExtracaoDescarga` (TRecord → `cfg_extracao_descarga`) | 🟢 |
| `_reversa_sdd/flowcharts/extracao-descarga.md` | Fluxogramas Mermaid | 🟢 |
