# GAP: Milhar Premiada — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> **Status:** GAP — módulo presente no Java (allsystem) com lista + edição inline, mas **completamente ausente** no Zooloo PHP (sem entidade específica, sem controller, sem tela).

---

## Visão Geral

**Milhar Premiada** é um tipo de jogo onde o apostador escolhe uma milhar e concorre a prêmios distribuídos em até 5 colocações. A tela de configuração permite ajustar o valor do bilhete (`multiplicador`), os multiplicadores de prêmio por colocação (`multiplicadorColocacao01..05`) e os limites de aposta/descarga.

O módulo opera sobre a tabela `cad_modalidade` — a mesma das demais modalidades — filtrada pelo tipo de jogo Milhar Premiada. A interface usa o padrão **lista + edição inline** (formulário aparece no topo ao clicar "Edit", sem rota separada), idêntico ao módulo Bilhetinho.

| Aspecto | Valor |
|---|---|
| Tabela | `cad_modalidade` |
| API de listagem | `GET api/milharpremiada` |
| API de atualização | `PUT api/milharpremiada` |
| Padrão de UI | Lista + inline edit (sem rota separada de form) |
| Sem botão Criar | Registros pré-seeded 🟢 |
| Sem botão Excluir | Somente edição 🟢 |
| Auditoria register_log | **Ausente** 🔴 |

> **Diferença-chave vs Bilhetinho:** Milhar Premiada usa `cad_modalidade` + campos `multiplicadorColocacao01..05` de uma única linha; Bilhetinho usa `cad_modalidade_bilhetinho` com chave composta `(modalidade_id, colocacao)` — uma linha por colocação. Aqui, os 5 multiplicadores estão na mesma linha da `cad_modalidade`. 🟢

---

## Estrutura de Dados

### `cad_modalidade` — campos usados por Milhar Premiada

| Campo | Tipo | Obrigatório | Label no form | Notas |
|---|---|---|---|---|
| `modalidade_id` | PK | — | — | Identificador |
| `int_jogo_id` | FK `int_jogo` | — | "Milhar Premiada" (readonly) | Tipo de jogo — filtro de listagem |
| `apresentacao` | `varchar` | **Sim** | Apresentação | Nome de exibição |
| `ativo` | `boolean` | — | Ativo | Ativo/inativo |
| `multiplicador` | `double` | **Sim** | Valor Milhar Premiada | Preço do bilhete |
| `multiplicador_colocacao_01` | `double` | **Sim** | Multip. 1º prêmio | Prêmio da 1ª colocação |
| `multiplicador_colocacao_02` | `double` | **Sim** | Multip. 2º prêmio | Prêmio da 2ª colocação |
| `multiplicador_colocacao_03` | `double` | **Sim** | Multip. 3º prêmio | Prêmio da 3ª colocação |
| `multiplicador_colocacao_04` | `double` | **Sim** | Multip. 4º prêmio | Prêmio da 4ª colocação |
| `multiplicador_colocacao_05` | `double` | **Sim** | Multip. 5º prêmio | Prêmio da 5ª colocação |
| `limite_palpite` | `double` | Não | Limite Palpite | Limite de aposta por palpite |
| `limite_descarga` | `double` | Não | Limite Descarga | Limite de volume de apostas |
| `limite_aceite` | `double` | Não | Limite Aceite | Limite de aceitação |

> **Nota:** Os campos `multiplicadorColocacao04` e `multiplicadorColocacao05` estão presentes na Milhar Premiada mas **ausentes** nos módulos Quininha/Seninha/Lotinha (que usam apenas 01–03). O mapeamento Java usa os mesmos nomes de campo em `cad_modalidade`. 🟢

---

## Interface no Sistema Java (allsystem — referência)

### Colunas da lista

| Coluna | Dado | Notas |
|---|---|---|
| Modalidades | `intJogo.descricao` | Nome do tipo de jogo |
| Apresentação | `apresentacao` | |
| Valor Milhar Premiada | `multiplicador` | BRL |
| Multip. 01 | `multiplicadorColocacao01` | BRL |
| Multip. 02 | `multiplicadorColocacao02` | BRL |
| Multip. 03 | `multiplicadorColocacao03` | BRL |
| Multip. 04 | `multiplicadorColocacao04` | BRL |
| Multip. 05 | `multiplicadorColocacao05` | BRL |
| Limite Palpite | `limitePalpite` | BRL |
| Ativo | `ativo ? "Sim" : "Não"` | |
| Ações | Editar (inline) | Sem Delete |

### Formulário inline (aparece no topo ao clicar Editar)

| Campo | Tipo | Obrigatório | Comportamento |
|---|---|---|---|
| Milhar Premiada | `text` readonly | — | `intJogo.descricao` — não editável |
| Apresentação | `text` | **Sim** | |
| Ativo | `checkbox` | — | |
| Valor Milhar Premiada | `number` | **Sim** | `multiplicador` |
| Multip. 1º prêmio | `number` | **Sim** | `multiplicadorColocacao01` |
| Multip. 2º prêmio | `number` | **Sim** | `multiplicadorColocacao02` |
| Multip. 3º prêmio | `number` | **Sim** | `multiplicadorColocacao03` |
| Multip. 4º prêmio | `number` | **Sim** | `multiplicadorColocacao04` |
| Multip. 5º prêmio | `number` | **Sim** | `multiplicadorColocacao05` |
| Limite Palpite | `number` | Não | |
| Limite Descarga | `number` | Não | |
| Limite Aceite | `number` | Não | |

### Botões do formulário inline

| Botão | Condição | Ação |
|---|---|---|
| Cancelar | Sempre | `isEditing = false` — fecha o form sem salvar |
| Salvar | `form.valid && !isSaving` | `PUT api/milharpremiada` → recarrega lista |

---

## Fluxo Principal

```
1. Gestor acessa Milhar Premiada
2. GET api/milharpremiada → lista modalidades Milhar Premiada
3. Gestor clica "Editar" na linha desejada
4. Formulário inline aparece no topo (scroll para #page-heading)
5. Objeto clonado para edição — original na lista não é alterado até salvar
6. Gestor altera campos desejados
7. Clica Salvar:
   a. PUT api/milharpremiada com objeto editado
   b. ngOnInit() recarrega toda a lista
   c. isEditing = false (fecha o form)
8. (Alternativa) Clica Cancelar → isEditing = false
```

---

## Regras de Negócio

- **RN-MP-01** Somente edição — não há criação de novos registros via tela 🟢
- **RN-MP-02** Campos `multiplicador` e `multiplicadorColocacao01..05` são obrigatórios 🟢
- **RN-MP-03** `limitePalpite`, `limiteDescarga` e `limiteAceite` são opcionais 🟢
- **RN-MP-04** Ao clicar Editar, o objeto é clonado (`JSON.parse(JSON.stringify(mod))`) — alterações no form não afetam a linha da lista até que o save seja confirmado 🟢
- **RN-MP-05** Após salvar com sucesso, `ngOnInit()` recarrega a lista do servidor — a linha editada reflete os novos valores 🟢
- **RN-MP-06** `intJogo.descricao` é exibido como readonly — o tipo de jogo não pode ser alterado via tela 🟢
- **RN-MP-07** Não há auditoria em `register_log` neste módulo — lacuna em relação ao padrão do sistema 🔴
- **RN-MP-08** A filtragem por tipo de jogo Milhar Premiada é feita pelo backend em `GET api/milharpremiada` 🟡

---

## Comparação com módulos similares

| Aspecto | Milhar Premiada | Bilhetinho | Quininha Modalidade |
|---|---|---|---|
| Tabela | `cad_modalidade` | `cad_modalidade_bilhetinho` (PK composta) | `cad_modalidade` |
| Multiplicadores por colocação | 01..05 (na mesma linha) | 01..05 (uma linha por colocação) | 01..03 (na mesma linha) |
| Limite Palpite | Sim | Sim | Sim |
| Limite Descarga | Sim | Sim | Não |
| Limite Aceite | Sim | Sim | Não |
| register_log | Ausente 🔴 | Ausente 🔴 | Ausente 🔴 |
| Padrão UI | Lista + inline edit | Lista + inline edit | Lista + rota de edit |

---

## Dependências

| Componente | Relação |
|---|---|
| `cad_modalidade` | Tabela alvo — campos `multiplicador_colocacao_01..05` |
| `int_jogo` | Tipo de jogo Milhar Premiada (código inferido: `MBP`) 🟡 |
| `api/milharpremiada` | Endpoint específico — filtra e atualiza somente Milhar Premiada |

---

## Gap Analysis

| Item | Java (allsystem) | Zooloo (PHP) | Lacuna |
|---|---|---|---|
| Tela lista + inline edit | Angular presente | Ausente | 🔴 |
| `GET api/milharpremiada` | Presente | Ausente | 🔴 |
| `PUT api/milharpremiada` | Presente | Ausente | 🔴 |
| Campos `multiplicadorColocacao04` e `05` no form | Presente | Ausente em `Modalidade.php` | 🔴 |
| Auditoria register_log | Ausente no Java | Ausente | 🔴 (lacuna a corrigir) |

---

## Critérios de Aceitação (propostos)

```gherkin
# Happy path — visualizar lista
Dado que existem modalidades Milhar Premiada cadastradas
Quando gestor acessa MilharPremiadaList
Então tabela exibe: Modalidades, Apresentação, Valor (BRL), Multip.01-05 (BRL), Limite Palpite, Ativo

# Happy path — editar multiplicadores
Dado que modalidade "Milhar Premiada X" tem multiplicadorColocacao01=500.00
Quando gestor clica Editar, altera multiplicadorColocacao01=600.00 e salva
Então PUT api/milharpremiada enviado
E lista recarregada com novo valor R$ 600,00

# Cancelar edição
Quando gestor clica Editar e altera dados mas clica Cancelar
Então form fecha e lista mantém valores originais

# Sem botão Criar
Quando gestor acessa lista Milhar Premiada
Então não há botão "Novo" — somente botões "Edit" por linha

# Formulário inline — obrigatórios
Dado que gestor limpa campo "Valor Milhar Premiada"
Quando tenta salvar
Então botão Salvar permanece desabilitado (validação required)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Lista com colunas coretas (incl. multip. 04 e 05) | Must | Visualização da configuração |
| Edição inline (sem rota separada) | Must | Padrão do módulo no sistema legado |
| Campos `multiplicadorColocacao04` e `05` em `Modalidade.php` | Must | Atualmente ausentes no Active Record PHP |
| Auditoria register_log | Should | Consistência com outros módulos de configuração |
| Filtro por tipo de jogo Milhar Premiada no backend | Must | Sem filtro a lista mostraria todas as modalidades |

---

## Rastreabilidade de Código (Java — referência)

| Arquivo | Sistema |
|---|---|
| `allsystem/.../webapp/app/entities/milhar-premiada/milhar-premiada.component.html` | Java — lista + form inline |
| `allsystem/.../webapp/app/entities/milhar-premiada/milhar-premiada.component.ts` | Java — `loadEntityToEdit()`, `save()`, `cancelEditing()` |
| `allsystem/.../webapp/app/entities/milhar-premiada/milhar-premiada.service.ts` | Java — `GET/PUT api/milharpremiada` |

**Arquivos a criar no Zooloo:**

| Arquivo | Descrição |
|---|---|
| `app/control/milhar-premiada/MilharPremiadaList.php` | Lista + edição inline com 5 multiplicadores de colocação |

**Arquivo a atualizar no Zooloo:**

| Arquivo | Alteração necessária |
|---|---|
| `app/model/entities/Modalidade.php` | Adicionar `multiplicador_colocacao_04` e `multiplicador_colocacao_05` ao Active Record (atualmente mapeados apenas até 03) |
