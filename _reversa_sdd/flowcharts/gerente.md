# Fluxograma — Módulo Gerente

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## GerenteForm — Salvar (criação de Gerente + SystemUser)

```mermaid
flowchart TD
    A([onSave chamado]) --> B[Validar formulário]
    B --> C{Validação OK?}
    C -- não --> D[Exibir erros de validação]
    D --> Z([Fim])
    C -- sim --> E[TTransaction::open 'permission']
    E --> F{coletor_id presente?}
    F -- sim --> G[Gerente::load coletor_id]
    F -- não --> H[new Gerente]
    G --> I[Atribuir campos do formulário]
    H --> I
    I --> J{login preenchido?}
    J -- sim --> K{Modo edição?}
    K -- não --> L[new SystemUser]
    K -- sim --> M[SystemUser::load usuario_id]
    L --> N[Definir login, name, email, active]
    M --> N
    N --> O[MD5 da senha ⚠️]
    O --> P[systemUser->store]
    P --> Q{Novo usuário?}
    Q -- sim --> R[new SystemGroup: GERENTE_GROUP_ID]
    R --> S[group->addUser systemUser]
    S --> T[gerente->usuario_id = systemUser->id]
    Q -- não --> T
    J -- não --> T
    T --> U[gerente->store]
    U --> V[TTransaction::close]
    V --> W["TForm::sendData('form_ferente',...) ⚠️TYPO"]
    W --> X[TToast sucesso]
    X --> Z
    E --> Y{Exceção?}
    P --> Y
    U --> Y
    Y -- sim --> AA[TTransaction::rollback]
    AA --> AB[TToast erro]
    AB --> Z
```

## GerenteList — Toggle Ativo (Gerente + SystemUser)

```mermaid
flowchart TD
    A([onTurnOnOff chamado]) --> B[TTransaction::open 'permission']
    B --> C[Gerente::load id]
    C --> D{ativo == 'S'?}
    D -- sim --> E[gerente->ativo = 'N']
    D -- não --> F[gerente->ativo = 'S']
    E --> G[gerente->store]
    F --> G
    G --> H{usuario_id definido?}
    H -- sim --> I[SystemUser::load usuario_id]
    I --> J{ativo foi 'S'?}
    J -- sim --> K[systemUser->active = '0']
    J -- não --> L[systemUser->active = '1']
    K --> M[systemUser->store]
    L --> M
    H -- não --> N[TTransaction::close]
    M --> N
    N --> O[onReload]
    O --> P([Fim])
    B --> Q{Exceção?}
    G --> Q
    M --> Q
    Q -- sim --> R[TTransaction::rollback + TToast erro]
    R --> P
```

> **Nota:** ⚠️ Bug confirmado em GerenteForm::onSave — `TForm::sendData('form_ferente'...)` deveria ser `'form_gerente'`.
> **TODO documentado:** Ao inativar Gerente, inativar o SystemUser correspondente (parcialmente implementado no List, mas não no Form).
