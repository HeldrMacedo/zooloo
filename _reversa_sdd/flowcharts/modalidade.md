# Fluxograma — Módulo Modalidade

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## ModalidadeForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar formulário]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{modalidade_id presente?}
    F -- sim --> G[Modalidade::load id]
    F -- não --> H[new Modalidade]
    H --> I[Calcular nova ordem: MAX+1]
    G --> J[Atribuir campos]
    I --> J
    J --> K[modalidade->store]
    K --> L[TTransaction::close]
    L --> M[TForm::sendData: repopular]
    M --> N[TToast sucesso]
    N --> Z
    E --> O{Exceção?}
    K --> O
    O -- sim --> P[TTransaction::rollback + TToast erro]
    P --> Z
```

## ModalidadeForm — onChangeJogo (Callback AJAX)

```mermaid
flowchart TD
    A([onChangeJogo: jogo_id selecionado]) --> B[TTransaction::open 'permission']
    B --> C[IntJogo::load jogo_id]
    C --> D{jogo->informar_valores_modalidade == 'S'?}
    D -- sim --> E[Habilitar campos de multiplicador_colocacao 1..5]
    D -- não --> F[Desabilitar campos de multiplicador_colocacao 1..5]
    E --> G[TForm::sendData: atualizar UI]
    F --> G
    G --> H[TTransaction::close]
    H --> I([Fim])
    B --> J{Exceção?}
    C --> J
    J -- sim --> K[TTransaction::rollback + TToast erro]
    K --> I
```

## ModalidadeList — Filtrar por Jogo

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros: descricao, jogo_id]
    B --> C[Construir TCriteria]
    C --> D{jogo_id filtrado?}
    D -- sim --> E[Adicionar TFilter: jogo_id = X]
    D -- não --> F[Sem filtro de jogo]
    E --> G[TTransaction::open 'permission']
    F --> G
    G --> H[Modalidade::getObjects critérios + JOIN IntJogo]
    H --> I[Renderizar DataGrid com descrição do jogo]
    I --> J[TTransaction::close]
    J --> K([Fim])
```

> **Regra de negócio:** Cada tipo de jogo (`int_jogo`) permite apenas uma modalidade. O combo de jogo no form exclui jogos já usados (`NOT IN cad_modalidade`) para registros novos.
> **Constantes de modalidade ID:** MILHAR_INSTANTANEA=22, MILHAR_MOTO_01=34, _02=35, _03=36 — usadas em BilheteRestService para regras especiais.
