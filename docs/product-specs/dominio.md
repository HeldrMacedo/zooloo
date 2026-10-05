# Domínio do negócio

Sistema de banca de Jogo do Bicho e derivados.

| Termo | Significado |
|---|---|
| **Área** | Zona geográfica/franquia da banca |
| **Extração** | Sorteio agendado (dias da semana e hora limite para apostas) |
| **Modalidade** | Tipo de aposta (Milhar, Centena, Dezena, Grupo, Duque, Terno...) |
| **Vendedor** | Ponto de venda de bilhetes, vinculado a uma Área |
| **Coletor / Gerente** | Supervisor de vendedores, vinculado a uma Área |
| **Terminal** | Dispositivo de venda (serial pré-cadastrado em `cad_terminal`) |
| **Bilhete / JB** | Registro de aposta (`mov_jb` = jogo do bicho, `mov_bilhetinho` = bilhetinho) |
| **Palpite** | Número apostado em um bilhete |
| **Sorteio** | Ocorrência de uma extração numa data (`mov_sorteio`) |
| **Resultado** | Números sorteados (`mov_sorteio.numeros_sorteados`) |
| **Cotação** | Multiplicador do prêmio por área/modalidade (`cfg_area_cotacao`) |
| **Comissão** | Percentual do vendedor sobre as vendas |
| **Descarga** | Limite de apostas por número para controle de risco (`cfg_extracao_descarga`) |

## Tipos de jogo (`int_jogo`)

Semeados na tabela `int_jogo` (exemplos):

- `BIL` — Bilhetinho
- `MBP` — Milhar Brinde Progressiva
- `QUI` — Quininha
- `SEN` — Seninha
- `LOT` — Lotinha
- `DD3Li` — Duque de Dezena 3 na Linha
- `TD3Li` — Terno de Dezena 3 na Linha

Formato de `palpites` por modalidade (visão do app): `../app-zooloo/docs/product-specs/regras-jogo.md`.
