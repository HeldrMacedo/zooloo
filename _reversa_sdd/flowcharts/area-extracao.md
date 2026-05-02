# Fluxograma — Módulo AreaExtracao

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## AreaExtracaoList — Toggle de Status (Ativar/Desativar)

```mermaid
flowchart TD
    A([onToggleStatus chamado com area_id + extracao_id]) --> B[TTransaction::open 'permission']
    B --> C{Registro existe em cfg_area_extracao?}
    C -- não: inativo --> D[new AreaExtracao]
    D --> E[areEx->area_id = area_id]
    E --> F[areEx->extracao_id = extracao_id]
    F --> G[areEx->store — ATIVA a extração para a área]
    C -- sim: ativo --> H["AreaExtracao::load id"]
    H --> I["areEx->delete() — HARD DELETE ⚠️"]
    G --> J[TTransaction::close]
    I --> J
    J --> K[onReload]
    K --> L([Fim])
    B --> M{Exceção?}
    G --> M
    I --> M
    M -- sim --> N[TTransaction::rollback + TToast erro]
    N --> L
```

## AreaExtracaoList — Carregar Grid (SQL complexo)

```mermaid
flowchart TD
    A([onReload chamado]) --> B[Obter filtro: area_id selecionada]
    B --> C[checkUserPermissions]
    C --> D{Usuário é Gerente?}
    D -- sim --> E[Forçar area_id = gerente.area_id]
    D -- não --> F[Usar area_id do filtro]
    E --> G[TTransaction::open 'permission']
    F --> G
    G --> H["SQL: SELECT cad_extracao.*, cfg_area_extracao.id as status_id FROM cad_extracao LEFT JOIN cfg_area_extracao ON (area_id + extracao_id)"]
    H --> I[Para cada extração: ativa se status_id NOT NULL]
    I --> J[Renderizar toggle button por extração]
    J --> K[TTransaction::close]
    K --> L([Fim])
```

> **Padrão de ativação:** Ao contrário de outros módulos (que usam campo `ativo`), AreaExtracao usa presença/ausência de registro como estado — `INSERT` para ativar, `DELETE` para desativar.
> **Isolamento de Gerente:** Gerentes só veem/editam extrações da própria área.
