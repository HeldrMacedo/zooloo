# Fluxograma — Módulo REST API

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Confiança: 🟢 CONFIRMADO

## Autenticação — Login JWT

```mermaid
flowchart TD
    A([POST /rest.php?class=ApplicationAuthenticationRestService&method=login]) --> B[Extrair login + password do body]
    B --> C[ApplicationAuthenticationService::authenticate login, password]
    C --> D{Credenciais válidas?}
    D -- não --> E[HTTP 401 Unauthorized]
    D -- sim --> F[Carregar SystemUser]
    F --> G[Buscar terminal associado ao usuário]
    G --> H[Construir payload JWT: user_id, terminal_id, area_id, expires=+1h]
    H --> I[JWT::encode payload, APPLICATION_NAME+seed, 'HS256']
    I --> J[HTTP 200 + token]
    E --> Z([Fim])
    J --> Z
```

## BilheteRestService — Registrar Bilhete

```mermaid
flowchart TD
    A([POST registrar com Bearer JWT]) --> B[Validar JWT: extrair terminal_id]
    B --> C[Verificar: terminal pertence ao usuário autenticado]
    C --> D{Terminal válido?}
    D -- não --> E[HTTP 403 Forbidden]
    D -- sim --> F[Verificar: sorteio existe e situacao='A']
    F --> G{Sorteio aberto?}
    G -- não --> H[Erro: Sorteio fechado]
    G -- sim --> I[Verificar: sorteio está na área do vendedor]
    I --> J{Área compatível?}
    J -- não --> K[Erro: Extração não disponível na área]
    J -- sim --> L[Verificar: dentro do hora_limite da extração]
    L --> M{Dentro do prazo?}
    M -- não --> N[Erro: Prazo encerrado]
    M -- sim --> O[Para cada palpite: verificar limite_palpite]
    O --> P["COALESCE(cfg_area_limite.valor, cfg_parametros.limite_global)"]
    P --> Q{Valor dentro do limite?}
    Q -- não --> R[Erro: Limite de aposta atingido]
    Q -- sim --> S[Verificar limite_venda diário do vendedor]
    S --> T{Limite diário OK?}
    T -- não --> U[Erro: Limite de venda diário atingido]
    T -- sim --> V[Gerar bilhete_numero = MAX+1 por vendedor/dia]
    V --> W[Gerar string_autorizacao = md5 ymd+6chars]
    W --> X[INSERT mov_jb + mov_jb_sorteio + mov_jb_sort_palpite]
    X --> Y[HTTP 200 + bilhete_numero + string_autorizacao]
    E --> Z([Fim])
    H --> Z
    K --> Z
    N --> Z
    R --> Z
    U --> Z
    Y --> Z
```

## BilheteRestService — Cancelar Bilhete

```mermaid
flowchart TD
    A([POST cancelar com Bearer JWT]) --> B[Verificar: bilhete pertence ao terminal]
    B --> C{Permissão pode_cancelar?}
    C -- não --> D[HTTP 403]
    C -- sim --> E{Dentro da janela de tempo pode_cancelar_tempo?}
    E -- não --> F[Erro: Fora do prazo de cancelamento]
    E -- sim --> G[Verificar quota diária de cancelamentos pode_cancelar_qtde]
    G --> H{Quota disponível?}
    H -- não --> I[Erro: Quota de cancelamentos atingida]
    H -- sim --> J[UPDATE mov_jb situacao='C']
    J --> K[HTTP 200 confirmação]
    D --> Z([Fim])
    F --> Z
    I --> Z
    K --> Z
```

## SorteioRestService — Abertos por Área

```mermaid
flowchart TD
    A([GET abertos com Bearer JWT]) --> B[Extrair area_id do JWT]
    B --> C["SQL: JOIN mov_sorteio + cad_extracao + cfg_area_extracao WHERE area_id + data_hoje + situacao='A'"]
    C --> D[Para cada sorteio:]
    D --> E[Calcular minutos_restantes = hora_limite - agora]
    E --> F{minutos_restantes <= 30?}
    F -- sim --> G[urgente = true]
    F -- não --> H[urgente = false]
    G --> I[Retornar sorteio com urgente flag]
    H --> I
    I --> J{Mais sorteios?}
    J -- sim --> D
    J -- não --> K[HTTP 200 + array de sorteios]
    K --> L([Fim])
```

## ModalidadeRestService — Disponíveis por Área

```mermaid
flowchart TD
    A([GET disponiveis com Bearer JWT]) --> B[Extrair area_id + extracao_id do request]
    B --> C["SQL: JOIN cad_modalidade + int_jogo + cfg_area_cotacao + cfg_area_limite com COALESCE para overrides por área"]
    C --> D[Agrupar por descricao_grupo]
    D --> E[Para cada modalidade:]
    E --> F["SELECT * FROM cfg_palpite_cotado WHERE modalidade_id = X"]
    F --> G[Injetar palpites_cotados na resposta]
    G --> H{Mais modalidades?}
    H -- sim --> E
    H -- não --> I[HTTP 200 + modalidades agrupadas com cotações]
    I --> J([Fim])
```

---

## Triggers de Banco — mov_jb e mov_jb_sorteio

> Confirmado pela consulta direta ao banco `jb` em 2026-05-01 🟢

Quatro triggers BEFORE INSERT executam automaticamente ao registrar um bilhete:

```
INSERT mov_jb
    └── trg_mv_jb (BEFORE INSERT)
            └── func_trg_mv_jb_datahora()
                    └── SET new.data_hora_servidor = now()

INSERT mov_jb_sorteio
    ├── trg_mv_jb_sorteio_comissao (BEFORE INSERT)
    │       └── func_trg_mv_jb_sorteio_comissao()
    │               Hierarquia de comissão (cfg_vendedor_mod_comissao):
    │               1. global área (area_id, modalidade_id=NULL, vendedor_id=NULL)
    │               2. área + modalidade (modalidade_id, vendedor_id=NULL)
    │               3. modalidade global (area_id=NULL, vendedor_id=NULL)
    │               4. área + vendedor (modalidade_id=NULL)
    │               5. área + modalidade + vendedor (mais específico)
    │               6. fallback: cad_vendedor.comissao
    │               SET new.comissao_sorteio = (varcomissao/100) * new.total_sorteio
    │               UPDATE mov_jb SET comissao_valor += new.comissao_sorteio
    │
    ├── trg_mv_jb_sorteio_previsao (BEFORE INSERT)
    │       └── func_trg_mov_jb_sorteio_previsao()
    │               Calcula previsao_premio por jogo_id:
    │               - Milhar Invertida (3): usa cotação milhar
    │               - Milhar+Centena (9,10): soma cotações
    │               - Centena+Dezena (19): soma cotações
    │               - Milhar+Centena+Dezena (18): soma cotações
    │               - Centena Invertida (5): usa cotação centena
    │               - Bilhetinho/Quininha/Seninha (1,25,27): multiplicadorColocacao01
    │               - Milhar Brinde (20): vlr_palpite=1
    │               SET new.previsao_premio = mtpc_modalidade * vlr_palpite
    │
    └── trg_mv_jb_sorteio_instantaneo (BEFORE INSERT)
            └── func_trg_mov_jb_sorteio_instantaneo()
                    Para jogo_abrev='MINST' (Milhar Instantânea):
                    INSERT mov_sorteio com situacao='F' imediatamente (sorteio instantâneo)
                    UPDATE mov_jb.sorteios_ids = novo sorteio_id
```

> **Endpoint base:** `http://localhost/rest.php`
> **Autenticação:** Bearer JWT (HS256, TTL 1h) ou Basic (API key estática `zooloo_api_key_2025`)
> **Lacuna 🔴:** `cfg_extracao_modalidade` referenciada em ModalidadeRestService sem Active Record correspondente.
