# Fluxograma — Módulo AreaCotacao

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## AreaCotacaoForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: area_id, extracao_id, modalidade_id, cotacao obrigatórios]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{area_cotacao_id presente?}
    F -- sim --> G[AreaCotacao::load id]
    F -- não --> H[new AreaCotacao]
    G --> I[Atribuir: area_id, extracao_id, modalidade_id, cotacao]
    H --> I
    I --> J[areaCotacao->store]
    J --> K[TTransaction::close]
    K --> L[TToast sucesso]
    L --> Z
    E --> M{Exceção?}
    J --> M
    M -- sim --> N[TTransaction::rollback + TToast erro]
    N --> Z
```

## AreaCotacaoList — Filtrar por Área e Extração

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros: area_id, extracao_id]
    B --> C[TTransaction::open 'permission']
    C --> D[JOIN cfg_area_cotacao + cad_area + cad_extracao + cad_modalidade]
    D --> E{Filtros aplicados?}
    E -- sim --> F[WHERE area_id AND/OR extracao_id]
    E -- não --> G[Sem filtro adicional]
    F --> H[Renderizar DataGrid]
    G --> H
    H --> I[TTransaction::close]
    I --> J([Fim])
```

> **Semântica:** A cotação é o multiplicador de premiação. Se um bilhete de R$1 acertar na Milhar com cotação 4000, o prêmio é R$4.000.
> **Relação:** cfg_area_cotacao tem chave composta (area_id + extracao_id + modalidade_id). Overrides podem existir por área específica.
