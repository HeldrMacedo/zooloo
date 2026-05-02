# Regras de Negócio Identificadas

## Regras de Negócio do Domínio

### 1. Sistema de Bilhetes
- O sistema permite o registro de bilhetes de diferentes modalidades de jogo (Jogo do Bicho, Bilhetinho, Quininha, Seninha, Lotinha, etc.)
- Os bilhetes são registrados por vendedores e coletores em áreas específicas
- O sistema calcula automaticamente os prêmios com base nas regras de negócio do Jogo do Bicho

### 2. Grupos de Animais
- O sistema trabalha com um conjunto de 25 animais associados a grupos de números específicos
- Cada animal representa um grupo de 4 dezenas (ex: Avestruz = 01 a 04, Águia = 05 a 08, etc.)

## Regras de Negócio de Usuários e Permissões

### 1. Perfis de Usuário
- O sistema possui diferentes perfis de usuário: Administrador, Gerente, Coletor e Vendedor
- Cada perfil tem permissões específicas para acesso às funcionalidades

## Regras de Negócio de Validação

### 1. Validação de Resultados
- Deveria haver uma verificação do horário limite da extração antes de permitir salvar o resultado (identificada como TODO no CLAUDE.md)

## Regras de Negócio de Segurança

### 1. Senhas e Autenticação
- O sistema utiliza MD5 sem salt para armazenamento de senhas (identificada como uma prática insegura no CLAUDE.md)

## Regras de Negócio de Pagamentos

### 1. Comissões e Prêmios
- O sistema trabalha com comissões variáveis por área/modalidade
- O sistema calcula automaticamente os prêmios com base em regras de negócio específicas

## Regras de Negócio de Horários

### 1. Extrações e Sorteios
- O sistema trabalha com extrações que definem dias da semana e hora limite para apostas
- Os sorteios são agendados e têm situação "Aberto" (A) ou "Fechado" (F)

## Regras de Negócio de Status

### 1. Status de Usuários e Gerentes
- Ao deixar um gerente inativo, também deveria inativar o usuário do sistema (identificada como TODO no CLAUDE.md)

## Regras de Negócio de Código

### 1. Código Legado
- O código legado (allsystem) utiliza o mesmo banco de dados e regras de negócio