# Decisão de Arquitetura: Sincronização de Status entre Gerente e Usuário do Sistema

> 🔴 LACUNA | Confiança: Baixa

## Descrição
O sistema Zooloo deveria sincronizar automaticamente o status de inatividade entre o gerente e o usuário do sistema, mas esta funcionalidade ainda não está completamente implementada.

## Contexto
O arquivo CLAUDE.md menciona que ao deixar um gerente inativo, também deveria inativar o usuário do sistema correspondente. Esta funcionalidade ainda não está implementada.

## Decisão
A implementação desta sincronização ainda não foi feita, representando uma lacuna na implementação do sistema.

## Consequências
1. **Inconsistência de dados**: A falta desta sincronização pode levar a inconsistências entre o status do gerente e o status do usuário do sistema.
2. **Problemas de segurança**: Um gerente inativo ainda pode ter um usuário ativo no sistema, o que pode levar a problemas de segurança.

## Status
Lacuna identificada, mas ainda não implementada.

## Recomendação
Implementar a sincronização automática entre o status do gerente e o status do usuário do sistema.