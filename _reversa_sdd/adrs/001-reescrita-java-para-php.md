# ADR-001: Reescrita do sistema Java/JHipster para PHP/Adianti

**Status:** Aceito
**Data:** 2025-06-25 (primeiro commit do projeto)
**Confiança:** 🟢 CONFIRMADO (evidenciado pelo primeiro commit e CLAUDE.md)

---

## Contexto

O sistema original `allsystem` foi desenvolvido em Java com Spring Boot e JHipster. O banco de dados PostgreSQL `jb` já existia com o schema completo de negócio. A equipe necessitava de uma interface web administrativa mais simples de manter e evoluir, sem a complexidade do ecossistema Java/Maven/JHipster.

## Decisão

Reescrever o front-end e back-end em PHP usando o **Adianti Framework 8.1**, mantendo o mesmo banco de dados PostgreSQL (`jb`) e schema de negócio intactos. O sistema PHP usa um banco separado (`applications`) para dados do sistema (usuários, permissões, logs), enquanto o banco `jb` mantém os dados de domínio.

## Consequências

- **Positivo:** Desenvolvimento mais rápido de telas CRUD com Adianti (padrão TPage/TRecord).
- **Positivo:** Equipe PHP pode manter o sistema sem conhecimento de Java/Spring.
- **Positivo:** Banco de dados legado preservado — sem migração de dados.
- **Negativo:** Dois bancos de dados (`applications` + `jb`) exigem configuração de duas conexões.
- **Negativo:** Schema do banco (tabelas de negócio) não está nos arquivos de migração do projeto PHP — está no banco legado.
- **Negativo:** Sistema legado Java (`allsystem/`) precisa ser mantido como referência de regras de negócio.
