# Decisão de Arquitetura: Uso de MD5 sem Salt para Senhas

> 🟡 INFERIDO | Confiança: Média-Baixa

## Descrição
O sistema Zooloo utiliza MD5 sem salt para armazenamento de senhas, o que é uma prática insegura. Esta decisão foi questionada e deve ser revista.

## Alternativas Consideradas
1. **Utilizar algoritmos de hash mais seguros com salt**: Implementar bcrypt, scrypt ou Argon2 para hash de senhas.
2. **Implementar um sistema de hash com salt**: Utilizar mecanismos mais seguros para armazenamento de senhas.

## Decisão
A decisão foi tomada de utilizar MD5 sem salt para compatibilidade com o legado, mas esta implementação foi identificada como uma lacuna de segurança que deve ser abordada.

## Consequências
1. **Baixa segurança no armazenamento de senhas**: O uso de MD5 sem salt é inseguro e deve ser evitado em sistemas modernos.
2. **Necessidade de revisão**: Esta prática de hashing insegura deve ser revista e atualizada.

## Status
A implementação atual utiliza MD5 sem salt, que é insegura e deve ser substituía no futuro.

## Recomendação
Substituir o uso de MD5 sem salt por um algoritmo de hash mais seguro com salt, como bcrypt, scrypt ou Argon2.

## Referências
1. OWASP Password Storage Cheat Sheet
2. NIST Password Guidelines
3. RFC 2104 - HMAC
4. RFC 2898 - PKCS #5: Password-Based Cryptography Specification

## Considerações de Segurança
O uso de MD5 sem salt é inseguro e deve ser evitado em sistemas modernos. O uso de algoritmos mais seguros como bcrypt, scrypt ou Argon2 é recomendado.