# Planos de execução

Todo trabalho com mais de um passo tem um plano versionado aqui. O agente lê o plano antes
de codar e registra o progresso nele.

```
active/      em andamento (AAAA-MM-DD-assunto.md)
completed/   concluídos — histórico
tech-debt.md dívida técnica e TODOs conhecidos
```

## Formato mínimo de um plano

```markdown
---
created: AAAA-MM-DD
status: em-andamento | concluido
branch: nome-da-branch
---
# Título
## Objetivo
## Critérios de aceite
- [ ] ...
- [ ] composer check verde
## Passos
## Log de progresso
```

Feature que envolve o app: o plano nasce **aqui** (contrato primeiro) e o plano do app em
`../app-zooloo/docs/exec-plans/active/` aponta para ele.

## Ativos

Nenhum registrado. Backlog de paridade: `docs/references/comparativo-allsystem-zooloo.md` §8.
