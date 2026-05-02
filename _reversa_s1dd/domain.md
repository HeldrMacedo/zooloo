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

## Regais de Negócio de Status

### 1. Status de Usuários e Gerentes
- Ao deixar um gerente inativo, também deveria inativar o usuário do sistema (identificada como TODO no CLAUDE.md)

## Estados e Transições

### Estados de Sorteio
- **A** - Aberto: Sorteio disponível para apostas
- **F** - Fechado: Sorteio encerrado, com resultados já registrados

### Estados de Usuário/Gerente/Área
- **S** - Ativo
- **N** - Inativo

## Componentes do Sistema

### Controladores (control)
Telas e formulários do sistema

### Modelos (model)
Active Records do negócio e do sistema Adianti

### Serviços (service)
Serviços de autenticação, REST, sistema e outros

### Visão (view)
Componentes visuais do sistema

## Tabelas do Banco de D�os

### Prefixos de Tabela
- cad_* : Cadastro (dados mestre)
- cfg_* : Configuração
- mov_* : Movimento (transações)
- int_* : Interno/sistema
- data_* : Dados auxiliares

## Funcionalidades do Sistema

### Cadastros
- Área
- Gerente
- Extração
- Modalidade
- Vendedor

### Configurações
- Área Extração
- Área Cotação
- Área Limite
- Área Comissão Modalidade
- Palpite Cotado
- Extração Descarga
- Parâmetros

### Operacional
- Resultado

## Tecnologias e Frameworks

### Adianti Framework
Framework PHP utilizado no desenvolvimento do sistema

### PostgreSQL
Banco de dados utilizado pelo sistema

### DomPDF
Biblioteca para geração de PDFs

### Bootstrap 5
Tema utilizado no sistema

## Considerações Finais

O sistema Zooloo apresenta um conjunto complexo de regras de negócio relacionadas ao gerenciamento de apostas de jogo do bicho, com funcionalidades de cadastro, configuração e operacionais. A implementação atual ainda apresenta algumas lacunas identificadas, como a sincronização entre o status do gerente e o status do usuário do sistema, e a verificação do horário limite da extração antes de salvar resultados.