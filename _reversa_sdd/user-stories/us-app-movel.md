# User Stories — Aplicativo Móvel (REST API)

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Módulos cobertos: Autenticação JWT, Apostas JB, Bilhetinho, Sorteios,
> Caixa do Vendedor, Terminal, Perfil do Vendedor

---

## Contexto

O aplicativo móvel consome a REST API do Zooloo via `rest.php` usando o padrão
Adianti: `GET /rest.php?class=XxxRestService&method=yyy`.

**Autenticação:** JWT (HS256), válido por 1 hora, chave = `APPLICATION_NAME + seed`
(seed em `app/config/application.php`). `rest_key` global: `zooloo_api_key_2025`.

**Implementado no Zooloo:**
- `ApplicationAuthenticationRestService` (login/logout/refresh/validate)
- `ModalidadeRestService` (sorteios, modalidades, palpites cotados)
- `BilheteRestService` (registrar aposta JB)
- `VendedorRestService` (perfil)
- `ResultadoRestService` (resultado por sorteio)
- `CaixaRestService` (resumo do caixa)
- `TerminalRestService` (informações do terminal)

---

## US-APP-01: Fazer Login no Aplicativo

**Como** vendedor com acesso ao app  
**Quero** autenticar com meu login e senha  
**Para que** eu possa acessar as funcionalidades de venda e caixa

### Critérios de Aceitação

```gherkin
# Happy path — login com sucesso
Dado que o vendedor tem login="joao" e senha="abc123"
Quando o app envia POST para ApplicationAuthenticationRestService::login
Com body: {login: "joao", password: "abc123", rest_key: "zooloo_api_key_2025"}
Então o servidor valida credenciais no sistema Adianti
E retorna: {token: "eyJ...", expires_at: "2026-05-01 15:30:00", user: {login, nome, ...}}
E o app armazena o token para uso nas próximas requisições

# Happy path — token no header
Dado que o vendedor está autenticado com token "eyJ..."
Quando o app faz qualquer requisição REST subsequente
Então inclui header: Authorization: Bearer eyJ...
E o servidor valida o token antes de processar

# Falha — credenciais inválidas
Quando o app envia login="joao" e senha="errada"
Então servidor retorna 401 {"error": "Credenciais inválidas"}
E o app exibe mensagem de erro para o usuário

# Happy path — renovar token antes de expirar
Dado que o token expira em 5 minutos
Quando o app chama ApplicationAuthenticationRestService::refreshToken
Então um novo token é retornado com novo prazo de 1 hora
E o token antigo deixa de ser válido

# Vulnerabilidade documentada — refreshToken sem validação
Dado que o endpoint refreshToken existe
Então ele não valida se o token atual ainda é válido antes de emitir um novo 🔴
E qualquer token (inclusive expirado) pode obter um novo token via refreshToken

# Happy path — logout
Quando o app chama ApplicationAuthenticationRestService::logout
Então o servidor invalida o token (ou registra o logout) 🟡
E o app limpa o token armazenado localmente

# Vulnerabilidade documentada — logout stateless
Dado que o logout é chamado
Então o servidor não mantém blacklist de tokens — JWT continua válido até expirar 🔴
E o app deve descartar o token localmente (não há invalidação server-side)
```

### Regras de Negócio Relacionadas

- Senha armazenada em MD5 sem salt — migração para bcrypt em andamento (ADR-003) 🟡
- Token JWT HS256, 1 hora de validade 🟢
- `rest_key` global validado em todas as requisições 🟢
- refreshToken sem validação do token atual — lacuna de segurança 🔴 (usa campo `expires` customizado, não `exp` padrão JWT)
- Logout stateless — token permanece válido até expirar 🟢 (confirmado: apenas registra log, sem blacklist)
- Login retorna `expires_at` (datetime) 🟢 — app seguirá padrão JWT (`exp` claim) quando for criado

### Dependências

- `system_users` (Adianti), `app/config/application.php` (seed JWT)
- `app/service/auth/ApplicationAuthenticationRestService.php`

### Prioridade

**Must** — toda funcionalidade do app depende de autenticação.

---

## US-APP-02: Consultar Sorteios e Modalidades Disponíveis

**Como** vendedor autenticado  
**Quero** ver os sorteios abertos e as modalidades disponíveis para aposta  
**Para que** eu possa orientar o apostador e registrar a aposta corretamente

### Critérios de Aceitação

```gherkin
# Happy path — listar sorteios abertos do dia
Quando o app chama ModalidadeRestService::getSorteios
Então recebe lista de sorteios com: sorteio_id, data, hora_limite, extracao, situacao='A'
E somente sorteios abertos do dia atual são retornados
E o app exibe o tempo restante até hora_limite para cada sorteio

# Happy path — listar modalidades por extração e área
Quando o app chama ModalidadeRestService::getModalidades com extracao_id e area_id
Então recebe lista de modalidades disponíveis para aquela extração na área do vendedor
E cada modalidade inclui: cotação efetiva (específica ou global de AreaCotacao)
E palpites especiais de cfg_palpite_cotado com suas cotações próprias

# Regra — cotação específica sobrepõe global
Dado que existe AreaCotacao global (extracao_id=null) com cotacao=4000 para Milhar/Centro
E existe AreaCotacao específica (extracao_id=3) com cotacao=3500 para Milhar/Centro/Tarde
Quando o app consulta modalidades para a extração Tarde/Centro
Então Milhar exibe cotacao=3500 (específica tem prioridade)

# Happy path — listar grupos do Jogo do Bicho
Quando o app chama ModalidadeRestService para grupos
Então recebe os 25 grupos (animais) com seus números correspondentes
E o app usa para exibir o bicho ao apostador ao digitar a milhar

# Happy path — palpites cotados
Dado que palpite "1234" tem cotação especial em cfg_palpite_cotado para Tarde/Milhar
Quando o app digita "1234" para Milhar
Então exibe cotacao especial em vez da cotação padrão da área
```

### Regras de Negócio Relacionadas

- Cotação efetiva = específica (`extracao_id` preenchido) > global (`extracao_id=null`) 🟢
- Apenas extrações em `cfg_area_extracao` da área do vendedor são disponibilizadas 🟢
- `PalpiteCotado` com `ativo=true` sobrepõe cotação padrão 🟢

### Dependências

- `cfg_area_extracao`, `cfg_area_cotacao`, `cfg_palpite_cotado`
- `ModalidadeRestService.php`, `mov_sorteio`, `cad_modalidade`

### Prioridade

**Must** — sem esta consulta o app não sabe o que pode ser apostado.

---

## US-APP-03: Registrar Aposta de Jogo do Bicho

**Como** vendedor autenticado  
**Quero** registrar uma aposta de JB para um apostador  
**Para que** o bilhete seja emitido e a aposta fique registrada no sistema

### Critérios de Aceitação

```gherkin
# Happy path — registrar aposta simples
Dado que o vendedor seleciona sorteio=Tarde, modalidade=Milhar, palpite="1234", valor=10.00
Quando o app envia POST para BilheteRestService::registrar com body:
  {terminal_id: 1, jogos: [{sorteio_id: 123, modalidade_id: 2, palpites: ["1234"], colocacao_inicial: 1, colocacao_final: 5, valor_palpite: 10.00}]}
Então o servidor cria registro em mov_jb e mov_jb_sorteio
E retorna: {jb_id: 123456, bilhete_numero: 789012, string_autorizacao: "ab3f9c...", total_bilhete: 10.00, data_hora: "...", vendedor_nome: "João"}
E `string_autorizacao` é um hash MD5 derivado do jb_id — equivalente ao NSU de comprovante 🟢
E o app exibe o comprovante com bilhete_numero e string_autorizacao

# Happy path — aposta com múltiplos palpites
Dado que o apostador quer apostar "1234" e "5678" na Milhar
Quando o app envia os dois palpites no mesmo bilhete
Então mov_jb tem 1 registro (bilhete)
E mov_jb_sorteio tem 2 registros (um por palpite)

# Falha — valor acima do limite de área
Dado que cfg_area_limite define limite=500.00 para Milhar/Centro
Quando o app tenta registrar aposta de valor=600.00 para Milhar na área Centro
Então servidor retorna erro: "Valor acima do limite configurado para esta modalidade"

# Falha — apostas fora do horário
Dado que hora_limite da extração "Tarde" é 15:30:00 e agora são 15:31:00
Quando o app tenta registrar aposta para a extração Tarde
Então servidor retorna erro: "Extração encerrada para apostas"

# Falha — sorteio não encontrado
Dado que o sorteio informado não existe ou está fechado
Quando o app tenta registrar
Então servidor retorna 404 "Sorteio não disponível"

# Happy path — cancelar aposta (se permitido)
Dado que o vendedor tem pode_cancelar='S' em cad_vendedor
E o bilhete foi criado dentro do tempo limite em pode_cancelar_tempo (HH:MM:SS)
E não atingiu o limite diário em pode_cancelar_qtde
Quando o app chama cancelamento via jb_id
Então mov_jb.cancelado='S' e o bilhete fica indisponível para prêmio

# Falha — tempo de cancelamento esgotado
Dado que pode_cancelar_tempo='00:30:00' e o bilhete foi emitido há 35 minutos
Quando o app tenta cancelar
Então servidor retorna erro: "Tempo limite para cancelamento esgotado"

# Falha — quota diária atingida
Dado que pode_cancelar_qtde=3 e o vendedor já cancelou 3 bilhetes hoje
Quando o app tenta cancelar mais um
Então servidor retorna erro: "Quota de cancelamentos diária atingida"
```

### Regras de Negócio Relacionadas

- Limite de aposta verificado em `cfg_area_limite` 🟢
- Prazo de apostas controlado por `hora_limite` da extração 🟢
- Comissão calculada por `trg_mv_jb_sorteio_comissao` (BEFORE INSERT em `mov_jb_sorteio`) com hierarquia: global área → área+modalidade → modalidade global → área+vendedor → área+modalidade+vendedor → `cad_vendedor.comissao` 🟢
- `previsao_premio` calculada por `trg_mv_jb_sorteio_previsao` (BEFORE INSERT) com lógica de cotação por área/modalidade 🟢
- `pode_cancelar='S'` no perfil do vendedor habilita cancelamento 🟢
- `pode_cancelar_tempo` (HH:MM:SS) define janela de tempo para cancelamento 🟢
- `pode_cancelar_qtde` (int) define limite diário de cancelamentos 🟢

### Dependências

- `BilheteRestService.php`, `mov_jb`, `mov_jb_sorteio`
- `cfg_area_limite`, `cfg_area_cotacao`, `cfg_area_extracao`
- `VendedorRestService.php` (perfil com permissões)

### Prioridade

**Must** — função central do app; sem isso o negócio não opera.

---

## US-APP-04: Registrar Aposta de Bilhetinho

**Como** vendedor autenticado  
**Quero** registrar uma aposta de Bilhetinho  
**Para que** o apostador concorra aos prêmios das 5 colocações do sorteio

### Critérios de Aceitação

```gherkin
# Estado atual — GAP
Dado que o app tenta registrar aposta de Bilhetinho
Quando chama o endpoint REST de Bilhetinho
Então o endpoint não existe no Zooloo PHP (BilhetinhoRestService não implementado) 🔴
E o Java tinha endpoint dedicado para apostas Bilhetinho

# Comportamento esperado (a implementar)
Dado que cfg_parametros.ativo_bilhetinho=true
Quando vendedor seleciona jogo=Bilhetinho, modalidade, palpite, valor
Então aposta é registrada em mov_bilhetinho e mov_bilhetinho_sorteio
E retorna NSU e previsão de prêmio para cada colocação (1ª a 5ª)

# Validação de feature flag
Dado que cfg_parametros.ativo_bilhetinho=false
Quando o app tenta acessar apostas Bilhetinho
Então o jogo não aparece no combo de tipos
```

### Regras de Negócio Relacionadas

- `ativo_bilhetinho` em `cfg_parametros` controla disponibilidade 🟢
- `cad_modalidade_bilhetinho` define multiplicadores por colocação 🟢
- `mov_bilhetinho` e `mov_bilhetinho_sorteio` existem no banco sem entidade PHP 🔴

### Dependências

- `mov_bilhetinho`, `mov_bilhetinho_sorteio` (sem entidade PHP)
- `cad_modalidade_bilhetinho`, `cfg_parametros.ativo_bilhetinho`

### Prioridade

**Must** — necessário para paridade com o Java quando `ativo_bilhetinho=true`.

---

## US-APP-05: Consultar Caixa e Resultado do Vendedor

**Como** vendedor autenticado  
**Quero** visualizar meu resumo de caixa e os resultados do sorteio  
**Para que** eu possa acompanhar meu desempenho e identificar bilhetes premiados

### Critérios de Aceitação

```gherkin
# Happy path — resumo do caixa
Quando o app chama CaixaRestService::resumo
Então recebe: total vendido, comissão, líquido, prêmios pagos no dia
E o app exibe o saldo atualizado do vendedor

# Happy path — resultado do sorteio
Dado que a extração "Tarde" foi encerrada com resultado "1234,5678,9012,3456,7890"
Quando o app chama ResultadoRestService com o sorteio encerrado
Então recebe os números sorteados e lista de bilhetes premiados do vendedor
E o app destaca os bilhetes premiados para o vendedor pagar ao apostador

# Happy path — perfil do vendedor
Quando o app chama VendedorRestService::getPerfil
Então recebe: nome, área, comissao_percentual, permissoes (permite_cancelar, permite_desconto, etc.)
E o app configura as funcionalidades visíveis conforme as permissões

# Happy path — informações do terminal
Quando o app chama TerminalRestService
Então recebe: terminal_id, nome_banca (de cfg_parametros), configurações do terminal
E o app inicializa com as configurações corretas da banca

# Regra — colocações Quininha/Seninha no resultado
Dado que o sorteio inclui Quininha ou Seninha
Quando o app chama ResultadoRestService
Então colocações são retornadas como: QUI/QUA/TER (Quininha) ou SEN/QUI/QUA (Seninha)
```

### Regras de Negócio Relacionadas

- `CaixaRestService` retorna resumo do dia por vendedor 🟢
- `ResultadoRestService` retorna colocações com siglas específicas por tipo de jogo 🟢
- `VendedorRestService` expõe flags de permissão que controlam UI do app 🟢
- `TerminalRestService` inicializa o terminal com `nome_banca` de `cfg_parametros` 🟢

### Dependências

- `CaixaRestService.php`, `ResultadoRestService.php`, `VendedorRestService.php`, `TerminalRestService.php`
- `mov_caixa`, `mov_sorteio`, `cad_vendedor`, `cfg_parametros`

### Prioridade

**Must** — vendedor precisa do caixa para controle financeiro; resultado para pagar prêmios.

---

## Fluxo Típico de uma Sessão do App

```
1. Vendedor abre o app
   └── POST login → recebe JWT

2. App inicializa
   ├── GET TerminalRestService → nome_banca, config
   ├── GET VendedorRestService::getPerfil → permissões
   └── GET ModalidadeRestService::getSorteios → sorteios do dia

3. Para cada aposta
   ├── GET ModalidadeRestService::getModalidades(extracao, area) → modalidades + cotações
   ├── Vendedor seleciona modalidade + digita palpite
   └── POST BilheteRestService::registrar → recebe NSU + comprovante

4. Após o sorteio
   ├── GET ResultadoRestService(sorteio) → números + premiados
   └── GET CaixaRestService::resumo → fechamento do caixa do dia

5. Manutenção do token
   └── GET refreshToken (antes dos 60 min) → novo JWT com mais 60 min
```

---

## Rastreabilidade de Código

| User Story | Service PHP | Spec SDD |
|---|---|---|
| US-APP-01 | `app/service/auth/ApplicationAuthenticationRestService.php` | `sdd/rest-api.md` |
| US-APP-02 | `app/service/rest/ModalidadeRestService.php` | `sdd/rest-api.md` |
| US-APP-03 | `app/service/rest/BilheteRestService.php` | `sdd/rest-api.md` |
| US-APP-04 | (a criar) `app/service/rest/BilhetinhoRestService.php` | `sdd/gap-bilhetinho.md` |
| US-APP-05 | `app/service/rest/CaixaRestService.php` + `ResultadoRestService.php` + `VendedorRestService.php` + `TerminalRestService.php` | `sdd/rest-api.md` |
