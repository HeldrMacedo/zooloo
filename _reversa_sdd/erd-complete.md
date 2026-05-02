# ERD Completo — zooloo

> Gerado pelo Reversa Architect em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO

---

## Diagrama de Entidade-Relacionamento

```mermaid
erDiagram
    %% ===== CADASTRO =====

    cad_area {
        int area_id PK
        varchar descricao
        varchar complemento
        char ativo
    }

    cad_coletor {
        int coletor_id PK
        varchar nome
        int area_id FK
        int usuario_id FK
        char ativo
        char acesso_web
    }

    cad_extracao {
        int extracao_id PK
        varchar descricao
        time hora_limite
        char segunda
        char terca
        char quarta
        char quinta
        char sexta
        char sabado
        char domingo
        decimal premiacao_maxima
        int filtro_banca
        char ativo
    }

    cad_modalidade {
        int modalidade_id PK
        varchar descricao
        int jogo_id FK
        decimal multiplicador_colocacao_1
        decimal multiplicador_colocacao_2
        decimal multiplicador_colocacao_3
        decimal multiplicador_colocacao_4
        decimal multiplicador_colocacao_5
        int ordem
        char ativo
    }

    cad_vendedor {
        int vendedor_id PK
        varchar nome
        int area_id FK
        int coletor_id FK
        int usuario_id FK
        char pode_cancelar
        time pode_cancelar_tempo
        int pode_cancelar_qtde
        char pode_pagar
        char pode_reimprimir
        int pode_reimprimir_qtde
        decimal limite_venda
        char ativo
    }

    cad_terminal {
        int terminal_id PK
        int vendedor_id FK
        int usuario_id FK
        varchar descricao
        char ativo
    }

    %% ===== CONFIGURAÇÃO =====

    cfg_area_extracao {
        int area_extracao_id PK
        int area_id FK
        int extracao_id FK
    }

    cfg_area_cotacao {
        int area_cotacao_id PK
        int area_id FK
        int extracao_id FK
        int modalidade_id FK
        decimal cotacao
    }

    cfg_area_limite {
        int area_limite_id PK
        int area_id FK
        int modalidade_id FK
        decimal limite_valor
    }

    cfg_area_comissao_modalidade {
        int id PK
        int area_id FK
        int modalidade_id FK
        decimal percentual_comissao
    }

    cfg_extracao_descarga {
        int extracao_descarga_id PK
        int extracao_id FK
        int modalidade_id FK
        decimal valor_descarga
    }

    cfg_palpite_cotado {
        int palpite_cotado_id PK
        int modalidade_id FK
        varchar palpite
        decimal cotacao
    }

    cfg_parametros {
        int parametros_id PK
        varchar nome_banca
        decimal limite_global
        char permite_bilhetinho
        char permite_quininha
        char permite_seninha
        char permite_lotinha
    }

    %% ===== MOVIMENTO =====

    mov_sorteio {
        int sorteio_id PK
        int extracao_id FK
        int sorteio_numero
        date data_sorteio
        time hora_sorteio
        char situacao
        varchar numeros_sorteados
    }

    mov_jb {
        int jb_id PK
        int area_id FK
        int coletor_id FK
        int terminal_id FK
        int vendedor_id FK
        varchar bilhete_numero
        timestamp data_hora
        decimal total_bilhete
        decimal comissao_valor
        varchar string_autorizacao
        char cancelado
        varchar cancelado_motivo
        timestamp data_cancelamento
    }

    mov_jb_sorteio {
        int jb_sorteio_id PK
        int jb_id FK
        int sorteio_id FK
        char sorteado_pago
        decimal previsao_premio
        decimal sorteado_valor_pago
    }

    mov_jb_sort_palpite {
        int jb_sort_palpite_id PK
        int jb_sorteio_id FK
        varchar palpite
        int modalidade_id FK
        decimal valor_aposta
        decimal premio_colocacao_01
        decimal premio_colocacao_02
        decimal premio_colocacao_03
        decimal premio_colocacao_04
        decimal premio_colocacao_05
        decimal ganhou_premio_total
        decimal pago_premio_total
    }

    %% ===== INTERNO =====

    int_jogo {
        int jogo_id PK
        varchar codigo
        varchar descricao
        char informar_valores_modalidade
        int qtd_colocacao_premio
    }

    int_calculo_sorteio {
        int calculo_id PK
        int sorteio_id FK
        int modalidade_id FK
        decimal valor_calculado
    }

    %% ===== RELACIONAMENTOS =====

    cad_area ||--o{ cad_coletor : "tem gerentes"
    cad_area ||--o{ cad_vendedor : "tem vendedores"
    cad_area ||--o{ cfg_area_extracao : "ativa extrações"
    cad_area ||--o{ cfg_area_cotacao : "tem cotações"
    cad_area ||--o{ cfg_area_limite : "tem limites"
    cad_area ||--o{ cfg_area_comissao_modalidade : "tem comissões"
    cad_area ||--o{ mov_jb : "registra bilhetes"

    cad_extracao ||--o{ cfg_area_extracao : "ativada em áreas"
    cad_extracao ||--o{ cfg_area_cotacao : "tem cotações"
    cad_extracao ||--o{ cfg_extracao_descarga : "tem descargas"
    cad_extracao ||--o{ mov_sorteio : "gera sorteios"

    cad_modalidade ||--o{ cfg_area_cotacao : "tem cotações"
    cad_modalidade ||--o{ cfg_area_limite : "tem limites"
    cad_modalidade ||--o{ cfg_area_comissao_modalidade : "tem comissões"
    cad_modalidade ||--o{ cfg_extracao_descarga : "tem descargas"
    cad_modalidade ||--o{ cfg_palpite_cotado : "tem palpites cotados"
    cad_modalidade ||--o{ mov_jb_sort_palpite : "em palpites"

    cad_vendedor ||--o{ cad_terminal : "tem terminais"
    cad_vendedor ||--o{ mov_jb : "registra bilhetes"
    cad_coletor ||--o{ cad_vendedor : "supervisiona"

    int_jogo ||--o{ cad_modalidade : "define tipo"

    mov_sorteio ||--o{ mov_jb_sorteio : "tem bilhetes"
    mov_sorteio ||--o{ int_calculo_sorteio : "tem cálculos"

    mov_jb ||--o{ mov_jb_sorteio : "participa de sorteios"
    cad_terminal ||--o{ mov_jb : "registra bilhetes"

    mov_jb_sorteio ||--o{ mov_jb_sort_palpite : "tem palpites"
```

---

## Tabela de Cardinalidades

| Relação | Tipo | Observação |
|---|---|---|
| Area → Gerente | 1:N | Uma área tem vários gerentes |
| Area → Vendedor | 1:N | Uma área tem vários vendedores |
| Gerente → Vendedor | 1:N | Um gerente supervisiona vários vendedores |
| Extracao → Sorteio | 1:N | Uma extração gera múltiplos sorteios (um por ocorrência) |
| Area × Extracao | N:M | Via `cfg_area_extracao` (sem campo ativo — presença = ativo) |
| Area × Modalidade | N:M via cfg_area_cotacao | Cotação específica por área+extração+modalidade |
| Vendedor → Terminal | 1:N | Um vendedor pode ter vários terminais (app) |
| MovJb → MovSorteio | N:M via mov_jb_sorteio | Bilhete pode participar de múltiplos sorteios |
| IntJogo → Modalidade | 1:1 | Cada tipo de jogo tem apenas uma modalidade |

> **🔴 Lacuna:** `cfg_extracao_modalidade` referenciada em `ModalidadeRestService` sem Active Record identificado — pode existir no banco sem entidade PHP mapeada.
