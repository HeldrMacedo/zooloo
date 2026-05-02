# Palpite Cotado — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

O módulo **Palpite Cotado** configura cotações especiais para números (palpites) específicos dentro de uma modalidade. Permite que a banca ofereça multiplicadores diferenciados por número — por exemplo, "bicho Avestruz (palpite 0001) paga 15x" enquanto o padrão da modalidade é 6x. O `ModalidadeRestService` injeta os palpites cotados na resposta de modalidades disponíveis, e o app móvel exibe e aplica esses valores diferenciados ao vendedor.

---

## Responsabilidades

- Criar e editar cotações especiais por combinação modalidade/palpite 🟢
- Validar que o palpite tem exatamente 4 dígitos numéricos 🟢
- Validar unicidade da combinação (modalidade_id + palpite) em criação e edição 🟢
- Validar que a cotação está entre 0 e 100 🟢
- Normalizar o campo `cotacao` de formato BR para float antes de persistir 🟢
- Ativar/desativar cotações especiais por palpite (campo `ativo`) 🟢
- Listar palpites cotados com filtro por modalidade e palpite 🟢
- Ser consumido pelo `ModalidadeRestService` para retornar cotações ao app 🟢

---

## Interface

### PalpiteCotadoForm — Campos

| Campo | Widget | Tabela.Coluna | Obrigatório | Notas |
|---|---|---|---|---|
| `palpite_cotado_id` | `TEntry` | `cfg_palpite_cotado.palpite_cotado_id` | — | Não editável; auto MAX+1 |
| `modalidade_id` | `TDBCombo` | `cfg_palpite_cotado.modalidade_id` | Sim | Filtra `ativo='S'` e `multiplicador IS NOT NULL`; exibe `{jogo->descricao}`; ordenado por `ordem` |
| `palpite` | `TEntry` | `cfg_palpite_cotado.palpite` | Sim | Máscara `9999`; exatamente 4 dígitos; apenas numérico |
| `cotacao` | `TEntry` | `cfg_palpite_cotado.cotacao` | Sim | Máscara `99,99`; numérica 2 casas; intervalo 0–100 |
| `ativo` | `TCombo` | `cfg_palpite_cotado.ativo` | Não | `S`=Sim / `N`=Não; padrão `S` |

> **Filtro especial do combo:** Modalidades com `multiplicador IS NOT NULL` — exclui modalidades sem multiplicador definido (que não aceitam palpites cotados). 🟢

> **Inconsistência:** O label do campo `cotacao` diz "Cotação (%)" mas o intervalo validado é 0–100, sugerindo que é um percentual. Porém, na `AreaCotacao` o campo equivalente chama-se `multiplicador` e representa um multiplicador direto (ex: 4000). A semântica exata do campo `cotacao` nesta tabela é diferente — pode ser um percentual de ajuste ou um multiplicador. 🔴

### PalpiteCotadoList — Filtros

| Filtro | Operador | Campo |
|---|---|---|
| `modalidade_id` | `=` | `cfg_palpite_cotado.modalidade_id` |
| `palpite` | `=` | `cfg_palpite_cotado.palpite` |

---

## Regras de Negócio

- **RN-PC-01** `modalidade_id`, `palpite` e `cotacao` são obrigatórios 🟢
- **RN-PC-02** `palpite` deve ter exatamente 4 dígitos — validado via `strlen == 4` e `is_numeric` 🟢
- **RN-PC-03** `cotacao` deve estar entre 0 e 100 — validado server-side após conversão BR→float 🟢
- **RN-PC-04** A combinação (`modalidade_id`, `palpite`) deve ser única — verificada em criação e edição 🟢
- **RN-PC-05** `cotacao` é recebida no formato BR e convertida via `str_replace(',', '.')` antes de persistir — **mesmo risco de bug que ExtracaoDescarga** se o valor tiver separador de milhar 🔴
- **RN-PC-06** Ao carregar para edição, `cotacao` é reformatada via `number_format($value, 2, ',', '.')` 🟢
- **RN-PC-07** O combo de modalidade filtra `ativo='S'` **e** `multiplicador IS NOT NULL` — modalidades sem multiplicador não aparecem 🟢
- **RN-PC-08** Campo `ativo` (`S`/`N`) permite desativar uma cotação especial sem excluí-la 🟢
- **RN-PC-09** `palpite_cotado_id` usa `IDPOLICY='max'` — `MAX(id) + 1` 🟢
- **RN-PC-10** O `ModalidadeRestService` consulta `cfg_palpite_cotado WHERE modalidade_id = X` para cada modalidade retornada e injeta o array `palpites_cotados` na resposta JSON 🟢

---

## Fluxo Principal — Criar Palpite Cotado

1. Usuário abre `PalpiteCotadoList` → clica em "+"
2. `PalpiteCotadoForm` abre no painel direito
3. Usuário seleciona modalidade, preenche palpite (4 dígitos) e cotação
4. Clica em "Salvar" → `onSave($param)`:
   - `TTransaction::open('permission')`
   - Validações: `modalidade_id`, `palpite` (obrigatório, 4 dígitos, numérico), `cotacao` (obrigatório, 0–100)
   - Converte: `$cotacao_num = (float) str_replace(',', '.', $data->cotacao)`
   - Constrói `TCriteria` com `modalidade_id + palpite`
   - Se `palpite_cotado_id` presente (edição): adiciona `palpite_cotado_id != X`
   - `TRepository('PalpiteCotado')::load($criteria)` → se existir: lança exceção de duplicidade
   - `new PalpiteCotado()` → `fromArray($data)` → `cotacao = $cotacao_num` → `store()`
   - `TForm::sendData('form_palpite_cotado', {id})`
   - `TTransaction::close()`

---

## Fluxo Alternativo — Editar Palpite Cotado

1. Usuário clica em "Editar" → `onEdit(['key' => $id])`:
   - `TTransaction::open('permission')`
   - `new PalpiteCotado($key)`
   - Formata: `$object->cotacao = number_format($object->cotacao, 2, ',', '.')`
   - `form->setData($object)`
   - `TTransaction::close()`
2. Usuário altera campos e salva — unicidade re-verificada excluindo o próprio registro

---

## Integração com ModalidadeRestService

```
GET /rest.php?class=ModalidadeRestService&method=disponiveis

Para cada modalidade retornada:
  SELECT palpite, cotacao
  FROM cfg_palpite_cotado
  WHERE modalidade_id = X AND ativo = 'S'

Resposta JSON por modalidade:
{
  "modalidade_id": 3,
  "apresentacao": "Milhar",
  "multiplicador": 6000,
  "palpites_cotados": [
    { "palpite": "0001", "cotacao": 15.00 },
    { "palpite": "0025", "cotacao": 12.50 }
  ]
}
```

O app móvel usa `palpites_cotados` para exibir cotações diferenciadas por número ao vendedor. 🟢

---

## Dependências

| Componente | Relação |
|---|---|
| `PalpiteCotado` (Active Record) | Entidade principal — `cfg_palpite_cotado` |
| `Modalidade` | FK `modalidade_id`; combo filtra `ativo='S'` e `multiplicador IS NOT NULL` |
| `ModalidadeRestService` | Lê esta tabela e injeta `palpites_cotados` na resposta |
| `TStandardList` | Herança em PalpiteCotadoList |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Requisitos Não Funcionais

| Tipo | Requisito | Evidência | Confiança |
|---|---|---|---|
| Integridade | Unicidade (modalidade+palpite) em criação e edição | `PalpiteCotadoForm.php:125-140` | 🟢 |
| Integridade | Palpite exatamente 4 dígitos numéricos | `PalpiteCotadoForm.php:103-111` | 🟢 |
| Integridade | Cotação entre 0–100 após conversão | `PalpiteCotadoForm.php:118-122` | 🟢 |
| Correção | Bug de conversão BR→float com separador de milhar (mesmo que ExtracaoDescarga) | `PalpiteCotadoForm.php:118` | 🔴 |
| Consistência | Transação com rollback em operações de escrita | `PalpiteCotadoForm.php:88-160` | 🟢 |

---

## Critérios de Aceitação

```gherkin
# Happy path — criar palpite cotado
Dado que não existe cotação para modalidade_id=3, palpite="0001"
Quando o usuário seleciona modalidade=3, preenche palpite="0001", cotacao="15,00", ativo="S" e salva
Então um registro é criado em cfg_palpite_cotado com cotacao=15.00
E ModalidadeRestService inclui {"palpite":"0001","cotacao":15.00} na resposta

# Falha — palpite com menos de 4 dígitos
Dado que o usuário preenche palpite="001"
Quando tenta salvar
Então exceção "Palpite deve ter exatamente 4 dígitos" é lançada

# Falha — palpite não numérico
Dado que o usuário preenche palpite="AB01"
Quando tenta salvar
Então exceção "Palpite deve conter apenas números" é lançada

# Falha — duplicidade
Dado que já existe cotação para modalidade_id=3, palpite="0001"
Quando tenta criar outra para a mesma combinação
Então exceção "Já existe um palpite cotado para esta modalidade e palpite" é lançada

# Happy path — desativar cotação sem excluir
Dado que existe palpite_cotado_id=5 com ativo="S"
Quando o usuário edita e altera ativo="N" e salva
Então o registro permanece mas com ativo="N"
E ModalidadeRestService não retorna mais esse palpite (filtra ativo='S')

# Falha — cotação fora do intervalo
Dado que o usuário preenche cotacao="150,00"
Quando tenta salvar
Então exceção "Cotação deve estar entre 0 e 100%" é lançada
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Criar/editar palpite cotado | Must | Permite diferenciação de cotação por número — estratégia de negócio |
| Validação de 4 dígitos numéricos | Must | Palpite inválido quebraria lookup no BilheteRestService |
| Validação de unicidade (criação + edição) | Must | Duplicatas causariam comportamento indefinido no ModalidadeRestService |
| Integração com ModalidadeRestService | Must | Sem isso a configuração não tem efeito no app |
| Campo `ativo` — desativar sem excluir | Should | Permite suspender temporariamente sem perder histórico |
| Correção do bug de conversão BR→float | Must | Mesmo bug crítico do ExtracaoDescarga |
| Clarificação da semântica de `cotacao` (% vs multiplicador) | Should | Campo ambíguo — definir contrato claro com o app |

---

## Rastreabilidade de Código

| Arquivo | Classe / Função | Cobertura |
|---|---|---|
| `app/control/palpite-cotado/PalpiteCotadoForm.php` | `PalpiteCotadoForm::__construct` | 🟢 |
| `app/control/palpite-cotado/PalpiteCotadoForm.php` | `PalpiteCotadoForm::onSave` | 🟢 |
| `app/control/palpite-cotado/PalpiteCotadoForm.php` | `PalpiteCotadoForm::onEdit` | 🟢 |
| `app/model/entities/PalpiteCotado.php` | `PalpiteCotado` (TRecord → `cfg_palpite_cotado`) | 🟢 |
| `_reversa_sdd/flowcharts/palpite-cotado.md` | Fluxogramas Mermaid + integração ModalidadeRestService | 🟢 |
