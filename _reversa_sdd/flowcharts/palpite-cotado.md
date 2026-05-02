# Fluxograma — Módulo PalpiteCotado

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## PalpiteCotadoForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: modalidade_id, palpite, cotacao obrigatórios]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{id presente?}
    F -- sim --> G[PalpiteCotado::load id]
    F -- não --> H[new PalpiteCotado]
    G --> I[Atribuir: modalidade_id, palpite, cotacao]
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

## PalpiteCotadoList — Grid com Modalidade

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros: modalidade_id, palpite]
    B --> C[TTransaction::open 'permission']
    C --> D[JOIN cfg_palpite_cotado + cad_modalidade]
    D --> E[Aplicar filtros opcionais]
    E --> F[Renderizar: Modalidade | Palpite | Cotação Especial]
    F --> G[TTransaction::close]
    G --> H([Fim])
```

## Integração com ModalidadeRestService

```mermaid
flowchart TD
    A([disponiveis endpoint chamado]) --> B[Query principal de modalidades]
    B --> C[Para cada modalidade retornada:]
    C --> D["SELECT * FROM cfg_palpite_cotado WHERE modalidade_id = X"]
    D --> E{Existem palpites cotados?}
    E -- sim --> F[Injetar array palpites_cotados na resposta da modalidade]
    E -- não --> G[palpites_cotados = array vazio]
    F --> H[Retornar modalidade com cotações especiais]
    G --> H
    H --> I([App móvel recebe e aplica cotações diferenciadas])
```

> **Semântica:** Permite definir cotação especial para números específicos — por exemplo, "bicho boi (palpite 01) paga 10x" enquanto o padrão é 6x. Usado para atrair apostas em números "leves".
> **Tabela:** `cfg_palpite_cotado` — combinação (modalidade_id + palpite).
