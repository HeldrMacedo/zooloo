# Parâmetros — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Parâmetros** é um formulário singleton que centraliza as configurações globais da banca. A tabela `cfg_parametros` mantém exatamente um registro — identificado sempre pelo `parametros_id` existente. Contém dados cadastrais da banca (nome, CNPJ, endereço), mensagens personalizadas exibidas no app móvel, flags que habilitam/desabilitam tipos de jogo globalmente, e limites numéricos para modalidades invertidas. O campo `limite_global` (inferido do uso em `BilheteRestService`) serve como fallback de limite de aposta quando não existe configuração específica em `cfg_area_limite`.

> **Nota:** O campo `limite_global` não aparece no formulário atual nem na entidade PHP — a entidade e o form não o expõem. Sua existência é inferida do uso no `BilheteRestService` via `COALESCE`. 🔴

---

## Responsabilidades

- Editar os parâmetros globais da banca (singleton) 🟢
- Carregar automaticamente o registro existente ao abrir o formulário 🟢
- Garantir que apenas um registro exista em `cfg_parametros` 🟢
- Validar intervalos numéricos dos campos `qtde_num_*` 🟢
- Controlar feature flags de tipos de jogo (bilhetinho, quininha, seninha, etc.) 🟢
- Servir `limite_global` como fallback de limite de aposta para `BilheteRestService` 🟡

---

## Interface

### ParametrosForm — Campos

#### Dados Cadastrais da Banca
| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `parametros_id` | `TEntry` | `cfg_parametros.parametros_id` | — | Não editável; ID do registro singleton |
| `nome_banca` | `TEntry` | `cfg_parametros.nome_banca` | Sim | `TRequiredValidator` |
| `cnpj` | `TEntry` | `cfg_parametros.cnpj` | Não | — |
| `telefone` | `TEntry` | `cfg_parametros.telefone` | Não | — |
| `cidade` | `TEntry` | `cfg_parametros.cidade` | Não | — |
| `estado` | `TEntry` | `cfg_parametros.estado` | Não | — |
| `site` | `TEntry` | `cfg_parametros.site` | Não | — |
| `email` | `TEntry` | `cfg_parametros.email` | Não | — |

#### Mensagens Personalizadas
| Campo | Widget | Tabela.Coluna | Notas |
|---|---|---|---|
| `mensagem_01` | `TText` | `cfg_parametros.mensagem_01` | Exibida no app móvel / bilhete |
| `mensagem_02` | `TText` | `cfg_parametros.mensagem_02` | — |
| `mensagem_03` | `TText` | `cfg_parametros.mensagem_03` | — |
| `mensagem_04` | `TText` | `cfg_parametros.mensagem_04` | — |
| `mensagem_05` | `TText` | `cfg_parametros.mensagem_05` | — |

#### Configurações de Modalidades Invertidas
| Campo | Widget | Tabela.Coluna | Obrigatório | Regra |
|---|---|---|---|---|
| `valor_milhar_brinde` | `TNumeric` | `cfg_parametros.valor_milhar_brinde` | Não | Valor mínimo para ganhar brinde na milhar |
| `qtde_num_mi` | `TEntry` | `cfg_parametros.qtde_num_mi` | Não | Numérico; deve estar entre 4 e 10 |
| `qtde_num_ci` | `TEntry` | `cfg_parametros.qtde_num_ci` | Não | Numérico; deve estar entre 3 e 10 |
| `qtde_num_mci` | `TEntry` | `cfg_parametros.qtde_num_mci` | Não | Numérico; deve estar entre 4 e 10 |
| `ativo_milharpremiada` | `TCombo` | `cfg_parametros.ativo_milharpremiada` | Não | S/N |
| `valor_milharpremiada` | `TNumeric` | `cfg_parametros.valor_milharpremiada` | Não | Valor da milhar premiada |

#### Feature Flags — Tipos de Jogo
| Campo | Widget | Tabela.Coluna | Notas |
|---|---|---|---|
| `ativo_modalidade` | `TCombo` | `cfg_parametros.ativo_modalidade` | Habilita modalidades em geral |
| `ativo_bilhetinho` | `TCombo` | `cfg_parametros.ativo_bilhetinho` | Habilita Bilhetinho |
| `ativo_instantaneo` | `TCombo` | `cfg_parametros.ativo_instantaneo` | Habilita modo instantâneo |
| `ativo_quininha` | `TCombo` | `cfg_parametros.ativo_quininha` | Habilita Quininha |
| `ativo_seninha` | `TCombo` | `cfg_parametros.ativo_seninha` | Habilita Seninha |
| `ativo_lotinha` | `TCombo` | `cfg_parametros.ativo_lotinha` | Habilita Lotinha |
| `ativo_jb` | `TCombo` | `cfg_parametros.ativo_jb` | Habilita Jogo do Bicho |
| `limite_extracao` | `TCombo` | `cfg_parametros.limite_extracao` | Aplica limite por extração |

#### Configurações de Acumulação (Quininha/Seninha)
| Campo | Widget | Tabela.Coluna | Notas |
|---|---|---|---|
| `sena_inc_quina` | `TCombo` | `cfg_parametros.sena_inc_quina` | Sena inclui prêmio de quina |
| `sena_inc_quadra` | `TCombo` | `cfg_parametros.sena_inc_quadra` | Sena inclui prêmio de quadra |
| `quina_inc_quadra` | `TCombo` | `cfg_parametros.quina_inc_quadra` | Quina inclui prêmio de quadra |
| `quina_inc_terno` | `TCombo` | `cfg_parametros.quina_inc_terno` | Quina inclui prêmio de terno |

**Campos na entidade sem widget no form:**

| Campo | Notas |
|---|---|
| `valor_bilhetinho` | Existe na entidade, sem campo no formulário atual 🔴 |
| `limite_global` | Usado via COALESCE no BilheteRestService mas não está na entidade PHP 🔴 |

---

## Regras de Negócio

- **RN-PA-01** `nome_banca` é o único campo obrigatório 🟢
- **RN-PA-02** Apenas um registro é permitido em `cfg_parametros` — enforçado via `Parametros::where('parametros_id', '>', 0)->first()` 🟢
- **RN-PA-03** `qtde_num_mi` deve estar entre 4 e 10 (inclusive) — validação server-side 🟢
- **RN-PA-04** `qtde_num_ci` deve estar entre 3 e 10 (inclusive) — validação server-side 🟢
- **RN-PA-05** `qtde_num_mci` deve estar entre 4 e 10 (inclusive) — validação server-side 🟢
- **RN-PA-06** O formulário carrega automaticamente o registro existente ao abrir (sem parâmetro `key`) via `Parametros::where(...)->first()` 🟢
- **RN-PA-07** Feature flags (`ativo_bilhetinho`, `ativo_quininha`, etc.) controlam quais tipos de jogo o `BilheteRestService` aceita 🟡
- **RN-PA-08** `limite_global` (não exposto no form PHP) é lido pelo `BilheteRestService` como fallback via `COALESCE(cfg_area_limite.limite_palpite, cfg_parametros.limite_global)` 🔴
- **RN-PA-09** Mensagens 01–05 são enviadas ao app móvel e podem ser exibidas em bilhetes impressos ou na interface do vendedor 🟡
- **RN-PA-10** `parametros_id` usa `IDPOLICY='max'` mas na prática o registro sempre tem o mesmo ID 🟢

---

## Fluxo Principal — Editar Parâmetros

1. Usuário acessa `ParametrosForm` via menu "Configurações → Parâmetros"
2. `onEdit($param)` sem `key`:
   - `TTransaction::open('permission')`
   - `Parametros::where('parametros_id', '>', 0)->first()`
   - Se existe: `form->setData($existing)` — popula todos os campos
   - Se não existe: form fica em branco para criação inicial
   - `TTransaction::close()`
3. Usuário altera os campos desejados e clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - `form->validate()` (TRequiredValidator + TNumericValidator)
   - Valida intervalos: `qtde_num_mi` (4–10), `qtde_num_ci` (3–10), `qtde_num_mci` (4–10)
   - `Parametros::where('parametros_id', '>', 0)->first()` — verifica se já existe
   - Se existe e `parametros_id` está vazio no form: lança exceção de singleton
   - `new Parametros()` → `fromArray($data)` → `store()`
   - `TForm::sendData('form_parametros', {parametros_id})`
   - `TTransaction::close()`

---

## Padrão Singleton

```php
// onEdit — carrega automaticamente sem precisar de key
$existing = Parametros::where('parametros_id', '>', 0)->first();
if ($existing) {
    $this->form->setData($existing);
}

// onSave — impede criação de segundo registro
$existing = Parametros::where('parametros_id', '>', 0)->first();
if ($existing && empty($data->parametros_id)) {
    throw new Exception('Só é permitido um registro de parâmetros.');
}
```

---

## Integração com BilheteRestService (limite_global)

```sql
-- Lógica inferida (campo não exposto no form PHP)
SELECT COALESCE(al.limite_palpite, p.limite_global) AS limite_efetivo
FROM cfg_parametros p
LEFT JOIN cfg_area_limite al
  ON al.area_id = :area_id AND al.modalidade_id = :modalidade_id
LIMIT 1
```

O campo `limite_global` existe na tabela `cfg_parametros` do banco mas **não está mapeado** na entidade `Parametros.php` nem no `ParametrosForm.php`. É acessado diretamente via SQL raw no `BilheteRestService`. 🔴

---

## Dependências

| Componente | Relação |
|---|---|
| `Parametros` (Active Record) | Entidade principal — `cfg_parametros` (singleton) |
| `AreaLimite` | Quando não há limite por área, usa `limite_global` destes parâmetros via COALESCE |
| `BilheteRestService` | Lê feature flags e `limite_global` para validar bilhetes |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Singleton enforçado — máximo um registro | `ParametrosForm.php:187-191` | 🟢 |
| Integridade | Intervalos numéricos validados server-side | `ParametrosForm.php:174-184` | 🟢 |
| Consistência | Transação com rollback em operações de escrita | `ParametrosForm.php:168-210` | 🟢 |
| Lacuna | `limite_global` não exposto na UI — editável apenas via banco direto | Entidade Parametros.php | 🔴 |

---

## Critérios de Aceitação

```gherkin
# Happy path — editar parâmetros
Dado que existe um registro em cfg_parametros
Quando o usuário acessa ParametrosForm
Então o formulário é preenchido automaticamente com os valores atuais
E o usuário pode alterar e salvar sem criar um novo registro

# Happy path — alterar feature flag
Dado que ativo_bilhetinho="N"
Quando o usuário altera para ativo_bilhetinho="S" e salva
Então BilheteRestService passa a aceitar apostas de Bilhetinho

# Falha — qtde_num_mi fora do intervalo
Dado que o usuário preenche qtde_num_mi=2
Quando tenta salvar
Então exceção "Campo milhar não pode ser menor que 4 e nem maior que 10!" é lançada

# Falha — criação de segundo registro
Dado que já existe um registro em cfg_parametros
Quando o usuário tenta criar um novo registro (sem parametros_id no form)
Então exceção "Só é permitido um registro de parâmetros" é lançada

# Lacuna — limite_global não editável via UI
Dado que BilheteRestService usa cfg_parametros.limite_global via COALESCE
Então esse campo não é editável pela interface web atual
E só pode ser alterado via acesso direto ao banco
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Editar nome_banca e dados cadastrais | Must | Identificação da banca em bilhetes e relatórios |
| Feature flags (ativo_jb, ativo_bilhetinho, etc.) | Must | Controlam quais tipos de jogo são aceitos no BilheteRestService |
| Validação de intervalos qtde_num_* | Must | Valores fora do intervalo causam comportamento indefinido nas modalidades invertidas |
| Mensagens 01–05 | Should | Personalização do app e bilhetes |
| Expor limite_global na UI | Must | Campo crítico para lógica de limite — deve ser editável |
| Expor valor_bilhetinho na UI | Should | Campo na entidade sem widget |
| Configurações sena_inc_* e quina_inc_* | Should | Regras de acumulação de premiação |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/parametros/ParametrosForm.php` | `ParametrosForm::__construct` | 🟢 |
| `app/control/parametros/ParametrosForm.php` | `ParametrosForm::onSave` | 🟢 |
| `app/control/parametros/ParametrosForm.php` | `ParametrosForm::onEdit` | 🟢 |
| `app/model/entities/Parametros.php` | `Parametros` (TRecord → `cfg_parametros`) | 🟢 |
| `_reversa_sdd/flowcharts/parametros.md` | Fluxogramas Mermaid | 🟢 |
| `_reversa_sdd/traceability/spec-impact-matrix.md` | Impacto: BilheteRestService depende de limite_global | 🟢 |
