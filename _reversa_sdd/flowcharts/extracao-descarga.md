# Fluxograma — Módulo ExtracaoDescarga

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## ExtracaoDescargaForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: extracao_id, modalidade_id, limite obrigatórios]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{id presente?}
    F -- sim --> G[ExtracaoDescarga::load id]
    F -- não --> H[new ExtracaoDescarga]
    G --> I[Atribuir: extracao_id, modalidade_id, valor_descarga]
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

## ExtracaoDescargaList — Grid com Extração e Modalidade

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros: extracao_id, modalidade_id]
    B --> C[TTransaction::open 'permission']
    C --> D[JOIN cfg_extracao_descarga + cad_extracao + cad_modalidade]
    D --> E[Aplicar filtros opcionais]
    E --> F[Renderizar: Extração | Modalidade | Limite de Descarga]
    F --> G[TTransaction::close]
    G --> H([Fim])
```

> **Semântica (Descarga):** Limite máximo de apostas acumuladas em um número específico por sorteio. Protege a banca de risco financeiro excessivo em números "pesados". Quando o limite é atingido, novas apostas naquele número são recusadas ou redirecionadas.
> **Tabela:** `cfg_extracao_descarga` — chave composta (extracao_id + modalidade_id).
> **Integração:** Verificado em BilheteRestService no momento do registro do bilhete.
