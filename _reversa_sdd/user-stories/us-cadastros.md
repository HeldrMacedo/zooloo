# User Stories — Cadastros

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Módulos cobertos: Área, Gerente, Extração, Modalidade, Vendedor

---

## US-CAD-01: Cadastrar Área

**Como** administrador do sistema  
**Quero** cadastrar uma nova área geográfica da banca  
**Para que** eu possa associar vendedores e configurações a ela

### Critérios de Aceitação

```gherkin
# Happy path — criar área
Dado que estou na tela AreaList
Quando clico em "Nova" e preencho descrição="Centro" e complemento="Região Central"
E clico em Salvar
Então um novo registro é criado em cad_area com ativo=true
E a área aparece na listagem

# Happy path — toggle ativo
Dado que a área "Centro" está ativa (ativo=true)
Quando clico no botão de toggle inativo
Então ativo é alterado para false
E a área pode ser reativada pelo mesmo botão

# Falha — descrição obrigatória
Quando salvo sem preencher descrição
Então o formulário exibe validação "Campo obrigatório"
E nenhum registro é criado

# Falha — exclusão com dependentes
Dado que a área "Centro" tem vendedores associados
Quando tento excluir a área
Então o banco bloqueia via FK e exibe mensagem de erro
```

### Regras de Negócio Relacionadas

- RN: Área pode ser ativada/desativada sem exclusão — integridade referencial protege contra DELETE com dependentes 🟢
- RN: `ativo` controla disponibilidade nos combos de filtro de relatórios 🟡

### Dependências

- `cad_vendedor` (FK `area_id`), `cad_coletor` (FK), `cfg_area_extracao`, `cfg_area_cotacao`, `cfg_area_limite`, `cfg_area_comissao_modalidade`

### Prioridade

**Must** — entidade fundacional; todos os outros cadastros dependem dela.

---

## US-CAD-02: Cadastrar Gerente (com usuário do sistema)

**Como** administrador  
**Quero** cadastrar um gerente e criar automaticamente seu acesso ao sistema  
**Para que** o gerente possa fazer login e gerenciar sua área

### Critérios de Aceitação

```gherkin
# Happy path — criar gerente com acesso web
Dado que preencho nome="Maria", área=Centro, login="maria", senha="abc123", acesso_web=true
Quando clico em Salvar
Então um registro é criado em cad_coletor
E um SystemUser é criado com login="maria" e senha hasheada (MD5 sem salt) 🔴
E o usuário aparece na lista de usuários do Adianti

# Happy path — criar gerente sem acesso web
Dado que preencho dados do gerente com acesso_web=false
Quando salvo
Então cad_coletor é criado mas nenhum SystemUser é criado

# Falha — login duplicado
Dado que já existe usuário com login="maria"
Quando tento criar gerente com login="maria"
Então erro "Login já em uso" é exibido

# Happy path — inativar gerente
Dado que o gerente "Maria" está ativo
Quando altero ativo=false e salvo
Então cad_coletor.ativo=false
E TODO: o SystemUser associado deveria ser inativado — não implementado atualmente 🔴

# Correção de typo no formulário
Dado que o campo "Descrição" no GerenteForm está mapeado com typo na variável interna
Então o bug de typo em GerenteForm deve ser corrigido antes do go-live 🔴
```

### Regras de Negócio Relacionadas

- RN: Dual-Entity — um único save cria `cad_coletor` + `SystemUser` na mesma TTransaction 🟢
- RN: Senha armazenada em MD5 sem salt — vulnerabilidade de segurança conhecida (ADR-003) 🔴
- RN: TODO documentado em CLAUDE.md: ao inativar gerente, inativar também o SystemUser 🔴

### Dependências

- `cad_area` (seleção de área), `system_users` (Adianti), `system_groups` (perfil de acesso)

### Prioridade

**Must** — acesso operacional dos gerentes ao sistema depende deste fluxo.

---

## US-CAD-03: Cadastrar Extração (com criação automática de sorteios)

**Como** administrador  
**Quero** cadastrar uma extração definindo dias da semana e hora limite  
**Para que** o sistema crie automaticamente os sorteios para apostas

### Critérios de Aceitação

```gherkin
# Happy path — criar extração
Dado que preencho descrição="Tarde", hora_limite="15:30", segunda=S, quarta=S, sexta=S
E premiacao_maxima=5 e calc_sorteio_id=1
Quando salvo
Então um registro é criado em cad_extracao
E a trigger trg_mv_cad_extracao_cria_sorteios dispara automaticamente
E sorteios são criados em mov_sorteio para as próximas semanas nas datas de segunda/quarta/sexta

# Happy path — alterar dias da semana
Dado que extração "Tarde" tem segunda=S, quarta=S
Quando altero para segunda=S, quarta=S, quinta=S e salvo
Então a trigger recria/atualiza os sorteios incluindo quinta-feira

# Falha — typo em ExtracaoForm
Dado que o campo de hora no ExtracaoForm contém bug de typo
Então o bug de typo em ExtracaoForm deve ser corrigido antes do go-live 🔴

# Falha — descrição obrigatória
Quando salvo sem preencher descrição
Então validação "Campo obrigatório" impede o save

# Happy path — premiacao_maxima controla campos de resultado
Dado que extração "Tarde" tem premiacao_maxima=5
Quando operador acessa tela de resultado desta extração
Então apenas 5 campos de prêmio são habilitados (campos 6–10 desabilitados)
```

### Regras de Negócio Relacionadas

- RN: Dias da semana armazenados como campos `S`/`N` individuais (`seg`, `ter`, `qua`, `qui`, `sex`, `sab`, `dom`) 🟢
- RN: Trigger `trg_mv_cad_extracao_cria_sorteios` cria sorteios automaticamente ao INSERT/UPDATE 🟢
- RN: `premiacao_maxima` (1–10) controla quantos campos de prêmio são exibidos na tela de resultado 🟢
- RN: `calc_sorteio_id` define o algoritmo de resultado (1=manual, 2–6=automático) 🟢

### Dependências

- `mov_sorteio` (criada pela trigger), `int_calculo_sorteio`, `cfg_area_extracao`

### Prioridade

**Must** — sem extrações cadastradas, nenhum sorteio existe e o sistema não opera.

---

## US-CAD-04: Cadastrar Modalidade de Jogo

**Como** administrador  
**Quero** cadastrar uma modalidade de aposta associada a um tipo de jogo  
**Para que** os vendedores possam registrar apostas nessa modalidade

### Critérios de Aceitação

```gherkin
# Happy path — criar modalidade
Dado que seleciono int_jogo="Milhar" e preencho apresentacao="Milhar 4 Dígitos"
E multiplicador=4000, multiplicador_colocacao_01=4000
Quando salvo
Então um registro é criado em cad_modalidade com o jogo associado

# Falha — jogo já tem modalidade
Dado que já existe modalidade para o jogo "Milhar"
Quando tento criar outra modalidade para "Milhar"
Então erro 409 "Jogo já possui modalidade cadastrada" é exibido

# Regra — jogo imutável após criação
Dado que a modalidade "Milhar" já foi criada com int_jogo_id=1
Quando edito a modalidade
Então o campo de jogo está readonly — não pode ser alterado
E somente apresentacao, multiplicadores e limites podem ser editados

# Validação de multiplicadores
Dado que preencho multiplicador=0
Quando salvo
Então validação exige valor > 0
```

### Regras de Negócio Relacionadas

- RN: `int_jogo_id` é imutável após criação — define o tipo de jogo permanentemente 🟢
- RN: 409 se `int_jogo_id` já tem modalidade associada 🟢
- RN: `apresentacao` é o nome exibido no app móvel e relatórios 🟢
- RN: Modalidades de Quininha/Seninha/Lotinha/Bilhetinho requerem campos adicionais (`multiplicador_colocacao_01..03`) 🟡

### Dependências

- `int_jogo`, `cfg_area_cotacao` (usa modalidade como dimensão), `cfg_area_comissao_modalidade`

### Prioridade

**Must** — sem modalidades, não há apostas possíveis.

---

## US-CAD-05: Cadastrar Vendedor (com usuário do sistema)

**Como** administrador ou gerente  
**Quero** cadastrar um vendedor vinculado a uma área  
**Para que** o vendedor possa registrar apostas via app móvel ou terminal

### Critérios de Aceitação

```gherkin
# Happy path — criar vendedor completo
Dado que seleciono área=Centro, coletor=Maria, e preencho nome="João", login="joao"
E senha="abc123", limite_venda=1000.00, comissao=10%
Quando salvo
Então registro criado em cad_vendedor
E SystemUser criado com login="joao" no Adianti
E vendedor aparece no combo de área ao filtrar relatórios

# Happy path — buscar vendedores por área (AJAX)
Dado que estou em uma tela com combo Área+Vendedor (ex: relatório)
Quando altero o combo Área para "Norte"
Então combo Vendedor recarrega via AJAX com somente os vendedores da área "Norte"

# Happy path — permissões de venda
Dado que vendedor tem permissoes: permite_cancelar=true, permite_desconto=false
Quando o app consulta perfil do vendedor via REST
Então o app exibe ou oculta as opções de cancelamento e desconto conforme as flags

# Falha — login duplicado
Dado que já existe usuário com login="joao"
Quando tento criar vendedor com login="joao"
Então erro "Login já em uso" é exibido

# Falha — área obrigatória
Quando salvo sem selecionar área
Então validação "Área obrigatória" impede o save

# Inativar vendedor
Dado que vendedor "João" está ativo
Quando altero ativo=false e salvo
Então cad_vendedor.ativo=false
E SystemUser associado é inativado (ou deveria ser — verificar implementação) 🔴
```

### Regras de Negócio Relacionadas

- RN: Dual-Entity — cria `cad_vendedor` + `SystemUser` na mesma TTransaction 🟢
- RN: Vendedor obrigatoriamente vinculado a `area_id` e `coletor_id` 🟢
- RN: Flags de permissão: `permite_cancelar`, `permite_desconto`, `permite_fiado`, etc. controlam funcionalidades no app 🟢
- RN: `limite_venda` e `limite_credito` são limites operacionais do ponto de venda 🟡
- RN: `acesso_web` determina se o vendedor pode logar na interface web 🟡

### Dependências

- `cad_area` (obrigatório), `cad_coletor` (obrigatório), `system_users` (Adianti), `cfg_area_comissao_modalidade`, `mov_caixa`

### Prioridade

**Must** — vendedores são os operadores do negócio; sem eles não há apostas.

---

## Atores do Sistema

| Ator | Perfil | Capacidades |
|---|---|---|
| **Administrador** | `system_users` com acesso total | Todos os cadastros, configurações, relatórios |
| **Gerente/Coletor** | `cad_coletor` com `acesso_web=true` | Visualiza sua área; relatórios filtrados por área |
| **Vendedor** | `cad_vendedor` com acesso ao app | Registra apostas, consulta caixa via app móvel |

---

## Rastreabilidade de Código

| User Story | Controller | Model | Spec SDD |
|---|---|---|---|
| US-CAD-01 | `app/control/area/AreaForm.php` | `app/model/entities/Area.php` | `sdd/area.md` |
| US-CAD-02 | `app/control/gerente/GerenteForm.php` | `app/model/entities/Gerente.php` | `sdd/gerente.md` |
| US-CAD-03 | `app/control/extracao/ExtracaoForm.php` | `app/model/entities/Extracao.php` | `sdd/extracao.md` |
| US-CAD-04 | `app/control/modalidade/ModalidadeForm.php` | `app/model/entities/Modalidade.php` | `sdd/modalidade.md` |
| US-CAD-05 | `app/control/vendedor/VendedorForm.php` | `app/model/entities/Vendedor.php` | `sdd/vendedor.md` |
