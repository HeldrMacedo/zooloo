# ADR-004: Cálculo de prêmios via triggers PostgreSQL (não no PHP)

**Status:** Aceito
**Data:** 2025-09-30 (commit `f3a3ce1` — adicionado com MovSorteio)
**Confiança:** 🟢 CONFIRMADO (documentado em CLAUDE.md e referenciado no código)

---

## Contexto

Ao registrar o resultado de um sorteio, o sistema precisa calcular quais bilhetes foram premiados, aplicar cotações, e registrar valores de prêmio. Esta lógica é complexa e envolve múltiplas tabelas (`mov_jb`, `mov_jb_sorteio`, `mov_jb_sort_palpite`, `cfg_area_cotacao`, etc.).

## Decisão

A lógica de cálculo de prêmios é delegada inteiramente ao banco de dados PostgreSQL através de **triggers**:

- `trg_mv_sorteio_verifica_ganhadores` — JB padrão
- `trg_mv_sorteio_verifica_ganhadores_lotinha` — Lotinha
- `trg_mv_sorteio_verifica_ganhadores_qui_sen` — Quininha/Seninha

O PHP apenas atualiza `situacao='F'` no `mov_sorteio` e as triggers disparam automaticamente.

## Consequências

- **Positivo:** Lógica de negócio central (cálculo de prêmios) em um único lugar — o banco.
- **Positivo:** Qualquer cliente (PHP web, REST API, scripts CLI) se beneficia automaticamente.
- **Positivo:** Performance — cálculo em SQL puro, sem round-trips PHP↔BD.
- **Negativo:** Lógica de negócio não está visível no código PHP — requer análise direta do banco.
- **Negativo:** Mais difícil de testar unitariamente — exige banco de dados real com triggers.
- **🔴 Lacuna:** O código das triggers não está nos arquivos SQL analisados (apenas referências nos comentários do CLAUDE.md). Precisam ser extraídos diretamente do banco `jb`.
