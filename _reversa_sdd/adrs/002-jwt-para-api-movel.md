# ADR-002: JWT HS256 com TTL de 1 hora para autenticação da API móvel

**Status:** Aceito
**Data:** 2026-04-29 (commit `2a8199f`)
**Confiança:** 🟢 CONFIRMADO

---

## Contexto

O sistema precisava expor uma API REST para um aplicativo móvel React Native. A autenticação precisava ser stateless, suportar múltiplos terminais simultâneos (um por vendedor) e carregar informações de identidade (terminal_id, area_id) para evitar queries adicionais em cada request.

## Decisão

Usar **JWT (JSON Web Token) com algoritmo HS256** e TTL de 1 hora. O payload do token inclui `user_id`, `terminal_id` e `area_id`. A chave de assinatura é `APPLICATION_NAME + seed` (configurável em `app/config/application.php`).

Adicionalmente, uma **API key estática** (`zooloo_api_key_2025`) como mecanismo alternativo via Basic Auth para integração de sistemas.

## Consequências

- **Positivo:** Stateless — sem estado de sessão no servidor para a API móvel.
- **Positivo:** `area_id` e `terminal_id` embutidos no token evitam queries de lookup na maioria dos endpoints.
- **Positivo:** Suporte a refresh de token sem reautenticação.
- **Negativo:** TTL de 1h é relativamente curto — vendedores que ficam com o app aberto precisam renovar token.
- **Negativo:** API key estática `zooloo_api_key_2025` é hard-coded — risco de exposição em repositório.
- **🔴 Risco:** Chave JWT derivada de `APPLICATION_NAME + seed` — se a seed for fraca ou previsível, tokens podem ser forjados.
