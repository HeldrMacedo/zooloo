# Fluxograma — Módulo AreaComissaoModalidade

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## AreaComissaoModalidadeForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: area_id, modalidade_id, percentual obrigatórios]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{id presente?}
    F -- sim --> G[AreaComissaoModalidade::load id]
    F -- não --> H[new AreaComissaoModalidade]
    G --> I[Atribuir: area_id, modalidade_id, percentual_comissao]
    H --> I
    I --> J[registro->store]
    J --> K[TTransaction::close]
    K --> L[TToast sucesso]
    L --> Z
    E --> M{Exceção?}
    J --> M
    M -- sim --> N[TTransaction::rollback + TToast erro]
    N --> Z
```

## AreaComissaoModalidadeList — Grid com Área e Modalidade

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros: area_id, modalidade_id]
    B --> C[TTransaction::open 'permission']
    C --> D[JOIN cfg_area_comissao_modalidade + cad_area + cad_modalidade]
    D --> E[Aplicar filtros opcionais]
    E --> F[Renderizar: Área | Modalidade | % Comissão]
    F --> G[TTransaction::close]
    G --> H([Fim])
```

> **Semântica:** Define o percentual de comissão do vendedor sobre apostas de uma modalidade específica em uma área. Tabela de configuração fina — permite distinção de comissão por tipo de jogo e zona geográfica.
> **Tabela:** `cfg_area_comissao_modalidade` — chave composta (area_id + modalidade_id).
