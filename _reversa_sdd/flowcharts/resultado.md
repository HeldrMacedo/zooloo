# Fluxograma — Módulo Resultado

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## ResultadoForm — Salvar Resultado

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: sorteio_id, numeros_sorteados obrigatórios]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F[MovSorteio::load sorteio_id]
    F --> G{sorteio.situacao == 'A'?}
    G -- não --> H[Erro: Sorteio não está Aberto]
    H --> I[TTransaction::rollback]
    I --> Z
    G -- sim --> J[Atribuir numeros_sorteados ao sorteio]
    J --> K{Confirmar fechamento?}
    K -- não --> L[sorteio->store — salva rascunho]
    K -- sim --> M[sorteio->situacao = 'F']
    M --> N[sorteio->store]
    N --> O["Trigger BD: trg_mv_sorteio_verifica_ganhadores executa automaticamente"]
    L --> P[TTransaction::close]
    O --> P
    P --> Q[TToast sucesso]
    Q --> Z
    E --> R{Exceção?}
    F --> R
    L --> R
    N --> R
    R -- sim --> S[TTransaction::rollback + TToast erro]
    S --> Z
```

## ResultadoForm — Cálculo de Grupo (JavaScript + PHP)

```mermaid
flowchart TD
    A([Usuário digita número no campo prêmio]) --> B[JS: chkGrupoDescricao chamado]
    B --> C[Obter últimos 4 dígitos: milhar]
    C --> D[dezmilhar = últimos 2 dígitos]
    D --> E{dezmilhar == 00?}
    E -- sim --> F[dezmilhar = 100]
    E -- não --> G[grupo = ceil dezmilhar / 4]
    F --> G
    G --> H[Lookup: array 25 animais por número de grupo]
    H --> I[Exibir descrição do animal no campo de texto]
    I --> J([UI atualizada])
```

```mermaid
flowchart TD
    A([PHP: calcularGrupoDescricao numeros]) --> B[Iterar até 10 prêmios]
    B --> C[Para cada número: extrair milhar]
    C --> D[dezmilhar = milhar mod 100]
    D --> E{dezmilhar == 0?}
    E -- sim --> F[dezmilhar = 100]
    E -- não --> G[grupo = ceil dezmilhar / 4]
    F --> G
    G --> H[grupos_animais array 1..25]
    H --> I[Retornar array com número + grupo + animal]
    I --> J([Resultado estruturado com grupos])
```

## ResultadoList — Filtrar Sorteios por Data/Extração

```mermaid
flowchart TD
    A([onReload]) --> B[Filtros: data_sorteio, extracao_id, situacao]
    B --> C[TTransaction::open 'permission']
    C --> D[JOIN mov_sorteio + cad_extracao]
    D --> E[Filtros opcionais]
    E --> F[Ordenar: data DESC + hora_limite DESC]
    F --> G[Renderizar DataGrid: Data | Extração | Situação | Números]
    G --> H[Botão Editar só para situacao='A']
    H --> I[TTransaction::close]
    I --> J([Fim])
```

> **Trigger automático:** Ao setar `situacao='F'`, o banco executa `trg_mv_sorteio_verifica_ganhadores` que calcula todos os bilhetes premiados. O PHP não precisa fazer essa lógica.
> **Múltiplas triggers:** Uma para JB padrão, uma para Lotinha, uma para Quininha/Seninha.
> **25 grupos Jogo do Bicho:** Avestruz(1), Borboleta(2), ..., Vaca(25). Algoritmo: `ceil(dezmilhar/4)`, onde `dezmilhar = últimos 2 dígitos` e `00 → 100`.
