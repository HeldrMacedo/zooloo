# Fluxograma — Módulo Extracao

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## ExtracaoForm — Salvar

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar formulário]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[Obter dados do form]
    E --> F[Converter array semanas[] → campos individuais]
    F --> G{segunda in semanas?}
    G -- sim --> H1[segunda = 'S']
    G -- não --> H2[segunda = 'N']
    H1 --> I
    H2 --> I
    I{...mesma lógica para terça, quarta, quinta, sexta, sabado, domingo}
    I --> J[TTransaction::open 'permission']
    J --> K{extracao_id presente?}
    K -- sim --> L[Extracao::load extracao_id]
    K -- não --> M[new Extracao]
    L --> N[Atribuir todos os campos incluindo filtro_banca=1]
    M --> N
    N --> O[extracao->store]
    O --> P[TTransaction::close]
    P --> Q["$this->form->cleat() ⚠️TYPO: deveria ser clear()"]
    Q --> R[TToast sucesso]
    R --> Z
    J --> S{Exceção?}
    O --> S
    S -- sim --> T[TTransaction::rollback]
    T --> U[TToast erro]
    U --> Z
```

## ExtracaoForm — Conversão de Dias da Semana

```mermaid
flowchart TD
    A([Array semanas[] recebido do form]) --> B[Iterar lista de dias]
    B --> C{dia IN semanas?}
    C -- sim --> D["extracao->{dia} = 'S'"]
    C -- não --> E["extracao->{dia} = 'N'"]
    D --> F[Próximo dia]
    E --> F
    F --> G{Todos processados?}
    G -- não --> B
    G -- sim --> H([7 campos definidos: segunda, terca, quarta, quinta, sexta, sabado, domingo])
```

> **Nota:** O campo `filtro_banca` é fixado em `1` em todas as criações/edições. Propósito exato 🟡 INFERIDO: filtra apenas extrações desta banca.
> **Bug:** `$this->form->cleat()` — método não existe; deveria ser `clear()`.
