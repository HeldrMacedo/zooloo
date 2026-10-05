# Nota de qualidade por área

A (sólido, testado, documentado) a D (frágil, sem teste ou sem versionamento).
Última revisão: 2026-10-05. Detalhe de paridade: `docs/references/comparativo-allsystem-zooloo.md`.

| Área | Nota | Por quê | Próximo passo |
|---|---|---|---|
| Auth REST / JWT | A- | Rotação, replay detection, rate limit, testes | Alinhar TTL; segredos em env |
| REST de aposta (Bilhete/Sorteio/Modalidade) | B | Teste de contrato e validação | Registrar payloads no api-contract |
| Cadastros | B- | Esqueleto completo; vários "parcial" na paridade | Fechar gaps do comparativo §1 |
| Resultado / premiação | C | Lógica em triggers não versionadas | Versionar schema; testes de integração |
| Descarrego | D | Gap crítico na paridade | Exec-plan próprio |
| Bilhetinho | D | Stack não implementado | Exec-plan próprio |
| Relatórios | C+ | Telas existem; correções recentes | Testes de totais |
| Infra / CI | D | Sem CI, sem lint, segredos no código | Ver tech-debt "Crítico" |
| Base de conhecimento | B | Reorganizada em 2026-10-05; `docs:check` | Agente de doc-gardening |
