# Fluxograma — Módulo Vendedor

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## VendedorForm — Salvar (criação de Vendedor + SystemUser)

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar formulário]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{vendedor_id presente?}
    F -- sim --> G[Vendedor::load vendedor_id]
    F -- não --> H[new Vendedor]
    G --> I[Atribuir campos do form]
    H --> I
    I --> J{login preenchido?}
    J -- sim --> K{Modo edição?}
    K -- não --> L[new SystemUser]
    K -- sim --> M[SystemUser::load usuario_id]
    L --> N[login, name, email='login@zooloo.com', active='1']
    M --> N
    N --> O[MD5 da senha ⚠️]
    O --> P[systemUser->store]
    P --> Q{Novo usuário?}
    Q -- sim --> R[new SystemGroup: VENDEDOR_GROUP_ID]
    R --> S[group->addUser systemUser]
    S --> T[vendedor->usuario_id = systemUser->id]
    Q -- não --> T
    J -- não --> T
    T --> U[vendedor->store]
    U --> V[TTransaction::close]
    V --> W[TToast sucesso]
    W --> Z
    E --> X{Exceção?}
    P --> X
    U --> X
    X -- sim --> Y[TTransaction::rollback + TToast erro]
    Y --> Z
```

## VendedorForm — onChangeArea (Callback AJAX)

```mermaid
flowchart TD
    A([onChangeArea: area_id selecionada]) --> B[TTransaction::open 'permission']
    B --> C["Gerente::getObjects WHERE area_id = {area_id}"]
    C --> D[Repopular combo de Gerente com opções filtradas]
    D --> E[TCombo::reload 'gerente_id']
    E --> F[TTransaction::close]
    F --> G([Fim])
```

## VendedorList — onReload com filtros de permissão

```mermaid
flowchart TD
    A([onReload]) --> B[Obter filtros do form]
    B --> C[checkUserPermissions]
    C --> D{Usuário é Gerente?}
    D -- sim --> E[Adicionar filtro: area_id = gerente.area_id]
    D -- não --> F[Sem restrição de área]
    E --> G[Construir TCriteria]
    F --> G
    G --> H[TTransaction::open 'permission']
    H --> I[Vendedor::getObjects critérios + JOIN Area + JOIN Gerente]
    I --> J[Renderizar DataGrid]
    J --> K[TTransaction::close]
    K --> L([Fim])
```

> **Padrão igual ao Gerente:** cria SystemUser + adiciona ao grupo VENDEDOR_GROUP_ID em uma única transação.
> **Email fabricado:** `login@zooloo.com` — não é um email real do vendedor.
> **Segurança:** Senha armazenada como MD5 (sem salt) — 🔴 vulnerabilidade conhecida.
