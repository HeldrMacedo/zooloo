# Decisão de Arquitetura: Implementação de REST API para Aplicativo Móvel

> 🟢 CONFIRMADO | Confiança: Alta

## Descrição
Foi implementada uma REST API completa para suportar um aplicativo móvel de venda de bilhetes, com autenticação JWT, registro de terminais, e endpoints para todas as operações necessárias.

## Contexto
O sistema Zooloo precisava expandir suas funcionalidades para suportar vendas através de um aplicativo móvel, o que demandou a criação de uma API REST completa com endpoints para todas as operações necessárias.

## Decisão
Foi decidido implementar uma API REST completa com os seguintes endpoints:
- Autenticação (login, refresh, logout)
- Perfil do vendedor (me)
- Registro de terminais (registrar)
- Listagem de sorteios abertos (abertos)
- Listagem de modalidades disponíveis (disponiveis)
- Registro de bilhetes (registrar)
- Cancelamento de bilhetes (cancelar)
- Detalhes de bilhetes (detalhe)
- Listagem de bilhetes (lista)
- Resultados recentes (recentes)
- Resumo do caixa (resumo)

## Consequências
1. **Expansão de funcionalidades**: O sistema agora suporta vendas através de aplicativo móvel.
2. **Aumento da complexidade**: A implementação da API REST adicionou complexidade ao sistema.
3. **Segurança**: A implementação de autenticação JWT e registro de terminais aumentou a segurança do sistema.

## Status
Implementada e em produção.

## Recomendação
Manter e evoluir a API REST conforme necessário para suportar novas funcionalidades do aplicativo móvel.