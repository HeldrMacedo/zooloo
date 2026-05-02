# Análise de Código — zooloo

> Gerado pelo Reversa Archaeologist em 2026-04-30
> doc_level: completo

---

## Visão Geral da Arquitetura

Todos os módulos do zooloo seguem o padrão MVC do Adianti Framework:

- **Model (TRecord):** Active Record com mapeamento direto para tabelas PostgreSQL. Atributos declarados em `__construct` via `addAttribute()`. Relacionamentos via métodos `get_xxx()` que chamam `XxxClass::find()`.
- **Controller-Form (TPage):** Formulário de criação/edição em painel lateral (`adianti_right_panel`). Métodos: `__construct` (UI), `onSave`, `onEdit`, `onReload`, `onClose`.
- **Controller-List (TStandardList ou TPage):** Datagrid com filtros, paginação e exportação (CSV/PDF/XML). Métodos: `onSearch`, `onReload`, `onAfterSearch`, `onTurnOnOff`, `onDelete`.
- **Service-REST:** Classes estáticas invocadas via `rest.php`. Todos os métodos recebem `$param` com `_auth` injetado pelo roteador JWT.

---

## Módulo: area

**Arquivo principal:** `app/model/entities/Area.php`, `app/control/area/AreaForm.php`, `app/control/area/AreaList.php`
**Tabela:** `cad_area` · **PK:** `area_id` (max)

### Lógica de Controle

**AreaForm::onSave** — Cria ou atualiza uma área. Não valida unicidade de `descricao`. Salva e dispara `AreaList::onReload`. 🟢

**AreaList::onTurnOnOff** — Toggle do campo `ativo` (S/N). Busca por `area_id`, inverte o valor e persiste. Não afeta cascata de entidades filhas. 🟢

**AreaList:** Filtros por `area_id (=)`, `descricao (like)`, `ativo (=)`. Ordenação padrão por `descricao`. Exporta CSV/PDF/XML. Paginação configurável (10/20/50/100/1000). 🟢

### Regras de Negócio

- Campo `descricao` é obrigatório (TRequiredValidator). 🟢
- Campo `ativo` padrão = 'S'. 🟢
- IDPOLICY = 'max' (próximo ID = MAX(area_id) + 1). 🟢

---

## Módulo: gerente

**Arquivo principal:** `app/model/entities/Gerente.php`, `app/control/gerente/GerenteForm.php`, `app/control/gerente/GerenteList.php`
**Tabela:** `cad_coletor` · **PK:** `coletor_id` (max)

### Lógica de Controle

**GerenteForm::onSave** — Algoritmo complexo: cria/atualiza **SystemUser** e **Gerente** em uma única transação. 🟢

Fluxo:
1. Cria `SystemUser` com `name = $data->nome`, `email = $login . '@zooloo.com'`, `frontpage_id = SystemUser::FRONTPAGE_ID`
2. Se `coletor_id` existe → busca gerente existente → usa `gerente->usuario_id` como `$object->id`
3. Valida senha apenas se novo usuário ou se senha foi fornecida
4. Hash de senha: `md5($password)` 🔴 (MD5 é inseguro — usar bcrypt)
5. Valida unicidade de login via `SystemUser::newFromLogin()`
6. Valida confirmação de senha (`password !== repassword`)
7. Salva SystemUser → busca `getUserGerenteForUser()` → cria ou atualiza Gerente
8. Registra histórico de senha: `SystemUserOldPassword::register()`
9. Adiciona ao grupo: `SystemGroup::GERENTE_GROUP_ID`

**GerenteList::onTurnOnOff** — Toggle `ativo` do Gerente **E** toggle `active` do SystemUser vinculado (Y/N). 🟢

**Bug identificado:** `TForm::sendData('form_ferente', $data)` — typo no nome do form (deve ser `'form_gerente'`). 🟡

### Regras de Negócio

- Todo gerente tem um `SystemUser` vinculado (1:1). 🟢
- Email gerado automaticamente: `login@zooloo.com`. 🟢
- Senha obrigatória apenas para novos usuários. 🟢
- Gerente pertence a uma Área (`area_id` obrigatório). 🟢
- Grupo fixo: `SystemGroup::GERENTE_GROUP_ID`. 🟢
- **TODO:** Ao inativar gerente, deve inativar SystemUser. Implementado em `onTurnOnOff` mas não no `onSave`. 🟡

---

## Módulo: extracao

**Arquivo principal:** `app/model/entities/Extracao.php`, `app/control/extracao/ExtracaoForm.php`
**Tabela:** `cad_extracao` · **PK:** `extracao_id` (max)

### Lógica de Controle

**ExtracaoForm::onSave** — Processa checkbox de dias da semana (`semanas[]`) mapeando para campos booleanos individuais (`segunda`, `terca`, ..., `domingo` = 'S'/'N'). Força `filtro_banca = 1`. 🟢

**onReload** — Reconstrói array `semanas[]` a partir dos campos booleanos individuais para popular o `TCheckGroup`. 🟢

**Bug identificado:** `$this->form->cleat()` — typo (deve ser `clear()`). 🟡

### Trigger de Banco (Documentado no CLAUDE.md)

`trg_mv_cad_extracao_cria_sorteios` — Ao INSERT/UPDATE em `cad_extracao`, cria automaticamente os sorteios futuros em `mov_sorteio`. 🟡 (inferido do CLAUDE.md — lógica no banco)

### Campos Relevantes

| Campo | Tipo | Observação |
|---|---|---|
| `hora_limite` | time | Hora máxima para apostas |
| `premiacao_maxima` | int | Quantos prêmios (1º ao Nº) |
| `segunda` a `domingo` | char(1) | S/N por dia da semana |
| `filtro_banca` | int | Sempre = 1 (filtro fixo) |
| `calculo_id` | FK | Referência a `int_calculo_sorteio` |
| `extracao_instantanea` | ? | Indica extração instantânea |
| `dia_sorteio_inicial` | date | Data do primeiro sorteio |

### Regras de Negócio

- Campos obrigatórios: descricao, descricao_mobile, hora_limite, premiacao_maxima, dia_sorteio_inicial. 🟢
- Dias da semana são flags individuais no banco (não array). 🟢
- `filtro_banca` sempre fixado em 1. 🟡 (razão desconhecida)

---

## Módulo: modalidade

**Arquivo principal:** `app/model/entities/Modalidade.php`, `app/control/modalidade/ModalidadeForm.php`
**Tabela:** `cad_modalidade` · **PK:** `modalidade_id` (max)

### Constantes de Domínio

```php
const MILHAR_INSTANTANEA = 22;
const MILHAR_MOTO_01     = 34;
const MILHAR_MOTO_02     = 35;
const MILHAR_MOTO_03     = 36;
```

### Lógica de Controle

**ModalidadeForm::onChangeJogo** — Callback AJAX: ao selecionar `IntJogo`, verifica `informar_valores_modalidade`:
- Se 'N': desabilita campos `multiplicador`, `limite_descarga`, `limite_palpite` e exibe orientação como alerta.
- Se 'S': habilita os campos.
- Para Milhar Moto (IDs 34/35/36): exibe campo `multiplicadorColocacao01`. 🟢

**ModalidadeForm::onSave** — Para novas modalidades, calcula `ordem = MAX(ordem) + 1` via `Modalidade::all()`. Para edições, atualiza mantendo ordem existente. 🟢

**Critério de seleção de jogo:** Filtra `IntJogo` por `ativo='S'`, `filtro_banca=1`, e `NOT IN (SELECT jogo_id FROM cad_modalidade)` — cada tipo de jogo só pode ter uma modalidade. 🟢

### Campos de Multiplicador

| Campo | Descrição |
|---|---|
| `multiplicador` | Multiplicador padrão do prêmio |
| `multiplicador_colocacao_01` a `_05` | Multiplicadores por colocação |
| `limite_descarga` | Limite global de descarga |
| `limite_palpite` | Limite de valor por palpite |
| `limite_aceite` | Limite de aceite |
| `limite_min_sorteio_diario` | Limite mínimo diário |

---

## Módulo: vendedor

**Arquivo principal:** `app/model/entities/Vendedor.php`, `app/control/vendedor/VendedorForm.php`
**Tabela:** `cad_vendedor` · **PK:** `vendedor_id` (max)

### Lógica de Controle

**VendedorForm::onChangeArea** — Callback AJAX: ao selecionar Área, recarrega combo de `Gerente` filtrando por `area_id` e `ativo='S'`. Habilita o campo `coletor_id`. 🟢

**VendedorForm::onSave** — Fluxo complexo similar ao GerenteForm:
1. Validações manuais (nome, login, area_id, coletor_id, senha para novos)
2. Validação de confirmação de senha
3. Cria/atualiza `SystemUser` (email = `login@zooloo.com`, `function_name = FUNCTION_VENDEDOR`)
4. Hash de senha: `md5()` 🔴
5. Cria/atualiza `Vendedor` com defaults: `pode_cancelar_tempo='00:00:00'`, `pode_cancelar_qtde=0`
6. Adiciona grupo: `SystemGroup::VENDEDOR_GROUP_ID`

### Permissões do Vendedor (campos de controle)

| Campo | Valores | Significado |
|---|---|---|
| `pode_cancelar` | S/N | Pode cancelar bilhetes |
| `pode_cancelar_tempo` | HH:MM:SS | Janela de tempo para cancelar |
| `pode_cancelar_qtde` | int | Qtde máx. cancelamentos/dia |
| `pode_pagar` | S/N | Pode registrar pagamento |
| `pode_pagar_outro` | S/N | Pode pagar bilhete de outro |
| `pode_reimprimir` | S/N | Pode reimprimir bilhete |
| `pode_reimprimir_qtde` | int | Qtde máx. reimpressões |
| `pode_reimprimir_tempo` | HH:MM:SS | Janela de tempo para reimprimir |
| `exibe_comissao` | S/N | Mostra comissão no app |
| `exibe_premiacao` | S/N/U | Mostra prêmio (U=Último) |

---

## Módulo: area-cotacao

**Arquivo principal:** `app/model/entities/AreaCotacao.php`, `app/control/area-cotacao/AreaCotacaoForm.php`
**Tabela:** `cfg_area_cotacao` · **PK:** `area_cotacao_id` (max)

### Lógica de Controle

**AreaCotacaoForm::onSave** — Valida unicidade por `(area_id, extracao_id, modalidade_id)`. `extracao_id` pode ser NULL (cotação global para qualquer extração da área). 🟢

### Regras de Negócio

- Chave única: `(area_id, modalidade_id, extracao_id)` — mesmo registro não pode ser inserido duas vezes. 🟢
- `extracao_id` é opcional: NULL significa "vale para todas as extrações". 🟢
- `multiplicador` sobrescreve o multiplicador da modalidade para esta área. 🟢
- No REST (`ModalidadeRestService`): usa `COALESCE(ac.multiplicador, m.multiplicador)` — cotação específica prevalece sobre a padrão. 🟢

---

## Módulo: area-extracao

**Arquivo principal:** `app/model/entities/AreaExtracao.php`, `app/control/area-extracao/AreaExtracaoList.php`
**Tabela:** `cfg_area_extracao` · **PK:** `area_extracao_id` (max)

### Lógica de Controle (Diferenciada)

**AreaExtracaoList** não herda de `TStandardList` — é uma tela customizada de configuração matricial. 🟢

**onLoadAreaExtracoes** — SQL customizado com LEFT JOIN: lista **todas** as extrações ativas e verifica quais estão ativas para a área selecionada. Usa `CASE WHEN ae.ativo IS true` para mapear boolean PostgreSQL. 🟢

**onToggleStatus** — Toggle on/off:
- Ativar: cria novo `AreaExtracao` ou atualiza `ativo = true`
- Desativar: **deleta o registro** (`$existing->delete()`) — não marca como inativo. 🟢

**checkUserPermissions** — Se o usuário logado é um Gerente (verificado via `cad_coletor.usuario_id`), filtra automaticamente apenas a área do gerente. 🟡

### Regras de Negócio

- Ativar extração para área = cria ou atualiza registro em `cfg_area_extracao`. 🟢
- Desativar = remove o registro (não soft-delete). 🟢
- Gerentes veem apenas sua área. 🟡

---

## Módulo: area-limite

**Arquivo principal:** `app/model/entities/AreaLimite.php`, `app/control/area-limite/AreaLimiteForm.php`
**Tabela:** `cfg_area_limite` · **PK:** `area_limite_id` (max)

### Lógica de Controle

**AreaLimiteForm::onSave** — Valida unicidade de `(area_id, modalidade_id)` em novas inserções. 🟢

### Regras de Negócio

- Limite de palpite por área/modalidade sobrescreve o limite global da modalidade. 🟢
- No REST: `COALESCE(al.limite_palpite, m.limite_palpite)` — limite da área prevalece. 🟢
- Unicidade: uma área pode ter no máximo um limite por modalidade. 🟢

---

## Módulo: AreaComissaoModalidade

**Arquivo principal:** `app/model/entities/AreaComissaoModalidade.php`, `app/control/AreaComissaoModalidade/AreaComissaoModalidadeForm.php`
**Tabela:** `cfg_area_comissao_modalidade` · **PK:** `area_comissao_modalidade_id` (max)

### Lógica de Controle

**AreaComissaoModalidadeForm::onSave:**
- Valida unicidade de `(area_id, modalidade_id)` em novas inserções. 🟢
- Valida faixa: `comissao >= 0 && comissao <= 100`. 🟢

### Regras de Negócio

- Comissão em % (0-100). 🟢
- Uma área pode ter no máximo uma comissão por modalidade. 🟢
- Máscara de entrada: `99,99` (2 casas decimais). 🟢

---

## Módulo: ExtracaoDescarga

**Arquivo principal:** `app/model/entities/ExtracaoDescarga.php`, `app/control/ExtracaoDescarga/ExtracaoDescargaForm.php`
**Tabela:** `cfg_extracao_descarga` · **PK:** `extracao_descarga_id` (max)

### Lógica de Controle

**ExtracaoDescargaForm::onSave:**
1. Valida obrigatoriedade de extração, modalidade, limite.
2. Valida `limite_descarga > 0`. 🟢
3. Valida unicidade de `(extracao_id, modalidade_id)`. 🟢
4. Converte decimal: `str_replace(',', '.', $data->limite_descarga)`. 🟢
5. Salva valor já convertido para float. 🟢

### Regras de Negócio

- Descarga = limite máximo de apostas em um único número para evitar concentração de risco. 🟡
- Uma extração pode ter no máximo um limite de descarga por modalidade. 🟢
- Valor deve ser maior que zero. 🟢

---

## Módulo: palpite-cotado

**Arquivo principal:** `app/model/entities/PalpiteCotado.php`, `app/control/palpite-cotado/PalpiteCotadoForm.php`
**Tabela:** `cfg_palpite_cotado` · **PK:** `palpite_cotado_id` (max)

### Lógica de Controle

**PalpiteCotadoForm::onSave:**
1. Valida `strlen($palpite) == 4` — palpite deve ter exatamente 4 dígitos. 🟢
2. Valida `is_numeric($palpite)`. 🟢
3. Valida `cotacao >= 0 && cotacao <= 100`. 🟢
4. Valida unicidade de `(modalidade_id, palpite)`. 🟢
5. Converte cotação de string decimal para float. 🟢

### Regras de Negócio

- Palpite = número de 4 dígitos específico (ex: "1234"). 🟢
- Cotação especial sobrescreve a cotação padrão da modalidade para este número. 🟢
- Filtro de modalidades: apenas ativas e com multiplicador configurado. 🟢
- No REST: `ModalidadeRestService::getPalpitesCotados()` retorna lista de palpites cotados por modalidade. 🟢

---

## Módulo: parametros

**Arquivo principal:** `app/model/entities/Parametros.php`, `app/control/parametros/ParametrosForm.php`
**Tabela:** `cfg_parametros` · **PK:** `parametros_id` (max)

### Lógica de Controle

**ParametrosForm::onSave:**
1. Valida faixas: `qtde_num_mi` (4-10), `qtde_num_ci` (3-10), `qtde_num_mci` (4-10). 🟢
2. Bloqueia múltiplos registros: `WHERE parametros_id > 0 LIMIT 1`. 🟢
3. Ao abrir sem `key`: carrega automaticamente o registro existente. 🟢

### Feature Flags (campos ativo_*)

| Campo | Feature |
|---|---|
| `ativo_jb` | Jogo do Bicho (padrão) |
| `ativo_bilhetinho` | Bilhetinho |
| `ativo_modalidade` | Modalidades |
| `ativo_instantaneo` | Extração instantânea |
| `ativo_quininha` | Quininha |
| `ativo_seninha` | Seninha |
| `ativo_lotinha` | Lotinha |
| `ativo_milharpremiada` | Milhar Premiada |

### Regras de Negócio

- Singleton: apenas um registro de parâmetros é permitido. 🟢
- `nome_banca` obrigatório. 🟢
- Inclusão de prêmios: `sena_inc_quina`, `sena_inc_quadra`, etc. controlam se acertos menores ganham prêmio. 🟡

---

## Módulo: resultado

**Arquivo principal:** `app/model/entities/MovSorteio.php`, `app/control/resultado/ResultadoForm.php`
**Tabela:** `mov_sorteio` · **PK:** `sorteio_id` (max)

### Lógica de Controle

**ResultadoForm::onEdit** — Carrega sorteio, desabilita campos de prêmio além de `premiacao_maxima` da extração. Controla acesso por situação ('A' = editável, 'F' = somente leitura). 🟢

**Algoritmo chkGrupoDescricao (JavaScript):**
- Extrai os 2 últimos dígitos do número (`dezmilhar`).
- Se `dezmilhar = 0` → usa 100.
- Mapeia `dezmilhar` para grupo (1-25) e animal do Jogo do Bicho. 🟢
- Tabela: 25 grupos, 4 dezenas cada (ex: 1-4=Avestruz, 5-8=Águia, ..., 97-100=Vaca).

**ResultadoForm::onSave:**
- Verifica `situacao == 'A'` antes de salvar (sorteio deve estar aberto). 🟢
- Monta `numeros_sorteados` a partir do campo texto ou dos campos individuais. 🟢
- Formato: vírgula-separado ("1234,5678,..."). 🟢

**ResultadoForm::onConfirmCloseDraw:**
- Exige `numeros_sorteados` não vazio. 🟢
- Seta `situacao = 'F'` (Fechado). 🟢
- Trigger de banco `trg_mv_sorteio_verifica_ganhadores` calcula premiados após encerramento. 🟡

### Regras de Negócio

- Sorteios são criados automaticamente pelo trigger `trg_mv_cad_extracao_cria_sorteios`. 🟡
- Situação: A = Aberto, F = Fechado/Encerrado. 🟢
- Sorteio encerrado não pode ser modificado. 🟢
- Encerramento é irreversível (confirmação obrigatória). 🟢
- **TODO:** Verificar hora_limite antes de permitir encerramento. 🔴

### Algoritmo de Grupos do Jogo do Bicho

```
Grupo = ceil(dezmilhar / 4), onde dezmilhar = int(últimos 2 dígitos do milhar)
Exceção: 00 → dezmilhar = 100
```

25 grupos (01-Avestruz a 25-Vaca), cada um com 4 dezenas consecutivas.

---

## Módulo: rest-api

**Endpoint:** `http://localhost/rest.php`
**Autenticação:** Bearer JWT (HS256, TTL 1h) ou Basic (API key estática)

### ApplicationAuthenticationRestService

| Método | Fluxo |
|---|---|
| `login` | Autentica via `ApplicationAuthenticationService::authenticate()`, gera JWT com `userid`, `user`, `username`, `usermail`, `issued_at`, `expires`. TTL = 1h. |
| `validateToken` | Decodifica JWT, verifica `expires < time()`. Retorna dados do usuário. |
| `refreshToken` | Decodifica token atual (mesmo expirado?), emite novo com TTL +1h. |
| `logout` | Soft logout: registra `SystemAccessLogService::registerLogout()` se token válido. |

**Chave JWT:** `APPLICATION_NAME + $ini['general']['seed']` (configurável em `application.php`). 🟢

### BilheteRestService::registrar

Fluxo de registro de bilhete (mais complexo do sistema):

1. Extrai `usuario_id` do `_auth` injetado pelo roteador.
2. Valida `terminal_id` → deve pertencer ao vendedor e estar ativo.
3. Para cada jogo: valida `sorteio_id` (aberto, na área, dentro do hora_limite), valida `limite_palpite` (`COALESCE(area_limite, modalidade_limite)`).
4. Acumula `totalBilhete`, valida `limite_venda` do vendedor.
5. Gera `bilhete_numero` = `MAX(bilhete_numero) + 1` por vendedor/dia.
6. Gera `string_autorizacao` = `ymd + 6 chars de md5(uniqid)`.
7. Insere: `mov_jb` (cabeçalho), `mov_jb_sorteio` (por sorteio×modalidade), `mov_jb_sort_palpite` (por palpite).
8. Prêmios e comissão zeramos — triggers de banco calculam após sorteio.

### BilheteRestService::cancelar

1. Verifica `pode_cancelar == 'S'`.
2. Verifica `cancelado != 'S'` (já cancelado).
3. Se `pode_cancelar_tempo != '00:00:00'`: calcula `diff_segundos` entre agora e `data_hora` do bilhete.
4. Se `pode_cancelar_qtde > 0`: conta cancelamentos do dia (`data_cancelamento::date = CURRENT_DATE`).
5. Seta `cancelado='S'`, `data_cancelamento=now()`, `cancelado_motivo`.

### SorteioRestService::abertos

SQL com JOINs: `mov_sorteio ↔ cad_extracao ↔ cfg_area_extracao`. Filtra por:
- `situacao = 'A'`
- `data_sorteio = CURRENT_DATE`
- `hora_limite > CURRENT_TIME`
- `ae.area_id = $area_id AND ae.ativo = true`

Calcula `minutos_restantes` e flag `urgente` (≤ 30 min). 🟢

### ModalidadeRestService::disponiveis

SQL complexo que une: `cad_modalidade ↔ int_jogo ↔ cfg_area_cotacao ↔ cfg_area_limite ↔ cfg_extracao_modalidade`. Usa `COALESCE` para cotação e limite específicos da área. Agrupa por `descricao_grupo` para UI. Injeta palpites cotados por modalidade. 🟢

**Lacuna:** referencia `cfg_extracao_modalidade` que não tem Active Record no PHP. 🔴

### CaixaRestService::resumo

Três queries separadas:
1. Totais de venda/comissão/cancelamento do dia.
2. Total de prêmios pagos.
3. Totais por extração (agrupado).

Calcula `saldo_liquido = total_vendido - total_cancelado - total_premios_pagos`. 🟢

### ResultadoRestService::recentes

Retorna sorteios com situação 'F' dos últimos N dias (padrão 3). Calcula grupo do animal via `ceil(int(últimos 2 dígitos) / 4)`. Retorna máximo 50 resultados. 🟢

---

## Padrões Comuns Identificados

### Validações de Unicidade

Todos os módulos de configuração (`area-cotacao`, `area-limite`, `AreaComissaoModalidade`, `ExtracaoDescarga`, `palpite-cotado`) implementam validação de duplicata antes do insert via `getObjects(criteria)`. 🟢

### Soft Delete vs Hard Delete

- `AreaExtracao`: hard delete ao desativar. 🟢
- Demais entidades: soft delete via campo `ativo = 'N'`. 🟢

### Hash de Senha

MD5 usado em GerenteForm e VendedorForm. 🔴 (vulnerabilidade de segurança)

### Transações

Todos os métodos que modificam banco usam `TTransaction::open('permission')` / `TTransaction::close()` / `TTransaction::rollback()` em catch. 🟢

### Filtro filtro_banca

Campo `filtro_banca = 1` presente em `cad_extracao` e `int_jogo`. Filtra registros específicos da banca vs. registros genéricos. 🟡

---

## Bugs e TODOs Identificados

| Local | Tipo | Descrição |
|---|---|---|
| `GerenteForm:172` | Bug | Typo: `'form_ferente'` deve ser `'form_gerente'` |
| `ExtracaoForm:180` | Bug | Typo: `$this->form->cleat()` deve ser `clear()` |
| `GerenteForm`, `VendedorForm` | Segurança 🔴 | MD5 para hash de senha (inseguro — deve usar bcrypt) |
| `ResultadoForm` | TODO | Verificar `hora_limite` antes de salvar resultado |
| `ModalidadeRestService` | Lacuna 🔴 | Referência a `cfg_extracao_modalidade` sem Active Record |
| `GerenteForm::onSave` | Incompleto | Não sincroniza status SystemUser ao salvar (só no onTurnOnOff) |
