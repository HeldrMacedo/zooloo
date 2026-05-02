# Fluxograma — Módulo Parametros

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## ParametrosForm — Salvar (singleton)

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar: nome_banca obrigatório]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F[Parametros::load 1]
    F --> G{Registro ID=1 existe?}
    G -- não --> H[new Parametros com id=1]
    G -- sim --> I[Usar registro carregado]
    H --> J[Atribuir todos os campos do form]
    I --> J
    J --> K[parametros->store]
    K --> L[TTransaction::close]
    L --> M[TToast sucesso]
    M --> Z
    E --> N{Exceção?}
    K --> N
    N -- sim --> O[TTransaction::rollback + TToast erro]
    O --> Z
```

## ParametrosForm — onEdit (carregamento automático)

```mermaid
flowchart TD
    A([Tela aberta]) --> B[TTransaction::open 'permission']
    B --> C[Parametros::load 1]
    C --> D{Registro existe?}
    D -- sim --> E[TForm::sendData: popular formulário com valores atuais]
    D -- não --> F[Form em branco com defaults]
    E --> G[TTransaction::close]
    F --> G
    G --> H([Tela pronta para edição])
```

> **Padrão Singleton:** A tabela `cfg_parametros` sempre tem exatamente um registro com ID=1. Nunca há listagem nem criação de múltiplos registros.
> **Campos chave:** `nome_banca` (nome do estabelecimento), `limite_global` (fallback de limite de apostas quando não há cfg_area_limite), flags de features habilitadas (permite_bilhetinho, permite_quininha, etc.).
> **Uso em BilheteRestService:** `cfg_parametros.limite_global` é usado como fallback via COALESCE quando não existe cfg_area_limite para a combinação área+modalidade.
