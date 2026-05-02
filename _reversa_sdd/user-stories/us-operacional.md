# User Stories — Operacional

> Gerado pelo Reversa Writer em 2026-05-01
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO | 🔴 LACUNA
> Módulos cobertos: Descarrego JB, Mapa de Apostas, Consulta de Vendas,
> Apuração JB, Vendas por Sorteio, Geral Financeiro, Geral Comissão

---

## US-OPE-01: Gerenciar Apostas em Descarrego

**Como** operador da banca  
**Quero** visualizar e processar apostas que ultrapassaram o limite de descarga  
**Para que** o risco financeiro seja controlado antes do encerramento do sorteio

### Critérios de Aceitação

```gherkin
# Happy path — buscar apostas pendentes (modo detalhado)
Dado que seleciono data=2026-05-01, área=Centro, extração=Tarde
E clico em "Busca Detalhada"
Então tabela exibe apostas pendentes: Data, Apostado, Jogo, Ações
E rodapé exibe totalGeral=SUM(apostado) calculado no frontend
E contador regressivo exibe "Xh Ym Zs para encerrar" em #ContentPlaceHolderMaster_demo

# Happy path — buscar apostas pendentes (modo agrupado)
Quando clico em "Agrupada"
Então tabela exibe apostas agrupadas por número: Jogo, Total apostado, Quantidade
E tabela anterior é ocultada (somente uma visão por vez)

# Happy path — processar aposta individual (modal)
Dado que há aposta pendente para o palpite "1234"
Quando clico em "Processar" na linha
Então modal de confirmação é aberto
Quando confirmo no modal
Então PUT api/descarregojb/{userId}/{userName} é chamado
E register_log registrado: {acao:'Descarrego', historico:'Processar'}
E lista recarregada automaticamente via evento 'descarregoListModification'

# Happy path — processar todas em lote
Dado que btnProcessarTodas está habilitado
Quando clico em "Processar Todas"
Então PUT api/descarregarTodas/{userId}/{userName} com {data_sorteio, extracao}
E register_log: {historico:'Processar Todas'}
E todas as apostas pendentes são processadas de uma vez

# Happy path — visualizar processados
Quando clico em "Descarregadas Detalhada"
Então tabela de processados exibe: Data, Usuário, Apostado, Jogo
E sem botão Processar (somente visualização)

# Happy path — download PDF
Dado que btnDownload está habilitado
Quando clico em Download PDF
Então jsPDF gera PDF no browser com: cabeçalho=parametro.nomeBanca, colunas: # | Jogo | Valor
E nenhum endpoint de backend é chamado (geração client-side)

# Falha — área não selecionada (gerente)
Dado que usuário é gerente (não admin)
Quando clica Buscar sem selecionar área
Então alerta "Selecione uma área!"

# Bug documentado — botão habilitado pelo último registro
Dado que a lista tem 5 registros onde os 4 primeiros têm situacao='A' e o último 'P'
Quando a lista carrega
Então btnProcessar fica desabilitado (baseado apenas no último registro) 🔴
E o bug correto seria: habilitar se QUALQUER registro tiver situacao='A'

# Bug documentado — typo no alerta de sucesso
Quando processamento individual é concluído com sucesso
Então alerta exibe "Porcessado com sucesso!" (typo — deve ser "Processado") 🔴

# Regra — contador apenas para data atual
Dado que pesquiso data diferente de hoje (ex: ontem)
Quando resultado é exibido
Então contador regressivo fica em branco (não exibe tempo de outra data)
```

### Regras de Negócio Relacionadas

- RN-DJ-01: Área obrigatória para gerentes 🟢
- RN-DJ-02: Admin (lastName != null) vê todas as áreas 🟢
- RN-DJ-03: Filtro de extração exibe apenas extrações JB 🟢
- RN-DJ-05: Contador regressivo exibido somente quando data = hoje 🟢
- RN-DJ-07: Typo "Porcessado" a corrigir no Zooloo 🔴
- RN-DJ-08: Bug de habilitação de botão baseada no último registro 🔴

### Dependências

- `cfg_extracao_descarga` (limites que geram o descarrego)
- `api/descarregojb`, `api/descarregoagrupado`, `api/descarregados`, `api/descarregadosagrupado`
- `api/descarregarTodas`, `api/descarregartodasagrupado`
- `api/registerLog` (auditoria de cada processamento)

### Prioridade

**Must** — controle de risco operacional crítico; sem descarrego a banca fica exposta a perdas ilimitadas.

---

## US-OPE-02: Monitorar Volume de Apostas por Número (Mapa de Apostas)

**Como** gestor da banca  
**Quero** visualizar o volume consolidado de apostas por número antes do sorteio  
**Para que** eu possa identificar concentrações de risco e agir preventivamente

### Critérios de Aceitação

```gherkin
# Happy path — consultar mapa
Dado que seleciono data=2026-05-01, área=Centro, extração=Tarde, modalidade=Milhar, ordem=Palpite
Quando clico em Buscar
Então tabela exibe uma linha por palpite: Bicho, Palpite, Jogos, Total (BRL)
E rodapé exibe "Total Apostas = SUM(total)"
E palpites ordenados numericamente/alfabeticamente

# Happy path — ordenar por quantidade
Quando altero Ordem para "Quantidade de jogos" e busco
Então tabela exibe os números mais apostados primeiro (descendente)

# Happy path — ordenar por valor
Quando altero Ordem para "Valor total jogos" e busca
Então tabela exibe os números com maior volume financeiro primeiro

# Validação — modalidade restrita
Dado que o combo de modalidade está aberto
Então somente MILHAR, CENTENA, GRUPO e DEZENA aparecem
E modalidades como Duque, Terno, Bilhetinho não aparecem

# Falha — extração obrigatória
Quando clico Buscar sem selecionar extração
Então alerta "Escolha uma Extração e Modalidade!"

# Falha — área obrigatória para gerente
Dado que usuário é gerente
Quando busca sem selecionar área
Então alerta "Selecione uma área!"

# Diferença com Descarrego
Dado que consulto o Mapa de Apostas
Então é somente leitura — não há botão Processar
E qualquer número pode ser consultado independente de ter ultrapassado limite
```

### Regras de Negócio Relacionadas

- RN-MA-01: Extração e Modalidade são obrigatórios 🟢
- RN-MA-04: Combo de modalidade exibe apenas MILHAR, CENTENA, GRUPO, DEZENA 🟢
- RN-MA-05: Ordenação 0=Palpite, 1=Qtd jogos, 2=Valor total 🟢
- RN-MA-06: `bicho` derivado pelo backend a partir do grupo JB do palpite 🟡
- RN-MA-07: Preventivo (consulta livre) vs. Descarrego (reativo — acima do limite) 🟡
- RN-MA-08: Somente leitura — sem ação de processamento 🟢

### Dependências

- `mov_jb_sort_palpite` (dados agregados), `mov_jb`, `mov_sorteio`
- `cad_modalidade` + `int_jogo` (filtro restrito)
- `api/mapaapostas`

### Prioridade

**Must** — ferramenta de controle de risco preventivo; complementar ao Descarrego.

---

## US-OPE-03: Consultar Vendas de Bilhetes

**Como** gestor ou operador  
**Quero** consultar os bilhetes vendidos com filtros detalhados  
**Para que** eu possa auditar apostas, buscar bilhetes específicos e cancelar quando necessário

### Critérios de Aceitação

```gherkin
# Happy path — busca normal com filtros
Dado que seleciono data=2026-05-01, área=Centro, extração=Tarde, situacao=ATIVO
Quando clico em Buscar
Então tabela exibe bilhetes: NSU (6 dígitos), Data, Vendedor, Modalidade, Situação, Extração, Comissão, Total, Previsão Prêmio
E rodapé totaliza: comissaoTotal, total, previsaoPremio (excluindo CANCELADOS)

# Happy path — modo NSU (busca exclusiva)
Quando digito "123456" no campo NSU
Então todos os outros campos de filtro são desabilitados automaticamente
E botão "Buscar" some; botão "Buscar Nsu" aparece
Quando clico "Buscar Nsu"
Então apenas o bilhete com NSU=123456 é exibido

# Happy path — retornar ao modo filtros
Dado que estava no modo NSU
Quando apago o valor do campo NSU
Então todos os campos de filtro reaparecem habilitados
E botão "Buscar Nsu" some; botão "Buscar" volta

# Happy path — ver detalhe do bilhete
Dado que há bilhete na lista
Quando clico no NSU (link)
Então popup abre com detalhe completo: palpites, modalidade, valores, resultado de cada sorteio

# Happy path — cancelar bilhete via popup
Dado que o popup de detalhe está aberto para bilhete com situacao=ATIVO
Quando clico em Cancelar e confirmo
Então PUT api/vendasjb-cancelamento/{nsu} é chamado
E bilhete passa para situacao=CANCELADO
E totalização do rodapé é atualizada (exclui o cancelado)

# Regra — combo Tipo dinâmico
Dado que apenas ativo_jb=true em cfg_parametros
Quando acesso a tela
Então combo Tipo está oculto e filtro JOGOS é aplicado automaticamente

# Regra — AJAX área→vendedor
Quando altero o combo Área para "Norte"
Então combo Vendedor recarrega automaticamente com vendedores da área Norte
```

### Regras de Negócio Relacionadas

- RN-VJB-01: Modo NSU mutuamente exclusivo com modo filtros 🟢
- RN-VJB-02: Cancelados excluídos da totalização mas visíveis na lista 🟢
- RN-VJB-03: Combo Tipo oculto se apenas 1 jogo ativo 🟢
- RN-VJB-06: NSU com 6 dígitos com zeros à esquerda 🟢

### Dependências

- `mov_jb`, `mov_jb_sorteio`, `cad_vendedor`, `cad_area`, `cad_extracao`, `cfg_parametros`
- `api/rel-vendasjb`, `api/vendasnsu/{nsu}`, `api/vendas-detalhe/{nsu}`, `api/vendasjb-cancelamento/{nsu}`

### Prioridade

**Must** — consulta operacional diária de bilhetes vendidos.

---

## US-OPE-04: Apuração de Apostas por Sorteio

**Como** gestor  
**Quero** visualizar as apostas realizadas em um sorteio específico  
**Para que** eu possa auditar o volume apostado antes ou após o encerramento

### Critérios de Aceitação

```gherkin
# Happy path — apuração JB (com restrição de área)
Dado que seleciono data=2026-05-01, área=Centro, extração=Tarde
Quando busco
Então tabela exibe: NSU (6 dig.), Poule (6 dig.), Vendedor, Data, Extração, Palpites, Total
E palpites exibidos com espaço entre eles (CSV convertido: "1234,5678" → "1234 5678")
E rodapé: única célula com colspan=7 "Total R$ X.XXX,XX"

# Regra — restrição de área para JB
Dado que usuário é coletor/setorista
Quando acessa Apuração JB
Então vê apenas apostas de sua área (filtro automático)

# Happy path — apuração Bilhetinho (sem restrição de área)
Dado que seleciono data=2026-05-01, extração=Bilhetinho Tarde
Quando busco
Então tabela exibe as mesmas 7 colunas
E rodapé: duas células separadas "Total | R$ X.XXX,XX" (colspan=6 + valor)
E todos os perfis (inclusive coletor) veem todas as áreas

# Diferença de rodapé entre JB e Bilhetinho
Dado que estou na Apuração JB
Então rodapé tem 1 célula: <td colspan="7">Total R$ X.XXX,XX</td>
Dado que estou na Apuração Bilhetinho
Então rodapé tem 2 células: <td colspan="6">Total</td><td>R$ X.XXX,XX</td>
```

### Regras de Negócio Relacionadas

- Apuração JB: restrição de área por coletor/setorista; bug de janeiro corrigido 🟢
- Apuração Bilhetinho: sem restrição de área; bug de janeiro presente 🔴
- Palpites JB: `split(',').join(' ')` — vírgula → espaço no frontend 🟢
- Layout de rodapé difere entre os dois módulos 🟢

### Dependências

- `mov_jb_sorteio`, `mov_bilhetinho_sorteio`, `mov_sorteio`, `cad_vendedor`
- `api/apuracaojb`, `api/apuracao`

### Prioridade

**Must** — auditoria operacional diária.

---

## US-OPE-05: Visualizar Relatório Financeiro Geral

**Como** gestor da banca  
**Quero** visualizar o resumo financeiro consolidado por vendedor e por área  
**Para que** eu possa fechar o caixa e verificar prêmios pagos no período

### Critérios de Aceitação

```gherkin
# Happy path — Movimento Geral Caixa (por vendedor)
Dado que seleciono período 2026-05-01 a 2026-05-31 e área=Centro
Quando busco
Então tabela exibe por vendedor: Apurado, Comissão, Líquido, Prêmio Pago, P.Pago Terceiros
E "Total de P. Pagos" = premioPagos + premioPagosTerceiros (calculado no frontend)
E "Total" = liquido - (premioPagos + premioPagosTerceiros) (calculado no frontend)
E rodapé totaliza todas as colunas

# Happy path — Movimento Geral Financeiro (por área e vendedor)
Dado que seleciono o período e área
Quando busco o relatório geral financeiro
Então tabela exibe por área E vendedor: Apurado, Comissão, Total, Valor Prêmio, Total Geral, Prêmio Pago, Diferença
E "Total Geral" = total - premio (calculado no frontend)
E "Diferença" = premio - premioPago (calculado no frontend)

# Regra — combo Tipo dinâmico
Dado que há mais de 1 jogo ativo em cfg_parametros
Quando acesso o relatório financeiro
Então combo Tipo exibe os jogos ativos e permite filtrar por tipo

# Happy path — Geral Comissão modo Vendedor
Quando clico em "Geral Vendedor"
Então tabela exibe por (Vendedor, Extração, Modalidade): Total, Comissão
E "Líquido" = total - comissao (calculado no frontend)
E rodapé: totalVendedor, totalComissaoVendedor

# Happy path — Geral Comissão modo Área
Quando clico em "Geral Área"
Então tabela anterior some e nova exibe por (Área, Extração, Modalidade): Total, Comissão, Líquido
E os dois modos são mutuamente exclusivos (somente um visível por vez)
```

### Regras de Negócio Relacionadas

- `geralfinanceirojb`: typo no Java (`getalfinanceirojb`) a corrigir no Zooloo 🔴
- Colunas calculadas no frontend: não retornadas pela API — implementar no template PHP 🟢
- Geral Comissão: dois endpoints separados (`geralvendedor` / `geralarea`) 🟢

### Dependências

- `mov_jb`, `mov_jb_sorteio`, `cad_area`, `cad_vendedor`, `cfg_parametros`
- `api/geralfinanceirojb`, `api/geralCaixajb`, `api/geralvendedor`, `api/geralarea`

### Prioridade

**Must** — fechamento financeiro gerencial crítico.

---

## Matriz de Acesso por Perfil

| Operação | Admin | Gerente/Coletor | Setorista | Vendedor (app) |
|---|---|---|---|---|
| Descarrego | Todas as áreas | Sua área | Sua área | — |
| Mapa de Apostas | Todas as áreas | Sua área | Sua área | — |
| Consulta de Vendas | Todas as áreas | Sua área | Sua área | — |
| Apuração JB | Todas as áreas | Sua área | Sua área | — |
| Apuração Bilhetinho | Todas as áreas | **Todas** | **Todas** | — |
| Geral Financeiro | Todas | Sua área | Sua área | — |
| Geral Comissão | Todas | Sua área | Sua área | — |

> **Exceção da Apuração Bilhetinho:** nenhuma restrição por área — todos os perfis web
> veem todos os dados. Comportamento herdado do Java — avaliar se é intencional. 🟡

---

## Rastreabilidade de Código

| User Story | Controller (a criar) | Spec SDD |
|---|---|---|
| US-OPE-01 | `app/control/descarrego/DescarregoList.php` | `sdd/gap-admin-operacional.md` |
| US-OPE-02 | `app/control/mapa-apostas/MapaApostasList.php` | `sdd/gap-mapa-apostas.md` |
| US-OPE-03 | `app/control/relatorio/ConsultaVendasList.php` | `sdd/gap-relatorios-vendas.md` |
| US-OPE-04 | `app/control/relatorio/ApuracaoList.php` + `ApuracaoBilhetinhoList.php` | `sdd/gap-relatorios-operacionais.md` |
| US-OPE-05 | `app/control/relatorio/GeralFinanceiroList.php` + `GeralComissaoList.php` | `sdd/gap-relatorios-vendas.md` |
