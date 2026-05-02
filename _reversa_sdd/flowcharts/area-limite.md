# Fluxograma — Módulo AreaLimite

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## AreaLimiteForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: area_id, modalidade_id, limite_valor obrigatórios]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{area_limite_id presente?}
    F -- sim --> G[AreaLimite::load id]
    F -- não --> H[new AreaLimite]
    G --> I[Atribuir: area_id, modalidade_id, limite_valor]
    H --> I
    I --> J[areaLimite->store]
    J --> K[TTransaction::close]
    K --> L[TToast sucesso]
    L --> Z
    E --> M{Exceção?}
    J --> M
    M -- sim --> N[TTransaction::rollback + TToast erro]
    N --> Z
```

## AreaLimiteList — Renderização com Área e Modalidade

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros: area_id, modalidade_id]
    B --> C[TTransaction::open 'permission']
    C --> D[JOIN cfg_area_limite + cad_area + cad_modalidade]
    D --> E[Filtros opcionais por area_id e/ou modalidade_id]
    E --> F[Renderizar DataGrid: Área | Modalidade | Limite]
    F --> G[TTransaction::close]
    G --> H([Fim])
```

> **Semântica:** O `limite_valor` define o valor máximo em reais que pode ser apostado por palpite em uma determinada combinação área+modalidade. Usado no BilheteRestService via COALESCE (limite da área ou limite global).
> **Relacionamento com BilheteRestService:** `COALESCE(cfg_area_limite.limite_valor, cfg_parametros.limite_global)` — o limite da área tem precedência.
