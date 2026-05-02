# Permissões e Regras de Acesso - Zooloo

## Perfis de Usuário

O sistema Zooloo utiliza um sistema de permissões baseado em grupos para controlar o acesso às funcionalidades. Os principais perfis identificados são:

1. **Administrador (admin)**: Acesso total ao sistema
2. **Gerente (manager)**: Acesso administrativo às funcionalidades de sua área
3. **Vendedor (seller)**: Acesso limitado às funcionalidades de venda e consulta
4. **Coletor (collector)**: Acesso intermediário, podendo gerenciar vendedores e visualizar informações da área

## Matriz de Permissões

### Administrador (admin)
- Acesso total a todas as funcionalidades do sistema

### Gerente (manager)
- Acesso às funcionalidades administrativas de sua área
- Pode gerenciar vendedores e coletores da sua área
- Pode visualizar relatórios e dados de sua área

### Vendedor (seller)
- Acesso limitado às funcionalidades de registro de sorteios e bilhetes
- Pode registrar resultados de sorteios
- Pode visualizar relatórios de suas próprias vendas

### Coletor (collector)
- Acesso intermediário às funcionalidades de gerenciamento de vendedores
- Pode visualizar e gerenciar vendedores sob sua supervisão
- Pode acessar relatórios de desempenho da equipe

## Considerações

O sistema ainda não implementa de forma completa a sincronização entre o status do gerente e o status do usuário do sistema. Embora o TODO mencione que ao deixar um gerente inativo também se deve inativar o usuário do sistema, esta funcionalidade ainda não está implementada.

## Permissões Identificadas

### Regras de Negócio Implícitas

1. **Gerente (manager)**:
   - Pode acessar todas as funcionalidades administrativas
   - Pode gerenciar vendedores e coletores
   - Pode visualizar relatórios detalhados

2. **Coletor (collector)**:
   - Pode gerenciar vendedores sob sua supervisão
   - Pode visualizar relatórios da equipe
   - Pode gerenciar sorteios e resultados

3. **Vendedor (seller)**:
   - Acesso limitado às funcionalidades de registro de sorteios e bilhetes
   - Não tem acesso ao menu administrativo

4. **Administrador (admin)**:
   - Acesso total ao sistema
   - Pode gerenciar todas as áreas
   - Pode visualizar todos os relatórios

## Considerações Finais

O sistema de permissões do Zooloo segue um modelo baseado em perfis, onde cada perfil tem acesso a diferentes funcionalidades do sistema. A implementação atual ainda apresenta uma lacuna importante: a sincronização entre o status do gerente e o status do usuário do sistema ainda não está completamente implementada, conforme mencionado no TODO do código.

## Matriz de Permissões

| Perfil | Permissões | Descrição |
|-------|-------------|-----------|
| Administrador | Acesso total | Pode acessar todas as funcionalidades do sistema |
| Gerente | Acesso administrativo | Pode gerenciar vendedores e coletoors da sua área |
| Coletor | Acesso intermediário | Pode gerenciar vendedores sob supervisão |
| Vendedor | Acesso limitado | Acesso a funcionalidades de registro de sorteios e bilhetes |

## TODOs Identificados

1. A implementação da regra de negócio de inativação de gerente e usuário do sistema ainda não está completa.
2. A verificação do horário limite da extração antes de salvar resultados de sorteio ainda não está implementada.

## Conclusão

O sistema de permissões do Zooloo permite diferentes níveis de acesso baseados nos papéis de usuário. A implementação atual ainda apresenta uma lacuna importante que precisa ser corrigida: a sincronização entre o status do gerente e o status do usuário do sistema.