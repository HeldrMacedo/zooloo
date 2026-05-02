# Dicionário de Dados — zooloo

> Gerado pelo Reversa Archaeologist em 2026-04-30
> Fonte: Active Records em `app/model/entities/`
> Confiança: 🟢 CONFIRMADO (extraído do código) | 🟡 INFERIDO | 🔴 LACUNA

---

## Tabelas de Cadastro (cad_*)

### cad_area

**Classe:** `Area` | **PK:** `area_id` (max) | **Banco:** `applications`

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `area_id` | integer | Sim (PK) | Identificador da área |
| `descricao` | varchar | Sim | Nome da área/franquia |
| `ativo` | char(1) | Sim | S=Ativo, N=Inativo. Padrão: 'S' |

---

### cad_coletor (Gerente)

**Classe:** `Gerente` | **PK:** `coletor_id` (max) | **Banco:** `applications`

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `coletor_id` | integer | Sim (PK) | Identificador do gerente/coletor |
| `nome` | varchar | Sim | Nome completo |
| `area_id` | integer (FK→cad_area) | Sim | Área vinculada |
| `usuario_id` | integer (FK→system_users) | Sim | Usuário do sistema |
| `acesso_web` | char(1) | Não | S/N — acesso via web |
| `outras_areas` | varchar? | Não | Áreas adicionais 🔴 |
| `ativo` | char(1) | Sim | S/N |

---

### cad_extracao

**Classe:** `Extracao` | **PK:** `extracao_id` (max) | **Banco:** `applications`

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `extracao_id` | integer | Sim (PK) | |
| `filtro_banca` | integer | Sim | Sempre = 1 |
| `descricao` | varchar | Sim | Nome da extração |
| `descricao_mobile` | varchar | Sim | Abreviação para app |
| `hora_limite` | time | Sim | Hora máxima para apostas |
| `segunda` | char(1) | Não | S/N |
| `terca` | char(1) | Não | S/N |
| `quarta` | char(1) | Não | S/N |
| `quinta` | char(1) | Não | S/N |
| `sexta` | char(1) | Não | S/N |
| `sabado` | char(1) | Não | S/N |
| `domingo` | char(1) | Não | S/N |
| `premiacao_maxima` | integer | Sim | Nº máximo de prêmios (1º ao Nº) |
| `ultimo_sorteio_numero` | integer | Não | Último número de sorteio gerado |
| `gerar_restante` | ? | Não | 🔴 |
| `ativo` | char(1) | Sim | S/N |
| `dia_sorteio_inicial` | date | Sim | Data do primeiro sorteio |
| `extracao_instantanea` | ? | Não | Flag para extração instantânea 🔴 |
| `calculo_id` | integer (FK→int_calculo_sorteio) | Não | Tipo de cálculo |
| `limite_palpite` | numeric | Não | Comentado no form 🔴 |

---

### cad_modalidade

**Classe:** `Modalidade` | **PK:** `modalidade_id` (max) | **Banco:** `applications`

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `modalidade_id` | integer | Sim (PK) | |
| `jogo_id` | integer (FK→int_jogo) | Sim | Tipo de jogo |
| `ordem` | integer | Sim | Ordem de exibição |
| `apresentacao` | varchar | Sim | Nome de exibição |
| `multiplicador` | numeric(?,2) | Sim | Multiplicador padrão do prêmio |
| `limite_descarga` | numeric(?,2) | Não | Limite global de descarga |
| `limite_palpite` | numeric(?,2) | Não | Limite de valor por palpite |
| `limite_aceite` | numeric(?,2) | Não | Limite de aceite |
| `ativo` | char(1) | Sim | S/N |
| `multiplicador_colocacao_01` | numeric | Não | Mult. para 1ª colocação |
| `multiplicador_colocacao_02` | numeric | Não | Mult. para 2ª colocação |
| `multiplicador_colocacao_03` | numeric | Não | |
| `multiplicador_colocacao_04` | numeric | Não | |
| `multiplicador_colocacao_05` | numeric | Não | |
| `limite_min_sorteio_diario` | numeric | Não | Mínimo diário de sorteio |
| `limite_min_sorteio_colocacao_diario` | numeric | Não | |

---

### cad_vendedor

**Classe:** `Vendedor` | **PK:** `vendedor_id` (max) | **Banco:** `applications`

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `vendedor_id` | integer | Sim (PK) | |
| `area_id` | integer (FK→cad_area) | Sim | |
| `coletor_id` | integer (FK→cad_coletor) | Sim | Gerente/Setorista |
| `usuario_id` | integer (FK→system_users) | Sim | |
| `nome` | varchar | Sim | |
| `cep` | varchar | Não | |
| `rua` | varchar | Não | |
| `numero` | varchar | Não | |
| `bairro` | varchar | Não | |
| `cidade` | varchar | Não | |
| `uf` | char(2) | Não | UF do estado |
| `comissao` | numeric(?,2) | Sim | Percentual de comissão |
| `pode_cancelar` | char(1) | Sim | S/N. Padrão: 'N' |
| `pode_cancelar_tempo` | time | Não | HH:MM:SS. Padrão: '00:00:00' |
| `pode_cancelar_qtde` | integer | Não | Padrão: 0 |
| `pode_pagar` | char(1) | Sim | S/N. Padrão: 'S' |
| `pode_pagar_outro` | char(1) | Sim | S/N. Padrão: 'N' |
| `pode_reimprimir` | char(1) | Sim | S/N. Padrão: 'N' |
| `pode_reimprimir_qtde` | integer | Não | |
| `pode_reimprimir_tempo` | time | Não | |
| `pode_reimprimir_sort_naopg` | ? | Não | 🔴 |
| `pode_reimprimir_sort_pago` | ? | Não | 🔴 |
| `pode_reimprimir_outro` | ? | Não | 🔴 |
| `pode_reimprimir_sort_naopg_outro` | ? | Não | 🔴 |
| `pode_reimprimir_sort_pago_outro` | ? | Não | 🔴 |
| `reimprimir_data` | ? | Não | 🔴 |
| `reimprimir_qtde` | ? | Não | 🔴 |
| `exibe_comissao` | char(1) | Sim | S/N. Padrão: 'S' |
| `exibe_premiacao` | char(1) | Sim | S/N/U. Padrão: 'S' |
| `tipo_limite` | char(1)? | Não | D=Diário, A=Acumulado (comentado) |
| `treinamento` | char(1)? | Não | S/N (comentado) |
| `observacao` | text | Não | |
| `ativo` | char(1) | Sim | S/N. Padrão: 'S' |
| `limite_venda` | numeric(?,2) | Sim | Limite total por bilhete |

---

### cad_terminal

**Classe:** `Terminal` | **PK:** `terminal_id` (max) | **Banco:** `applications`

> Campos extraídos do `TerminalRestService::registrar`. 🟡

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `terminal_id` | integer | Sim (PK) | |
| `vendedor_id` | integer (FK→cad_vendedor) | Sim | |
| `serial` | varchar | Sim | Serial/IMEI do dispositivo |
| `tipo` | varchar | Não | Padrão: 'APP' |
| `multi_usuario` | char(1) | Não | Padrão: 'N' |
| `ativo` | char(1) | Sim | S/N. Padrão: 'S' |

---

## Tabelas de Configuração (cfg_*)

### cfg_area_extracao

**Classe:** `AreaExtracao` | **PK:** `area_extracao_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `area_extracao_id` | integer | Sim (PK) | |
| `area_id` | integer (FK→cad_area) | Sim | |
| `extracao_id` | integer (FK→cad_extracao) | Sim | |
| `ativo` | boolean | Sim | true/false (PostgreSQL boolean) |

---

### cfg_area_cotacao

**Classe:** `AreaCotacao` | **PK:** `area_cotacao_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `area_cotacao_id` | integer | Sim (PK) | |
| `area_id` | integer (FK→cad_area) | Sim | |
| `extracao_id` | integer (FK→cad_extracao) | Não | NULL = todas as extrações |
| `modalidade_id` | integer (FK→cad_modalidade) | Sim | |
| `multiplicador` | numeric(?,2) | Sim | Sobrescreve o multiplicador da modalidade |

**Unicidade:** `(area_id, extracao_id, modalidade_id)` — validado no código.

---

### cfg_area_limite

**Classe:** `AreaLimite` | **PK:** `area_limite_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `area_limite_id` | integer | Sim (PK) | |
| `area_id` | integer (FK→cad_area) | Sim | |
| `modalidade_id` | integer (FK→cad_modalidade) | Sim | |
| `limite_palpite` | numeric(?,2) | Sim | Sobrescreve o limite da modalidade |

**Unicidade:** `(area_id, modalidade_id)`.

---

### cfg_area_comissao_modalidade

**Classe:** `AreaComissaoModalidade` | **PK:** `area_comissao_modalidade_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `area_comissao_modalidade_id` | integer | Sim (PK) | |
| `area_id` | integer (FK→cad_area) | Sim | |
| `modalidade_id` | integer (FK→cad_modalidade) | Sim | |
| `comissao` | numeric(?,2) | Sim | Percentual 0-100% |

**Unicidade:** `(area_id, modalidade_id)`.

---

### cfg_extracao_descarga

**Classe:** `ExtracaoDescarga` | **PK:** `extracao_descarga_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `extracao_descarga_id` | integer | Sim (PK) | |
| `extracao_id` | integer (FK→cad_extracao) | Sim | |
| `modalidade_id` | integer (FK→cad_modalidade) | Sim | |
| `limite_descarga` | numeric(?,2) | Sim | Limite máximo por número apostado |

**Unicidade:** `(extracao_id, modalidade_id)`.

---

### cfg_palpite_cotado

**Classe:** `PalpiteCotado` | **PK:** `palpite_cotado_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `palpite_cotado_id` | integer | Sim (PK) | |
| `modalidade_id` | integer (FK→cad_modalidade) | Sim | |
| `palpite` | char(4) | Sim | Número de 4 dígitos |
| `cotacao` | numeric(?,2) | Sim | Percentual 0-100% |
| `ativo` | char(1) | Sim | S/N. Padrão: 'S' |

**Unicidade:** `(modalidade_id, palpite)`.

---

### cfg_parametros

**Classe:** `Parametros` | **PK:** `parametros_id` (max) | **Singleton**

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `parametros_id` | integer | Sim (PK) | |
| `nome_banca` | varchar | Sim | Nome da banca |
| `cnpj` | varchar | Não | |
| `telefone` | varchar | Não | |
| `cidade` | varchar | Não | |
| `estado` | varchar | Não | |
| `site` | varchar | Não | |
| `email` | varchar | Não | |
| `mensagem_01` a `mensagem_05` | text | Não | Mensagens exibidas no app |
| `valor_milhar_brinde` | numeric(?,2) | Não | Valor mínimo para brinde milhar |
| `valor_bilhetinho` | numeric(?,2) | Não | Valor do bilhetinho |
| `ativo_bilhetinho` | char(1) | Não | S/N |
| `ativo_modalidade` | char(1) | Não | S/N |
| `qtde_num_mi` | integer | Não | Qtd números milhar invertida (4-10) |
| `qtde_num_ci` | integer | Não | Qtd números centena invertida (3-10) |
| `qtde_num_mci` | integer | Não | Qtd números milhar centena invertida (4-10) |
| `ativo_instantaneo` | char(1) | Não | S/N |
| `ativo_quininha` | char(1) | Não | S/N |
| `ativo_seninha` | char(1) | Não | S/N |
| `ativo_lotinha` | char(1) | Não | S/N |
| `ativo_jb` | char(1) | Não | S/N |
| `sena_inc_quina` | char(1) | Não | S/N |
| `sena_inc_quadra` | char(1) | Não | S/N |
| `quina_inc_quadra` | char(1) | Não | S/N |
| `quina_inc_terno` | char(1) | Não | S/N |
| `limite_extracao` | char(1) | Não | S/N |
| `ativo_milharpremiada` | char(1) | Não | S/N |
| `valor_milharpremiada` | numeric(?,2) | Não | |

---

## Tabelas de Movimento (mov_*)

### mov_sorteio

**Classe:** `MovSorteio` | **PK:** `sorteio_id` (max)

| Campo | Tipo inferido | Obrigatório | Descrição |
|---|---|---|---|
| `sorteio_id` | integer | Sim (PK) | |
| `extracao_id` | integer (FK→cad_extracao) | Sim | |
| `sorteio_numero` | integer | Sim | Número sequencial |
| `data_sorteio` | date | Sim | Data do sorteio |
| `hora_sorteio` | time | Não | Hora do sorteio |
| `situacao` | char(1) | Sim | A=Aberto, F=Fechado |
| `numeros_sorteados` | text | Não | CSV: "1234,5678,...". Null antes do resultado |

---

### mov_jb

**Classe:** `MovJb` | **PK:** `jb_id` | Campos extraídos de `BilheteRestService`. 🟡

| Campo | Tipo inferido | Descrição |
|---|---|---|
| `jb_id` | integer (PK) | |
| `area_id` | integer (FK→cad_area) | |
| `coletor_id` | integer (FK→cad_coletor) | |
| `terminal_id` | integer (FK→cad_terminal) | |
| `vendedor_id` | integer (FK→cad_vendedor) | |
| `sorteios_ids` | varchar | IDs dos sorteios separados por vírgula |
| `sorteios_quantidade` | integer | |
| `bilhete_numero` | integer | Sequencial por vendedor/dia |
| `data_hora` | timestamp | |
| `nome_cliente` | varchar | Opcional |
| `fone_cliente` | varchar | Opcional |
| `total_bilhete` | numeric | |
| `comissao_valor` | numeric | |
| `comissao_pago` | char(1) | S/N |
| `string_autorizacao` | varchar | Código único de autorização |
| `cancelado` | char(1) | S/N |
| `cancelado_motivo` | text | |
| `data_cancelamento` | timestamp | |
| `reimpressao` | integer | Contador de reimpressões |
| `data_reimpressao` | timestamp | |

---

### mov_jb_sorteio

**Classe:** `MovJbSorteio` | **PK:** `jb_sorteio_id` | 🟡

| Campo | Tipo inferido | Descrição |
|---|---|---|
| `jb_sorteio_id` | integer (PK) | |
| `jb_id` | integer (FK→mov_jb) | |
| `sorteio_id` | integer (FK→mov_sorteio) | |
| `modalidade_id` | integer (FK→cad_modalidade) | |
| `palpites` | varchar | CSV de palpites |
| `palpites_quantidade` | integer | |
| `colocao_inicial` | integer | Colocação inicial apostada |
| `colocao_final` | integer | Colocação final apostada |
| `valor_palpites` | numeric | Valor por palpite |
| `total_sorteio` | numeric | |
| `comissao_sorteio` | numeric | Calculado por trigger |
| `sorteado` | char(1) | S/N |
| `sorteado_colocacao` | varchar | |
| `sorteado_valor` | numeric | |
| `sorteado_pago` | char(1) | S/N |
| `previsao_premio` | numeric | |
| `sorteado_valor_pago` | numeric | |

---

### mov_jb_sort_palpite

**Classe:** `MovJbSortPalpite` | **PK:** implícito | 🟡

| Campo | Tipo inferido | Descrição |
|---|---|---|
| `jb_sorteio_id` | integer (FK) | |
| `jb_id` | integer (FK) | |
| `sorteio_id` | integer (FK) | |
| `modalidade_id` | integer (FK) | |
| `palpite` | varchar(4) | Número apostado |
| `valor_palpite` | numeric | |
| `jogou_colocacao_01` a `_10` | char(1) | S/N — colocações apostadas |
| `premio_colocacao_01` a `_05` | numeric | Preenchido por trigger |
| `ganhou_premio_total` | numeric | |
| `pago_premio_total` | char(1) | S/N |

---

## Tabelas Internas (int_*)

### int_jogo

**Classe:** `IntJogo` | **PK:** `jogo_id` (max)

| Campo | Tipo inferido | Descrição |
|---|---|---|
| `jogo_id` | integer (PK) | |
| `filtro_banca` | integer | 1=banca, outro=genérico |
| `descricao_grupo` | varchar | Grupo de exibição no app |
| `descricao` | varchar | Nome completo |
| `abreviacao` | varchar | Sigla (BIL, MBP, QUI, etc.) |
| `tamanho_max` | integer | Tamanho máximo do palpite |
| `ativo` | char(1) | S/N |
| `qtd_colocacao_premio` | integer | Qtde de colocações premiadas |
| `informar_valores_modalidade` | char(1) | S/N — permite editar multiplicadores |
| `orientacao` | text | Texto de ajuda para o usuário |
| `habilitar_edicao_regular` | char(1)? | 🔴 |

---

### int_calculo_sorteio

**Classe:** `IntCalculoSorteio` | **PK:** `calculo_id` (max)

| Campo | Tipo inferido | Descrição |
|---|---|---|
| `calculo_id` | integer (PK) | |
| `descricao` | varchar | |
| `abreviacao` | varchar | |
| `orientacao` | text | |
| `premiacao_maxima` | integer | Nº máximo de prêmios para este cálculo |
| `ordem` | integer | |
| `ativo` | char(1) | S/N |

---

## Relacionamentos

```
cad_area
  ├── cad_coletor (gerente) [N:1]
  ├── cad_vendedor [N:1]
  ├── cfg_area_extracao [N:N com cad_extracao]
  ├── cfg_area_cotacao [N:N com cad_modalidade]
  ├── cfg_area_limite [N:N com cad_modalidade]
  └── cfg_area_comissao_modalidade [N:N com cad_modalidade]

cad_extracao
  ├── cfg_area_extracao [N:N com cad_area]
  ├── cfg_extracao_descarga [N:N com cad_modalidade]
  └── mov_sorteio [1:N]

cad_modalidade
  ├── int_jogo [N:1] — tipo de jogo
  ├── cfg_area_cotacao [N:N com cad_area]
  ├── cfg_area_limite [N:N com cad_area]
  ├── cfg_area_comissao_modalidade [N:N com cad_area]
  ├── cfg_extracao_descarga [N:N com cad_extracao]
  └── cfg_palpite_cotado [1:N]

cad_vendedor
  ├── cad_area [N:1]
  ├── cad_coletor [N:1]
  ├── system_users [1:1]
  ├── cad_terminal [1:N]
  └── mov_jb [1:N]

mov_sorteio
  ├── cad_extracao [N:1]
  ├── mov_jb_sorteio [1:N]
  └── → triggers calculam ganhadores

mov_jb
  ├── cad_area, cad_coletor, cad_vendedor, cad_terminal
  ├── mov_jb_sorteio [1:N]
  └── (sorteios_ids: CSV desnormalizado)

mov_jb_sorteio
  ├── mov_jb [N:1]
  ├── mov_sorteio [N:1]
  ├── cad_modalidade [N:1]
  └── mov_jb_sort_palpite [1:N]
```

---

## Tabela de Grupos do Jogo do Bicho

| Grupo | Animal | Dezenas |
|---|---|---|
| 01 | Avestruz | 01-04 |
| 02 | Águia | 05-08 |
| 03 | Burro | 09-12 |
| 04 | Borboleta | 13-16 |
| 05 | Cachorro | 17-20 |
| 06 | Cabra | 21-24 |
| 07 | Carneiro | 25-28 |
| 08 | Camelo | 29-32 |
| 09 | Cobra | 33-36 |
| 10 | Coelho | 37-40 |
| 11 | Cavalo | 41-44 |
| 12 | Elefante | 45-48 |
| 13 | Galo | 49-52 |
| 14 | Gato | 53-56 |
| 15 | Jacaré | 57-60 |
| 16 | Leão | 61-64 |
| 17 | Macaco | 65-68 |
| 18 | Porco | 69-72 |
| 19 | Pavão | 73-76 |
| 20 | Peru | 77-80 |
| 21 | Touro | 81-84 |
| 22 | Tigre | 85-88 |
| 23 | Urso | 89-92 |
| 24 | Veado | 93-96 |
| 25 | Vaca | 97-00 |

> Cálculo: `grupo = ceil(dezmilhar / 4)`, onde `dezmilhar = int(últimos 2 dígitos)`. Se `00` → `dezmilhar = 100`.
