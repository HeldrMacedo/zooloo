# Schema do banco (tabelas de negócio)

> O schema completo e as triggers **ainda não estão versionados** neste repo. Até lá, a fonte
> é o banco vivo: `docker exec applications_db psql -U postgres -d applications`.
> Alvo: `docs/generated/schema.sql` gerado com `pg_dump --schema-only` (ver tech-debt).
> Visão mais detalhada (do lado do app): `../app-zooloo/docs/references/arquitetura-dados.md`.

## Prefixos

| Prefixo | Categoria |
|---|---|
| `cad_*` | Cadastro (dados mestre) |
| `cfg_*` | Configuração |
| `mov_*` | Movimento (transações) |
| `int_*` | Interno/sistema |
| `data_*` | Dados auxiliares |
| `mob_*` | Suporte ao app mobile (`mob_auth_token`) |
| `system_*` | Tabelas do Adianti (usuários, grupos, permissões) |

## Tabelas principais

**Cadastro:**
- `cad_area` — áreas (area_id, descricao, complemento, ativo)
- `cad_vendedor` — vendedores (área + coletor, limites e permissões operacionais do app)
- `cad_coletor` — coletores/gerentes (área, pode ter acesso_web)
- `cad_extracao` — extrações com dias da semana, hora_limite, premiacao_maxima
- `cad_modalidade` — modalidades (vinculadas a `int_jogo`, com multiplicadores)
- `cad_terminal` — terminais de venda (ver `docs/design-docs/terminal-binding.md`)

**Configuração:**
- `cfg_area_extracao` — extrações ativas por área
- `cfg_area_cotacao` — multiplicador de premiação por área/extração/modalidade
- `cfg_area_limite` — limite de aposta por área/modalidade
- `cfg_area_comissao_modalidade` — comissão por área/modalidade
- `cfg_palpite_cotado` — cotação especial para palpites específicos
- `cfg_extracao_descarga` — limite de descarga por extração/modalidade
- `cfg_parametros` — parâmetros gerais da banca
- `cfg_grade_comissao` / `cfg_grade_comissao_itens` — grade de comissão

**Movimento:**
- `mov_sorteio` — sorteios (situacao: A=Aberto, F=Fechado; numeros_sorteados)
- `mov_jb` — bilhetes de Jogo do Bicho
- `mov_jb_sorteio` / `mov_jb_sort_palpite` — detalhe do JB por sorteio
- `mov_bilhetinho` / `mov_bilhetinho_sorteio` — bilhetinho
- `mov_caixa` / `mov_caixa_lancamentos` — caixa do vendedor

## Triggers importantes

- `trg_mv_cad_extracao_cria_sorteios` — ao inserir/atualizar extração, cria sorteios
- `trg_mv_sorteio_verifica_ganhadores` — ao registrar resultado, calcula premiados
- `trg_mv_sorteio_verifica_ganhadores_lotinha` — versão Lotinha
- `trg_mv_sorteio_verifica_ganhadores_qui_sen` — versão Quininha/Seninha

## Migrations versionadas

- `app/migrations/etapa2_auth.sql` — cria `mob_auth_token` (idempotente)

DDL parcial antigo (só `system_users`, `cad_vendedor`): `docs/references/ddl-parcial.md`.
