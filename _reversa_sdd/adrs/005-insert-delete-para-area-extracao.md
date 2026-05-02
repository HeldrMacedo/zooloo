# ADR-005: Ativação de AreaExtracao por presença de registro (INSERT/DELETE) em vez de campo ativo

**Status:** Aceito
**Data:** 2025-09-21 (commit `2d5f0f0`)
**Confiança:** 🟢 CONFIRMADO

---

## Contexto

O sistema precisa controlar quais extrações estão ativas em cada área. Todos os outros módulos com ativação/desativação (Area, Gerente, Vendedor, Extracao) usam um campo `ativo` char(1) S/N.

## Decisão

Para `AreaExtracao` (`cfg_area_extracao`), a ativação é representada pela **presença ou ausência de um registro** na tabela, sem campo `ativo`:
- **Ativar:** INSERT de novo registro com `(area_id, extracao_id)`
- **Desativar:** DELETE do registro (hard delete)

O grid usa LEFT JOIN para mostrar todas as extrações e inferir o status pela presença/ausência de `area_extracao.id`.

## Consequências

- **Positivo:** A tabela `cfg_area_extracao` é enxuta — apenas registros ativos existem, facilitando queries de filtro.
- **Positivo:** JOIN simples para saber quais extrações estão ativas em uma área: `WHERE cfg_area_extracao.area_id = X`.
- **Negativo:** Inconsistente com o padrão S/N do restante do sistema — aumenta carga cognitiva.
- **Negativo:** Hard delete perde histórico de ativações — não é possível saber quando a extração foi desativada.
- **Negativo:** Sem soft delete, dados de auditoria são perdidos.
