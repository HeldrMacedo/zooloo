# ADR-006: Criação dupla de entidade (SystemUser + Gerente/Vendedor) em uma transação

**Status:** Aceito
**Data:** 2025-09-01 (commit `f0b0032` — GerenteForm) / 2025-09-21 (VendedorForm)
**Confiança:** 🟢 CONFIRMADO

---

## Contexto

Gerentes e Vendedores precisam de dois tipos de identidade: uma identidade de domínio (para o sistema de loteria, com área, permissões de cancelamento, etc.) e uma identidade de sistema (para login no Adianti/REST API, com login, senha, grupo).

## Decisão

Ao criar um Gerente ou Vendedor, o sistema cria **dois registros em uma única TTransaction**:

1. `SystemUser` (tabela `system_users`) — identidade de acesso ao sistema
2. `Gerente`/`Vendedor` (tabela `cad_coletor`/`cad_vendedor`) — identidade de domínio

O `SystemUser` é adicionado ao grupo correspondente (`GERENTE_GROUP_ID=4` ou `VENDEDOR_GROUP_ID=5`). O `usuario_id` é gravado na entidade de domínio para vinculação.

## Consequências

- **Positivo:** Transação atômica — ou ambas as entidades são criadas, ou nenhuma.
- **Positivo:** Simples para o operador — um formulário cria tudo necessário.
- **Negativo:** Acoplamento entre dois domínios distintos (sistema e negócio) no mesmo controller.
- **Negativo:** Inativar Gerente requer lembrar de inativar também o SystemUser (bug documentado: `form_ferente` typo pode fazer o sendData falhar silenciosamente).
- **🔴 Bug:** `TForm::sendData('form_ferente', ...)` — typo em GerenteForm que impede a atualização correta do formulário após salvar.
- **🔴 Risco:** Se o Vendedor for inativado no cadastro, o SystemUser pode permanecer ativo no banco (comportamento não verificado).
