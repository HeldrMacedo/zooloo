# Decisão de Arquitetura: Estrutura de Pastas do Projeto

> 🟡 INFERIDO | Confiança: Média

## Descrição
O sistema Zooloo utiliza uma estrutura de pastas específica para organizar o código de forma modular, separando controladores, modelos e serviços em diretórios distintos.

## Alternativas Consideradas
1. **Estrutura modular**: A estrutura atual do projeto segue um padrão MVC (Model-View-Controller) com separação clara entre as camadas.
2. **Estrutura baseada em funcionalidades**: Uma abordagem baseada em funcionalidades do domínio, onde cada funcionalidade teria sua própria pasta com controladores, modelos e serviços relacionados.

## Decisão
A decisão foi tomada de manter a estrutura MVC tradicional com separação de responsabilidades, o que facilita a manutenção e evolução do sistema.

## Consequências
1. **Manutenção**: A estrutura atual permite fácil manutenção e evolução do sistema.
2. **Separação de responsabilidades**: A clara separação de responsabilidades entre as camadas facilita o desenvolvimento e manutenção.

## Status
A estrutura atual é baseada em uma arquitetura MVC com separação de responsabilidades clara entre as camadas.

## Recomendação
Manter a estrutura atual, pois ela é simples e eficaz para o tamanho do projeto.