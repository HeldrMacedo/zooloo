# Decisão de Arquitetura: Sistema de Grupos e Permissões de Usuários

> 🟡 INFERIDO | Confiança: Média

## Descrição
O sistema Zooloo utiliza um sistema de grupos e permissões baseado no Adianti Framework para controlar o acesso às funcionalidades do sistema. Os principais grupos identificados são: Administrador, Gerente, Coletor e Vendedor.

## Contexto
O sistema de grupos e permissões é fundamental para controlar o acesso às funcionalidades do sistema, garantindo que cada tipo de usuário tenha acesso apenas às funcionalidades necessárias para seu perfil.

## Decisão
Foi decidido implementar um sistema de grupos e permissões baseado no Adianti Framework, onde cada grupo tem acesso a diferentes funcionalidades do sistema.

## Consequências
1. **Segurança**: O sistema de grupos e permissões aumenta a segurança do sistema.
2. **Controle de acesso**: Permite controlar o acesso às funcionalidades do sistema de acordo com o perfil do usuário.
3. **Manutenção**: A implementação de grupos e permissões facilita a manutenção do sistema.

## Status
Implementado e em produção.

## Recomendação
Manter e evoluir o sistema de grupos e permissões conforme necessário para suportar novas funcionalidades do sistema.