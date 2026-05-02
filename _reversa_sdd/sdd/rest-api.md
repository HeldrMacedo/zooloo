# REST API — SDD (Software Design Document)

> Gerado pelo Reversa Writer em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA

---

## Visão Geral

A REST API do Zooloo serve exclusivamente o **aplicativo móvel** dos vendedores. O endpoint base é `rest.php`, e todas as requisições são roteadas via parâmetros `class` e `method`. Autenticação é feita por **JWT HS256** com validade de 1 hora — o token é enviado como parâmetro `token` nas requisições protegidas. O framework Adianti injeta os dados decodificados do JWT em `$param['_auth']`.

### Serviços implementados

| Serviço | Classe | Propósito |
|---|---|---|
| Autenticação | `ApplicationAuthenticationRestService` | Login, validação, refresh e logout de JWT |
| Sorteios | `SorteioRestService` | Sorteios abertos disponíveis para o vendedor |
| Modalidades | `ModalidadeRestService` | Modalidades disponíveis com cotações e limites |
| Bilhete | `BilheteRestService` | Registrar, cancelar, detalhar e listar bilhetes JB |
| Vendedor | `VendedorRestService` | Perfil e permissões do vendedor autenticado |
| Resultado | `ResultadoRestService` | Resultados recentes dos sorteios da área |
| Caixa | `CaixaRestService` | Resumo financeiro do dia do vendedor |
| Terminal | `TerminalRestService` | Registro/atualização do dispositivo (upsert) |

### Convenções de chamada

```
GET/POST rest.php?class=XxxRestService&method=nomeMetodo&token=JWT_TOKEN
          &param1=valor1&param2=valor2
          (corpo JSON em 'data' para POST)
```

Todos os serviços de negócio exigem `token` válido. O Adianti valida o token e injeta:
```php
$param['_auth']['id']   // usuario_id (SystemUser.id)
$param['_auth']['user'] // login
```

---

## 1. ApplicationAuthenticationRestService

### 1.1 `login`

**Parâmetros:** `data.login` (string), `data.password` (string)

**Fluxo:**
1. Valida presença de login e password
2. `ApplicationAuthenticationService::authenticate(login, password, false)` — autenticação padrão Adianti
3. Verifica `user->active === 'Y'`
4. Monta payload JWT: `user`, `userid`, `username`, `usermail`, `issued_at`, `expires = +1 hora`
5. `JWT::encode($payload, APPLICATION_NAME . $seed, 'HS256')`
6. Registra login via `SystemAccessLogService::registerLogin()` (se classe existir)

**Resposta (sucesso):**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "user": { "id": 1, "login": "vendedor1", "name": "João", "email": "...", "active": "Y" },
  "token": "eyJ...",
  "expires_at": "2026-04-30 15:00:00"
}
```

**Chave JWT:** `APPLICATION_NAME + seed` (seed em `app/config/application.php`) 🟢

### 1.2 `validateToken`

**Parâmetros:** `token` (string)

Decodifica o JWT e verifica `expires < time()`. Retorna dados do usuário se válido.

> **Atenção:** A verificação de expiração é feita manualmente (`$decoded['expires'] < time()`), não pela biblioteca JWT. A biblioteca JWT tem sua própria verificação de `exp` claim, mas o payload usa `expires` como campo customizado — não é o claim padrão `exp`. 🟡

### 1.3 `refreshToken`

**Parâmetros:** `token` (string — pode estar expirado)

Decodifica o token atual via `JWT::decode` e emite novo token com `expires = +1 hora`.

> **Risco de segurança:** Não verifica se o token original está dentro do prazo de validade antes de fazer o refresh. Um token expirado pode ser renovado indefinidamente enquanto permanecer decodificável. 🔴

### 1.4 `logout`

**Parâmetros:** `token` (string — opcional)

Registra logout via `SystemAccessLogService::registerLogout()`. Não invalida o token — a API é **stateless**; tokens expiram pelo prazo de validade. 🟡

### 1.5 `getToken` (legado)

Alias para `login`, retorna apenas o token string. Mantido para compatibilidade. 🟡

---

## 2. SorteioRestService

### 2.1 `abertos`

**Parâmetros:** `token`

Retorna sorteios abertos do dia atual disponíveis para a área do vendedor autenticado.

**Query:**
```sql
SELECT ms.sorteio_id, ms.sorteio_numero, ms.data_sorteio, ms.hora_sorteio, ms.situacao,
       e.extracao_id, COALESCE(e.descricao_mobile, e.descricao) AS extracao_descricao,
       e.hora_limite, e.extracao_instantanea
FROM mov_sorteio ms
JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
JOIN cfg_area_extracao ae ON ae.extracao_id = e.extracao_id
    AND ae.area_id = :area_id AND ae.ativo = true
WHERE ms.situacao = 'A'
  AND e.ativo = 'S'
  AND ms.data_sorteio = CURRENT_DATE
  AND e.hora_limite > CURRENT_TIME
ORDER BY e.hora_limite ASC
```

**Campos calculados:**
- `minutos_restantes` — minutos até `hora_limite` (0 se já passou)
- `urgente` — `true` se `minutos_restantes <= 30`

**Resposta (array de sorteios):**
```json
[
  {
    "sorteio_id": 42,
    "sorteio_numero": 10,
    "data_sorteio": "2026-04-30",
    "hora_limite": "18:00:00",
    "extracao_descricao": "Federal",
    "extracao_instantanea": "N",
    "minutos_restantes": 120,
    "urgente": false
  }
]
```

---

## 3. ModalidadeRestService

### 3.1 `disponiveis`

**Parâmetros:** `token`, `sorteio_id`

Retorna modalidades disponíveis para um sorteio na área do vendedor, com cotações e limites aplicados.

**Query (resumida):**
```sql
SELECT m.modalidade_id, m.apresentacao, m.ordem,
       j.jogo_id, j.descricao_grupo, j.tamanho_max, j.qtd_colocacao_premio,
       j.informar_valores_modalidade, j.orientacao,
       COALESCE(ac.multiplicador, m.multiplicador) AS multiplicador,
       COALESCE(al.limite_palpite, m.limite_palpite) AS limite_palpite
FROM cad_modalidade m
JOIN int_jogo j ON j.jogo_id = m.jogo_id
LEFT JOIN cfg_area_cotacao ac ON ac.modalidade_id = m.modalidade_id
    AND ac.area_id = :area_id
    AND (ac.extracao_id = :extracao_id OR ac.extracao_id IS NULL)
LEFT JOIN cfg_area_limite al ON al.modalidade_id = m.modalidade_id
    AND al.area_id = :area_id
WHERE m.ativo = 'S' AND j.ativo = 'S'
  AND EXISTS (
      SELECT 1 FROM cfg_extracao_modalidade em
      WHERE em.extracao_id = :extracao_id AND em.modalidade_id = m.modalidade_id
  )
ORDER BY j.descricao_grupo, m.ordem
```

> **Tabela não documentada:** `cfg_extracao_modalidade` — vincula modalidades a extrações; não tem entidade PHP nem tela de gerenciamento no Zooloo atual. 🔴

**Palpites cotados:** Para cada `modalidade_id` retornado, busca `cfg_palpite_cotado WHERE ativo='S'` e injeta array `palpites_cotados`.

**Resposta (agrupada por `descricao_grupo`):**
```json
[
  {
    "grupo": "Milhar",
    "modalidades": [
      {
        "modalidade_id": 1,
        "apresentacao": "MIL",
        "multiplicador": 6000.0,
        "limite_palpite": 500.0,
        "tamanho_max": 4,
        "qtd_colocacao_premio": 5,
        "informar_valores_modalidade": "N",
        "palpites_cotados": [
          { "palpite": "0001", "cotacao": 15.0 }
        ],
        "mult_col_01": 6000.0,
        "mult_col_02": 3000.0
      }
    ]
  }
]
```

**COALESCE de cotação:** `cfg_area_cotacao.multiplicador` prevalece sobre `cad_modalidade.multiplicador`. Cotação por extração específica prevalece sobre cotação global (extracao_id IS NULL). 🟢

**COALESCE de limite:** `cfg_area_limite.limite_palpite` prevalece sobre `cad_modalidade.limite_palpite`. 🟢

> **Divergência com SDD area-limite:** Neste serviço o COALESCE é `(cfg_area_limite.limite_palpite, cad_modalidade.limite_palpite)` — não usa `cfg_parametros.limite_global` como descrito em SDD área-limite. A documentação anterior do BilheteRestService (inferida) estava incorreta quanto ao uso de `limite_global` neste contexto. 🔴

---

## 4. BilheteRestService

### 4.1 `registrar`

**Parâmetros:** `token`, `data.terminal_id`, `data.nome_cliente?`, `data.fone_cliente?`, `data.jogos[]`

**Estrutura `jogos[]`:**
```json
{
  "sorteio_id": 42,
  "modalidade_id": 1,
  "palpites": ["1234", "5678"],
  "colocacao_inicial": 1,
  "colocacao_final": 5,
  "valor_palpite": 2.00
}
```

**Validações (em ordem):**
1. `usuario_id` autenticado
2. `terminal_id` pertence ao vendedor e `ativo='S'`
3. Para cada jogo: sorteio aberto + extração ativa na área + `hora_limite > CURRENT_TIME`
4. Para cada jogo: `COALESCE(cfg_area_limite.limite_palpite, cad_modalidade.limite_palpite)` — rejeita se `valor_palpite > limite`
5. Total do bilhete: `valor_palpite × qtde_palpites` por jogo — rejeita se `total > vendedor.limite_venda`

**Estrutura de inserção:**
```
mov_jb (1 registro — cabeçalho)
  └── mov_jb_sorteio (1 por sorteio × modalidade)
        └── mov_jb_sort_palpite (1 por número apostado)
               ├── jogou_colocacao_01..10 — S/N por colocação jogada
               └── premio_colocacao_01..05 — zerados (triggers preenchem após sorteio)
```

**Campos gerados:**
- `bilhete_numero` — `MAX(bilhete_numero) + 1` para o vendedor no dia corrente
- `string_autorizacao` — `date('ymd') + 6 chars de md5(uniqid())` ex: `"2604301a2b3c"`
- `comissao_valor = 0` — triggers/jobs calculam posteriormente 🟡

**Resposta:**
```json
{
  "jb_id": 123,
  "bilhete_numero": 5,
  "string_autorizacao": "26043012ab3c",
  "total_bilhete": 10.00,
  "data_hora": "2026-04-30 14:30:00",
  "vendedor_nome": "João"
}
```

### 4.2 `cancelar`

**Parâmetros:** `token`, `bilhete_id`, `data.motivo?`

**Validações:**
1. `vendedor.pode_cancelar === 'S'`
2. `jb.vendedor_id === vendedor.vendedor_id` — bilhete pertence ao vendedor
3. `jb.cancelado !== 'S'` — ainda não cancelado
4. Se `vendedor.pode_cancelar_tempo` definido: `agora - data_bilhete <= limite_segundos`
5. Se `vendedor.pode_cancelar_qtde > 0`: conta cancelamentos do dia atual

**Resultado:** seta `cancelado='S'`, `data_cancelamento`, `cancelado_motivo`.

### 4.3 `detalhe`

**Parâmetros:** `token`, `bilhete_id`, `reimprimir?` (0 ou 1)

Verifica `jb.vendedor_id === vendedor.vendedor_id`. Se `reimprimir=1`: checa `pode_reimprimir='S'` e `reimpressao < pode_reimprimir_qtde`; incrementa `reimpressao` e grava `data_reimpressao`.

Retorna bilhete completo com array `sorteios` incluindo `palpites` (explode por `,`), `sorteado`, `sorteado_valor`, `previsao_premio`.

### 4.4 `lista`

**Parâmetros:** `token`, `data_inicio?`, `data_fim?`, `situacao?` (todos|ativos|cancelados), `pagina?`, `por_pagina?` (10–50, padrão 20)

Retorna lista paginada dos bilhetes do vendedor no período. `total` para paginação.

---

## 5. VendedorRestService

### 5.1 `me`

**Parâmetros:** `token`

Retorna perfil completo do vendedor: dados cadastrais + permissões operacionais.

**Resposta:**
```json
{
  "vendedor_id": 1,
  "nome": "João Silva",
  "area_id": 2,
  "area_descricao": "Zona Sul",
  "comissao": 5.0,
  "limite_venda": 500.0,
  "pode_cancelar": "S",
  "pode_cancelar_tempo": "00:30:00",
  "pode_cancelar_qtde": 5,
  "pode_pagar": "N",
  "pode_reimprimir": "S",
  "pode_reimprimir_qtde": 3,
  "treinamento": "N"
}
```

---

## 6. ResultadoRestService

### 6.1 `recentes`

**Parâmetros:** `token`, `dias?` (1–30, padrão 3), `extracao_id?`

Retorna até 50 sorteios encerrados (`situacao='F'`) da área do vendedor nos últimos N dias. Para cada número sorteado calcula grupo e descrição do animal.

> **Bug:** Cálculo `ceil((int) substr($milhar, -2) / 4)` resulta em 0 quando `dezmilhar=00`, sem tratar o caso especial `00→100→grupo 25`. Diferente da implementação correta em `ResultadoForm.php`. 🔴

**Resposta:**
```json
[
  {
    "sorteio_id": 40,
    "data_sorteio": "2026-04-29",
    "extracao_descricao": "Federal",
    "numeros": [
      { "posicao": 1, "numero": "1234", "grupo": 9, "grupo_descricao": "Cobra" },
      { "posicao": 2, "numero": "5600", "grupo": 0, "grupo_descricao": "" }
    ]
  }
]
```

---

## 7. CaixaRestService

### 7.1 `resumo`

**Parâmetros:** `token`, `data?` (padrão: hoje, formato `Y-m-d`)

**Queries:**
1. Totais gerais: `COUNT(*)`, `SUM(total_bilhete)`, `SUM(comissao_valor)` + cancelados
2. Prêmios pagos: `SUM(sorteado_valor_pago) WHERE sorteado_pago='S'`
3. Breakdown por extração (agrupa por `extracao_id`)

**Campos calculados:**
- `total_liquido = total_vendido - total_cancelado`
- `saldo_liquido = total_vendido - total_cancelado - total_premios_pagos`

**Resposta:**
```json
{
  "data": "2026-04-30",
  "qtde_bilhetes": 12,
  "qtde_cancelados": 1,
  "total_vendido": 240.00,
  "total_cancelado": 20.00,
  "total_liquido": 220.00,
  "total_comissao": 11.00,
  "total_premios_pagos": 60.00,
  "saldo_liquido": 160.00,
  "por_extracao": [
    { "extracao_descricao": "Federal", "qtde_bilhetes": 8, "total_vendido": 160.00 }
  ]
}
```

---

## 8. TerminalRestService

### 8.1 `registrar`

**Parâmetros:** `token`, `data.serial`, `data.tipo?` (padrão: `"APP"`)

**Lógica upsert:**
- Busca `cad_terminal WHERE vendedor_id = X AND serial = Y`
- Se existe: atualiza `tipo` e `ativo='S'`
- Se não existe: cria com `multi_usuario='N'`, `ativo='S'`

Retorna `terminal_id`, `vendedor_id`, `serial`, `tipo`.

---

## Padrões Arquiteturais da API

### Autenticação JWT

```
Header: nenhum (token via query string ou body)
Algoritmo: HS256
Chave: APPLICATION_NAME + seed (ambos em app/config/application.php)
Validade: 1 hora (campo customizado 'expires', não claim padrão 'exp')
```

### Padrão de Autorização por Vendedor

Todos os serviços de negócio seguem o mesmo padrão:
```php
$usuario_id = $param['_auth']['id'] ?? null;
if (!$usuario_id) throw new Exception('Usuário não autenticado');

$vendedor = Vendedor::find(usuario_id + ativo='S');
$area_id = $vendedor->area_id;
// usa area_id para filtrar dados
```

### Tratamento de Erros

Todos os métodos têm try/catch:
- Em caso de exception: `TTransaction::rollback()` + `throw $e` (a camada HTTP do Adianti formata a resposta de erro)
- Erros retornam objeto com `success: false, message: ...` (apenas no serviço de Auth que retorna diretamente)

### Segurança

| Aspecto | Implementação | Confiança |
|---|---|---|
| Autenticação | JWT HS256, 1 hora | 🟢 |
| Autorização | Vendedor só acessa seus próprios dados | 🟢 |
| Refresh sem re-auth | Token expirado pode ser renovado via refreshToken | 🔴 |
| Logout stateless | Token não é invalidado — apenas log | 🟡 |
| string_autorizacao | date + md5(uniqid) — não criptograficamente seguro | 🟡 |

---

## Dependências

| Componente | Relação |
|---|---|
| `firebase/php-jwt` | Encode/decode JWT |
| `AdiantiRestService` | Interface implementada por `ApplicationAuthenticationRestService` |
| `Vendedor` (Active Record) | Resolução de `usuario_id → vendedor + area_id` em todos os serviços |
| `MovJb`, `MovJbSorteio`, `MovJbSortPalpite` | Entidades de bilhete (não têm formulários no Zooloo) |
| `Terminal` | Entidade de terminal (não tem formulário no Zooloo) |
| `cfg_extracao_modalidade` | Tabela sem entidade PHP — filtra modalidades por extração 🔴 |
| `TTransaction` (banco `permission`) | Todas as operações |

---

## Tabelas Sem Entidade PHP / Tela

| Tabela | Uso | Status |
|---|---|---|
| `mov_jb` | Cabeçalho do bilhete JB | Sem Form/List no Zooloo |
| `mov_jb_sorteio` | Detalhe por sorteio | Sem Form/List no Zooloo |
| `mov_jb_sort_palpite` | Detalhe por palpite | Sem Form/List no Zooloo |
| `cad_terminal` | Terminais dos vendedores | Sem Form/List no Zooloo |
| `cfg_extracao_modalidade` | Modalidades por extração | Sem Form/List no Zooloo 🔴 |
| `mov_caixa` | Caixa do vendedor | Não usado ainda — CaixaRestService usa mov_jb direto |

---

## Critérios de Aceitação

```gherkin
# Happy path — login
Dado que vendedor1/senha123 é usuário ativo
Quando POST rest.php?class=ApplicationAuthenticationRestService&method=login {data: {login, password}}
Então retorna success:true, token JWT válido por 1 hora

# Falha — login inválido
Dado que senha123 está errada
Quando tenta login
Então retorna success:false, message:'Credenciais inválidas'

# Happy path — listar sorteios abertos
Dado que vendedor está autenticado e sua área tem extração ativa com hora_limite às 18:00
Quando GET rest.php?class=SorteioRestService&method=abertos&token=JWT
Então retorna array de sorteios com minutos_restantes e urgente=false

# Happy path — registrar bilhete
Dado que sorteio_id=42 está aberto para a área do vendedor e valor_palpite <= limite
Quando POST registrar com jogos[modalidade_id=1, palpites=["1234"], valor_palpite=2.00]
Então retorna jb_id, bilhete_numero, string_autorizacao, total_bilhete=2.00

# Falha — valor acima do limite
Dado que cfg_area_limite.limite_palpite=5.00 para a área+modalidade
Quando tenta registrar com valor_palpite=10.00
Então exceção "Valor R$ 10 excede o limite de R$ 5 para esta modalidade"

# Happy path — cancelar bilhete
Dado que vendedor tem pode_cancelar='S' e bilhete tem 15 minutos (dentro do tempo)
Quando POST cancelar com bilhete_id=123
Então jb.cancelado='S', data_cancelamento preenchida

# Falha — cancelar além do tempo
Dado que pode_cancelar_tempo='00:10:00' e bilhete tem 20 minutos
Quando tenta cancelar
Então exceção "Tempo limite para cancelamento esgotado"

# Bug — grupo "00" no resultado
Dado que numeros_sorteados inclui "1200" (dezmilhar=00)
Quando ResultadoRestService.recentes processa
Então grupo calculado é 0 (bug — deveria ser 25 VACA)
```

---

## Prioridade

| Responsabilidade | MoSCoW | Justificativa |
|---|---|---|
| Login JWT | Must | Sem auth, app não funciona |
| Sorteios abertos | Must | Vendedor precisa saber o que pode vender |
| Modalidades disponíveis | Must | Base para montar o bilhete |
| Registrar bilhete | Must | Função principal do app |
| Cancelar bilhete | Must | Operação corretiva |
| Detalhe/reimpressão | Must | Comprovante da venda |
| Lista de bilhetes | Should | Histórico do vendedor |
| Resultados recentes | Should | Consulta dos números sorteados |
| Caixa/resumo financeiro | Should | Controle financeiro do dia |
| Terminal (upsert) | Must | Necessário antes do primeiro bilhete |
| Corrigir bug grupo "00" no ResultadoRestService | Must | Resultado incorreto para VACA (dezmilhar=00) 🔴 |
| Corrigir refreshToken sem verificação de expiração | Should | Risco de segurança 🔴 |
| Documentar cfg_extracao_modalidade | Should | Tabela sem entidade e sem tela de gestão 🔴 |

---

## Rastreabilidade de Código

| Arquivo | Classe / Método | Cobertura |
|---|---|---|
| `app/service/auth/ApplicationAuthenticationRestService.php` | `login`, `validateToken`, `refreshToken`, `logout` | 🟢 |
| `app/service/rest/SorteioRestService.php` | `abertos` | 🟢 |
| `app/service/rest/ModalidadeRestService.php` | `disponiveis`, `getPalpitesCotados` | 🟢 |
| `app/service/rest/BilheteRestService.php` | `registrar`, `cancelar`, `detalhe`, `lista` | 🟢 |
| `app/service/rest/BilheteRestService.php` | `validarSorteioAberto`, `validarLimitePalpite`, `validarLimiteVenda` | 🟢 |
| `app/service/rest/VendedorRestService.php` | `me` | 🟢 |
| `app/service/rest/ResultadoRestService.php` | `recentes` | 🟢 |
| `app/service/rest/CaixaRestService.php` | `resumo` | 🟢 |
| `app/service/rest/TerminalRestService.php` | `registrar` | 🟢 |
