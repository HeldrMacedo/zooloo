Reversa

Transforme sistemas legados em especificações executáveis por agentes de IA.
Sabe aquele sistema que ninguém quer tocar? O que tem 10 anos, roda em produção, gera dinheiro todos os dias, mas ninguém sabe ao certo o que ele faz por dentro? O Reversa foi feito para ele.
O que é o Reversa?¶
O Reversa é um framework de engenharia reversa de especificações. Você o instala dentro do projeto legado, ativa um agente de IA que já usa no dia a dia, e ele coordena um time de especialistas para analisar o código e gerar especificações completas, rastreáveis e prontas para uso por qualquer agente codificador.
Em outras palavras: o Reversa transforma código sem documentação em contratos operacionais que um agente de IA consegue entender e usar para evoluir o sistema com segurança.
Começo rápido¶
Na raiz do projeto legado:
npx reversa install
Depois, abra o projeto no seu agente de IA favorito e digite: 
/reversa
Pronto. O Reversa assume o volante e guia você até o fim. 
Por que o Reversa existe¶
O problema clássico¶
Imagine um sistema que entrou em produção em 2015. Ninguém que o escreveu ainda está na empresa. A documentação original era um arquivo Word que ninguém sabe onde foi parar. O código funciona, gera receita todos os dias, mas tem partes que ninguém ousa tocar porque "mexeu aqui, quebrou ali".
Esse sistema carrega anos de conhecimento acumulado: regras de negócio implícitas, decisões arquiteturais tomadas às 23h antes de um deadline, lógica crítica enterrada em funções com nomes como processar_v2_final_revisado. O conhecimento existe. Ele está no código. Mas está preso lá dentro, inacessível para qualquer agente de IA.

O problema com agentes de IA¶
Agentes de IA são transformadores para criar e evoluir software. Mas eles dependem de especificações para operar com segurança.
Para sistemas novos, funciona bem: você escreve a spec, o agente executa. Mas para sistemas legados? O agente não tem como saber o que não pode quebrar. Se você pedir para ele "refatorar o módulo de pagamentos", ele vai refatorar com base no que o código parece fazer, sem saber o que o código deve fazer.
O resultado é aquele momento clássico: o agente quebra uma regra de negócio que ninguém tinha documentado, e só descobrimos quando o cliente liga reclamando.

A solução¶
O Reversa é a ponte entre o sistema legado e os agentes de IA.
Ele analisa o código existente e extrai o conhecimento acumulado: regras de negócio, fluxos, contratos entre módulos, decisões arquiteturais retroativas. Depois, transforma tudo em especificações executáveis, rastreáveis e prontas para uso por qualquer agente codificador.
O resultado não é documentação para humanos lerem numa tarde tranquila. São contratos operacionais que permitem a um agente evoluir o sistema com fidelidade ao que já existe.

Para quem é¶
    • Empresas com sistemas legados que querem modernizar sem reescrever tudo do zero 
    • Equipes que usam vibe coding e nunca escreveram specs formais (sem julgamento) 
    • Desenvolvedores que herdaram um projeto e precisam entender o que ele faz antes de mudar qualquer coisa 
    • Qualquer pessoa que tem um sistema funcionando mas sem documentação e quer usar agentes de IA para evoluí-lo com segurança 

O que o Reversa não é¶
O Reversa não é uma ferramenta de análise estática tradicional. Ele não gera cobertura de código, não faz linting, não aponta bugs. Ele é um framework de extração de conhecimento: pega o que está implícito no código e torna explícito em especificações formais.
Também não é uma solução mágica. Partes do sistema que são genuinamente inacessíveis pela análise estática (comportamento dependente de dados reais, regras que só existem na cabeça de alguém) vão aparecer como lacunas, marcadas com 🔴, esperando validação humana. Honestidade é parte do design.
Instalação¶
Requisitos¶
    • Node.js 18+ instalado na máquina 
Se você não tem Node.js, instale em nodejs.org e volte aqui.

Um comando, isso é tudo¶
Na raiz do projeto legado que você quer analisar:
npx reversa install
O instalador faz tudo isso pra você:
    1. Detecta as engines de IA presentes no ambiente (Claude Code, Codex, Cursor, Gemini CLI, Windsurf) 
    2. Pergunta quais agentes instalar (todos selecionados por padrão) 
    3. Coleta nome do projeto, idioma e preferências 
    4. Copia os agentes para .agents/skills/ e .claude/skills/ (para Claude Code) 
    5. Cria o arquivo de entrada da engine escolhida (CLAUDE.md, AGENTS.md, etc.) 
    6. Cria a estrutura .reversa/ com estado, configuração e plano 
    7. Gera o manifesto SHA-256 para atualizações seguras no futuro 
É tipo um npm install, mas para o seu time de agentes de engenharia reversa.

O que é criado no projeto¶
projeto-legado/
├── .reversa/               ← estado, config e contexto da análise
├── .agents/skills/         ← agentes universais (todas as engines)
├── .claude/skills/         ← mirror para Claude Code
├── CLAUDE.md               ← ponto de entrada para Claude Code (se detectado)
├── AGENTS.md               ← ponto de entrada para Codex (se detectado)
└── _reversa_sdd/           ← onde as especificações serão geradas (vazio inicialmente)
Como usar¶
Ativar o Reversa¶
Após instalar, abra o projeto no seu agente de IA e ative o Reversa:
Claude Code / Cursor / Gemini CLICodex e engines sem slash commands
/reversa
É só isso. O Reversa assume o controle e coordena toda a análise a partir daí.

O que acontece quando você ativa¶
O Reversa verifica se existe uma análise em andamento:
Primeira vez: ele cria um plano de exploração personalizado para o seu projeto, apresenta ao usuário para aprovação e começa a análise pela fase 1.
Sessão retomada: ele lê o checkpoint salvo em .reversa/state.json e continua exatamente de onde parou. Não importa se você fechou o editor, reiniciou a máquina ou deixou dormindo por três dias.

Fluxo típico de uma análise completa¶
Você digita /reversa
        ↓
Reversa cria o plano de exploração
        ↓
Você revisa e aprova o plano
        ↓
Scout mapeia a superfície do projeto
        ↓
Reversa apresenta o resumo do Scout e você escolhe o nível de documentação
        ↓
Archaeologist analisa módulo por módulo
        ↓
Detective e Architect interpretam o que foi encontrado
        ↓
Writer gera as especificações (uma por vez, com sua aprovação)
        ↓
Reviewer revisa tudo e levanta perguntas para validação
        ↓
Especificações prontas em _reversa_sdd/
O processo é incremental e conversacional. Você não precisa estar presente o tempo todo: o Reversa avisa quando precisa de você.

Quanto tempo leva?¶
Depende do tamanho do projeto, mas uma regra geral:
Tamanho do projeto	Estimativa
Pequeno (< 10 módulos)	2 a 4 sessões
Médio (10 a 30 módulos)	5 a 10 sessões
Grande (30+ módulos)	10+ sessões
O Archaeologist analisa um módulo por sessão para economizar contexto. Para projetos grandes, você vai retomar várias vezes, mas cada retomada é automática e sem perda de progresso.

Dica: estouro de contexto¶
Se a sessão ficar muito longa e o contexto começar a acabar, o Reversa salva o checkpoint automaticamente e avisa:
"Vou pausar aqui. Tudo está salvo. Digite /reversa em uma nova sessão para continuar."
Sem drama. Sem perda. É só continuar depois.

Nível de documentação¶
Depois que o Scout termina, o Reversa apresenta um resumo do que encontrou (quantos módulos, integrações, se há banco de dados) e pergunta qual volume de documentação você quer para o projeto:
Nível	Quando usar	O que gera
Essencial	Projetos simples, scripts, protótipos	Artefatos principais: análise de código, domínio, arquitetura, specs SDD
Completo	Projetos médios, equipes pequenas (padrão)	Tudo do essencial + diagramas C4, ERD, ADRs, OpenAPI, user stories e matrizes de rastreabilidade
Detalhado	Sistemas enterprise, múltiplas equipes	Tudo do completo + flowcharts por função, ADRs expandidos, diagrama de deployment e revisão cruzada obrigatória
A escolha fica salva em .reversa/state.json e todos os agentes seguintes a respeitam automaticamente. Se precisar ajustar depois de iniciada a análise, basta editar o campo doc_level no arquivo.

Ativar um agente específico manualmente¶
Se quiser rodar um agente avulso, sem passar pelo orquestrador:
/reversa-scout
/reversa-detective
/reversa-data-master
Útil quando você já tem uma análise em andamento e quer executar um agente específico por algum motivo pontual.
CLI¶
O Reversa tem um CLI simples para gerenciar a instalação e o ciclo de vida dos agentes no seu projeto. Todos os comandos rodam com npx reversa na raiz do projeto.

Comandos disponíveis¶
install¶
npx reversa install
Instala o Reversa no projeto legado atual. Detecta as engines presentes, pergunta suas preferências e cria toda a estrutura necessária.
Use uma vez, na raiz do projeto que você quer analisar.

status¶
npx reversa status
Mostra o estado atual da análise: qual fase está em andamento, quais agentes já rodaram, o que falta completar.
Útil para ter uma visão geral rápida antes de retomar uma sessão.

update¶
npx reversa update
Atualiza os agentes para a versão mais recente do Reversa.
O comando é inteligente: ele verifica o manifesto SHA-256 de cada arquivo e nunca sobrescreve arquivos que você personalizou. Se você fez ajustes em algum agente, eles ficam intactos.

add-agent¶
npx reversa add-agent
Adiciona um agente específico ao projeto. Útil se você não instalou todos os agentes na instalação inicial e agora quer incluir, por exemplo, o Data Master ou o Design System.

add-engine¶
npx reversa add-engine
Adiciona suporte a uma engine de IA que não estava presente quando você instalou. Por exemplo: instalou só para Claude Code e agora quer adicionar Codex também.

uninstall¶
npx reversa uninstall
Remove o Reversa do projeto: apaga os arquivos criados pela instalação (.reversa/, .agents/skills/reversa-*/, os arquivos de entrada das engines).
Seus arquivos continuam intactos
O uninstall remove apenas o que o Reversa criou. Nenhum arquivo original do projeto é tocado. As especificações geradas em _reversa_sdd/ também são preservadas por padrão.
Configuração¶
O Reversa guarda toda a sua configuração e estado da análise dentro da pasta .reversa/ na raiz do projeto. Você pode abrir e editar os arquivos quando quiser.

Estrutura da pasta .reversa/¶
.reversa/
├── state.json          ← estado da análise entre sessões
├── config.toml         ← configuração do projeto
├── config.user.toml    ← suas preferências pessoais (não commitar)
├── plan.md             ← plano de exploração (você pode editar)
├── version             ← versão instalada do Reversa
├── context/
│   ├── surface.json    ← dados gerados pelo Scout
│   └── modules.json    ← dados gerados pelo Archaeologist
└── _config/
    ├── manifest.yaml           ← metadados da instalação
    └── files-manifest.json     ← hashes SHA-256 para updates seguros

config.toml: configuração do projeto¶
Criado na instalação. Define as configurações compartilhadas com o time:
[project]
name = "meu-projeto"
language = "pt-br"

[agents]
installed = ["reversa", "scout", "archaeologist", "detective", "architect", "writer", "reviewer"]

[output]
folder = "_reversa_sdd"

[engines]
active = ["claude-code"]
Você pode mudar o folder de saída se preferir um nome diferente de _reversa_sdd.

config.user.toml: preferências pessoais¶
Para preferências que são suas e não devem ser commitadas:
[user]
name = "Sandeco"
answer_mode = "chat"  # "chat" ou "file"
Não commitar
Adicione config.user.toml ao .gitignore. Cada pessoa do time pode ter suas próprias preferências sem afetar os outros.

plan.md: plano de exploração¶
O Reversa gera esse arquivo na primeira sessão, depois de conversar com você sobre o projeto. Ele lista as tarefas da análise em ordem.
Você pode editá-lo diretamente: reordenar tarefas, remover módulos que não quer analisar, adicionar notas. O Reversa vai respeitar o que estiver aqui quando retomar a análise.

state.json: estado da análise¶
Mantido automaticamente pelo Reversa. Registra a fase atual, quais agentes já rodaram e o progresso do Writer.
Você pode abrir para ver como está, mas não precisa editar manualmente. Se algo der errado e você precisar resetar uma fase específica, é aqui que você procuraria.

Modo de resposta (answer_mode)¶
Controla como o Reviewer levanta perguntas de validação para você:
Modo	Comportamento
chat (padrão)	As perguntas aparecem no chat, uma a uma. Você responde na conversa.
file	O Reviewer gera um arquivo _reversa_sdd/questions.md com todas as perguntas. Você preenche e avisa quando terminar.
O modo file é útil quando há muitas perguntas e você quer responder com calma, fora da sessão.

Nível de documentação (doc_level)¶
Define o volume de artefatos que cada agente vai gerar durante a análise. Não é configurado na instalação: o Reversa pergunta no início da primeira análise, após o Scout mapear o projeto, para que você decida com informação real na mão.
Valor	Quando usar	Artefatos gerados
essencial	Projetos simples, scripts, protótipos	Análise de código, domínio, arquitetura (C4 contexto), specs SDD
completo	Projetos médios, equipes pequenas (padrão)	Tudo do essencial + diagramas C4 completos, ERD, ADRs, OpenAPI, user stories, matrizes de rastreabilidade
detalhado	Sistemas enterprise, alta criticidade	Tudo do completo + flowcharts por função, ADRs expandidos, diagrama de deployment, revisão cruzada obrigatória
A escolha fica salva em .reversa/state.json no campo doc_level. Você pode editá-lo manualmente a qualquer momento para ajustar o nível no meio de uma análise.
Engines suportadas¶
O Reversa funciona com as principais engines de IA do mercado. O instalador detecta automaticamente quais estão presentes no ambiente, mas você pode adicionar mais a qualquer momento com npx reversa add-engine.

Compatibilidade¶
Engine	Arquivo criado	Skills path	Como ativar
Claude Code ⭐	CLAUDE.md	.claude/skills/reversa-*/ e .agents/skills/reversa-*/	/reversa
Codex ⭐	AGENTS.md	.agents/skills/reversa-*/	reversa
Cursor ⭐	.cursorrules	.agents/skills/reversa-*/	/reversa
Gemini CLI	GEMINI.md	.agents/skills/reversa-*/	/reversa
Windsurf	.windsurfrules	.agents/skills/reversa-*/	/reversa
Antigravity	AGENTS.md	.agents/skills/reversa-*/	/reversa
Kiro	.kiro/steering/reversa.md	.agents/skills/reversa-*/	/reversa
Opencode	AGENTS.md	.agents/skills/reversa-*/	reversa
Cline	.clinerules	.agents/skills/reversa-*/	/reversa
Roo Code	.roorules	.agents/skills/reversa-*/	/reversa
GitHub Copilot	.github/copilot-instructions.md	.agents/skills/reversa-*/	/reversa
Aider	CONVENTIONS.md	.agents/skills/reversa-*/	reversa
Amazon Q Developer	.amazonq/rules/reversa.md	.agents/skills/reversa-*/	/reversa

Claude Code¶
A engine mais testada e com melhor suporte. Usa slash commands nativos, o que torna a ativação intuitiva. O Reversa cria os arquivos em .claude/skills/ e em .agents/skills/ (para compatibilidade com outras engines que possam ser adicionadas depois).

Codex¶
Totalmente compatível. Como o Codex não usa slash commands, a ativação é pelo nome do agente diretamente: reversa, reversa-scout, etc. O arquivo AGENTS.md na raiz do projeto serve como ponto de entrada.

Cursor¶
Compatível via .cursorrules. O Cursor lê as regras desse arquivo e os agentes ficam disponíveis como skills.

Gemini CLI e Windsurf¶
Suporte completo. Os agentes ficam em .agents/skills/ e são acessados via os mecanismos nativos de cada engine.

Antigravity¶
Plataforma de desenvolvimento agêntico do Google, lançada em novembro de 2025. Lê AGENTS.md nativamente (mesmo arquivo do Codex). Se Codex já estiver instalado no projeto, o AGENTS.md existente é reaproveitado sem duplicação. Comando CLI: agy.

Kiro¶
IDE agêntico da Amazon. Usa steering documents em .kiro/steering/ para instruir o agente: o instalador cria .kiro/steering/reversa.md. Os agentes ficam em .agents/skills/ e são ativados via /reversa.

Opencode¶
Agente de codificação open source para terminal (SST). Lê AGENTS.md nativamente, mesma convenção do Codex. Comando CLI: opencode. Como Codex, a ativação é pelo nome do agente: reversa.

Cline e Roo Code¶
Extensions de VS Code com suporte a regras personalizadas via .clinerules e .roorules respectivamente. O padrão é idêntico ao Cursor e Windsurf: arquivo de regras na raiz do projeto que instrui o agente ao ativar /reversa.

GitHub Copilot¶
Usa .github/copilot-instructions.md como arquivo de instruções customizadas, lido automaticamente pelo Copilot em toda sessão. O instalador cria o arquivo dentro de .github/ (que pode já existir no projeto).

Aider¶
Agente de codificação para terminal. O entry file CONVENTIONS.md na raiz é passado via --read CONVENTIONS.md ou configurado em .aider.conf.yml. Como Codex e Opencode, a ativação é pelo nome: reversa.

Amazon Q Developer¶
CLI de IA da AWS. Usa regras em .amazonq/rules/ para instruir o agente por projeto. O instalador cria .amazonq/rules/reversa.md sem interferir em outras regras que você já tenha nessa pasta.

Múltiplas engines no mesmo projeto¶
Você pode ter todas as engines instaladas ao mesmo tempo. Os agentes em .agents/skills/ são compartilhados por todas. O instalador cria os arquivos de entrada específicos de cada engine sem conflito entre eles.
Se você trabalha em equipe e cada pessoa usa uma engine diferente, isso funciona normalmente: cada um usa o arquivo de entrada da sua engine, mas todos os agentes estão no mesmo lugar.
Pipeline de análise¶
O Reversa transforma um sistema legado em especificações executáveis em 5 fases. Cada fase tem agentes específicos, e o orquestrador central coordena tudo para que aconteça na ordem certa.

Visão geral¶
Fase 1          Fase 2        Fase 3              Fase 4        Fase 5
Reconhecimento  Escavação     Interpretação       Geração       Revisão
   Scout        Archaeologist    Detective            Writer       Reviewer
                               Architect
Agentes independentes que rodam em qualquer fase: Visor, Data Master, Design System

Fase 1: Reconhecimento¶
Agente: Scout
O Scout faz o primeiro tour no projeto. Como um corretor de imóveis que visita um imóvel pela primeira vez: não abre gavetas, não lê todos os documentos, só mapeia o território.
O que ele produz:
    • Inventário completo do projeto (inventory.md) 
    • Lista de dependências com versões (dependencies.md) 
    • Estrutura de dados em JSON para os próximos agentes (.reversa/context/surface.json) 
Depois que o Scout termina, o Reversa usa o surface.json para personalizar a Fase 2: em vez de uma tarefa genérica "analisar o código", o plano vira uma tarefa por módulo identificado.
Também é nesse momento que o Reversa apresenta o resumo do Scout e pergunta o nível de documentação (doc_level): essencial, completo ou detalhado. A escolha define quais artefatos cada agente vai gerar nas fases seguintes — veja Como usar para a tabela completa.

Fase 2: Escavação¶
Agente: Archaeologist
O Archaeologist escava o terreno módulo a módulo. Com paciência e precisão, cataloga cada artefato: funções, algoritmos, estruturas de dados, fluxos de controle. Ele não interpreta nem julga. Só descreve com precisão o que está lá.
Importante: o Archaeologist roda um módulo por sessão, de propósito. Projetos grandes têm muitos módulos, e tentar analisar tudo de uma vez consome contexto e reduz a qualidade da análise.
O que ele produz:
    • Análise técnica consolidada (code-analysis.md) 
    • Dicionário de dados (data-dictionary.md) 
    • Fluxogramas em Mermaid por módulo (flowcharts/[modulo].md) 
    • Dados estruturados por módulo (.reversa/context/modules.json) 

Fase 3: Interpretação¶
Agentes: Detective + Architect
Aqui a análise deixa de ser descritiva e vira interpretativa. Dois agentes trabalham em paralelo nessa fase.
O Detective é o Sherlock Holmes do time. Olha para o que o Archaeologist catalogou e pergunta: "Mas por que isso está aqui? Quem tomou essa decisão? O que o histórico git revela?". Extrai regras de negócio implícitas, ADRs retroativos, máquinas de estado e matrizes de permissão.
O Architect é o cartógrafo. Sintetiza tudo em documentação arquitetural formal: diagramas C4 nos três níveis (Contexto, Containers, Componentes), ERD completo, mapa de integrações, dívidas técnicas.
O que eles produzem:
    • Domínio e regras de negócio (domain.md) 
    • Máquinas de estado em Mermaid (state-machines.md) 
    • Matriz de permissões (permissions.md) 
    • ADRs retroativos (adrs/) 
    • Diagramas C4 (c4-context.md, c4-containers.md, c4-components.md) 
    • ERD completo (erd-complete.md) 
    • Visão arquitetural geral (architecture.md) 

Fase 4: Geração¶
Agente: Writer
O Writer é o tabelião do time. Transforma tudo que foi descoberto nas fases anteriores em contratos formais: especificações SDD por componente, specs OpenAPI para as APIs, user stories para os fluxos de usuário.
Cada afirmação nas specs é marcada com a escala de confiança: 🟢 CONFIRMADO, 🟡 INFERIDO ou 🔴 LACUNA.
O Writer não gera tudo de uma vez. Ele monta um plano, apresenta para você aprovar, e depois gera um arquivo por vez, esperando confirmação antes de continuar. Isso permite revisão incremental e evita desperdício de contexto.
O que ele produz:
    • Specs por componente (sdd/[componente].md) 
    • Specs de API (openapi/[api].yaml) 
    • User stories (user-stories/[fluxo].md) 
    • Matriz de rastreabilidade código-spec (traceability/code-spec-matrix.md) 

Fase 5: Revisão¶
Agente: Reviewer
O Reviewer tenta furar as specs. Encontra contradições internas, conflitos entre specs diferentes, afirmações marcadas como 🟢 que são na verdade inferências, comportamentos óbvios não especificados.
Ele também coleta as lacunas 🔴 que só você pode resolver e apresenta como perguntas para validação humana. Depois que você responde, ele atualiza as specs e gera o relatório final de confiança.
Bônus: se o plugin do Codex estiver ativo na sessão, o Reviewer pode solicitar uma revisão cruzada independente antes de fazer a sua própria análise.
O que ele produz:
    • Perguntas para validação (questions.md) 
    • Relatório final de confiança (confidence-report.md) 
    • Lacunas sem resposta (gaps.md) 
    • Specs atualizadas in-place com as reclassificações 

Agentes independentes¶
Esses agentes não pertencem a uma fase específica e podem ser acionados a qualquer momento:
Agente	Quando usar
Visor	Quando você tiver screenshots do sistema disponíveis
Data Master	Quando houver DDL, migrations ou modelos ORM para analisar
Design System	Quando houver arquivos CSS, temas ou screenshots de interface

Escala de confiança¶
Uma das partes mais importantes do Reversa é a honestidade. O sistema não finge saber o que não sabe.
Toda afirmação gerada nas especificações é marcada com um dos três níveis abaixo. Sem exceções.

Os três níveis¶
Marcação	Nome	Significado
🟢	CONFIRMADO	Extraído diretamente do código, com arquivo e linha como evidência. Pode ser citado.
🟡	INFERIDO	Deduzido a partir de padrões, nomenclatura ou contexto. Provavelmente está certo, mas pode estar errado.
🔴	LACUNA	Não determinável pela análise do código. Requer validação humana.

Por que isso importa¶
Sem essa marcação, uma especificação gerada por IA é uma caixa preta de confiança. Você não sabe o que foi extraído do código e o que foi inventado.
Com a escala de confiança, você sabe exatamente onde confiar e onde questionar. Um agente de IA usando essa spec sabe o mesmo: "esse item é 🟢, pode usar. Esse é 🔴, precisa de uma fonte humana."

Exemplos práticos¶
🟢 CONFIRMADO
A função calcular_desconto aplica 15% para pedidos acima de R$ 500. Fonte: src/pricing/discount.js, linha 47.
Isso foi extraído literalmente do código. Se alguém contestar, tem onde apontar.

🟡 INFERIDO
O sistema parece usar soft delete para registros de clientes (campo deleted_at presente na tabela).
O campo existe, o padrão é conhecido, mas em nenhum lugar do código está escrito explicitamente "usamos soft delete". Pode ser que o campo esteja lá por outro motivo.

🔴 LACUNA
Não foi possível determinar o comportamento do sistema quando o pagamento falha por timeout na gateway.
O código chama a gateway, mas não há tratamento de erro para timeout. O comportamento real pode existir na camada de infraestrutura, em um banco de dados que não foi analisado, ou nunca ter sido implementado. Precisa de alguém que conhece o sistema para responder.

Como as lacunas são resolvidas¶
O Reviewer coleta todas as lacunas 🔴 e as apresenta como perguntas para você responder. Depois que você responde, ele atualiza as specs e reclassifica: 🔴 vira 🟢 se você confirmou com evidência, ou 🟡 se você deu uma resposta mas sem certeza absoluta.
Lacunas que não puderam ser respondidas ficam em _reversa_sdd/gaps.md para tratamento posterior.
Agentes¶
O Reversa coordena um time de especialistas. Cada agente faz uma coisa só e faz bem. Nenhum deles tenta fazer tudo.
O orquestrador central (o próprio Reversa) coordena quem entra quando, em que ordem e em que ritmo. Mas você também pode acionar qualquer agente diretamente quando precisar.

Agentes obrigatórios¶
Esses fazem parte do pipeline principal. O orquestrador os executa na sequência certa.
Agente	Fase	Analogia	Função
Reversa	Orquestração	O regente de orquestra	Coordena todos os agentes, salva checkpoints e guia o usuário
Scout	Reconhecimento	O corretor de imóveis	Mapeia a superfície: pastas, linguagens, frameworks, dependências, entry points
Archaeologist	Escavação	O escavador	Análise profunda módulo a módulo: algoritmos, fluxos, estruturas de dados
Detective	Interpretação	Sherlock Holmes	Extrai regras de negócio implícitas, ADRs, máquinas de estado, permissões
Architect	Interpretação	O cartógrafo	Sintetiza tudo em diagramas C4, ERD e mapa de integrações
Writer	Geração	O tabelião	Gera specs SDD, OpenAPI e user stories com rastreabilidade de código

Agentes opcionais¶
Instalados por padrão, mas podem ser acionados de forma independente em qualquer momento.
Agente	Analogia	Quando usar
Reviewer	O revisor de specs	Após o Writer: revisa criticamente as specs e valida lacunas
Visor	O ilustrador forense	Quando tiver screenshots do sistema disponíveis
Data Master	O geólogo	Quando houver DDL, migrations ou modelos ORM para analisar
Design System	O estilista	Quando houver arquivos CSS, temas ou screenshots de interface

Sequência recomendada¶
/reversa → orquestra tudo automaticamente

Ou manualmente, se preferir controlar cada passo:

Scout → Archaeologist (N sessões) → Detective → Architect → Writer → Reviewer

Opcionais em qualquer fase:
Visor · Data Master · Design System
Guia com analogias¶
Não sabe qual agente chamar? Ativa o guia:
/agents_help
Ele explica cada agente com uma analogia do mundo real. Mas já que você está aqui, aqui vai o resumo completo:

O time completo com analogias¶
🎼 Reversa: o regente de orquestra¶
Um regente não toca nenhum instrumento. Ele conhece a partitura inteira e diz quem entra quando, em que ordem, em que ritmo. Sem ele, cada músico tocaria sua parte sem se conectar com os outros.
Use /reversa para iniciar ou retomar a análise completa. Ele cuida da sequência por você.

🗺️ Scout: o corretor de imóveis¶
O corretor faz o primeiro tour no imóvel. Não abre gavetas, não lê documentos, não mexe em nada. Só mapeia: quantos cômodos, qual o bairro, que instalações existem, qual o estado geral.
Use o Scout no começo. Ele gera o inventário do projeto sem entrar na lógica do código.

⛏️ Archaeologist: o escavador¶
O arqueólogo escava o terreno com paciência, camada por camada. Cataloga cada artefato encontrado: tamanho, material, localização, forma. Ele não interpreta a civilização, só descreve com precisão o que está lá.
Use o Archaeologist para analisar o código módulo a módulo. Roda um módulo por sessão para economizar tokens.

🔍 Detective: Sherlock Holmes¶
Sherlock Holmes chega depois do arqueólogo. Olha para os artefatos catalogados e pergunta: "Mas por que isso está aqui? Quem colocou? O que isso revela sobre quem viveu aqui?" Ele não escava. Ele interpreta.
Use o Detective após o Archaeologist. Ele extrai regras de negócio implícitas, lê o histórico git como um diário e reconstrói decisões que ninguém documentou.

📐 Architect: o cartógrafo¶
O cartógrafo visita um território e produz mapas formais: planta baixa, mapa de elevação, planta estrutural. Alguém que nunca pisou lá consegue entender tudo olhando para os mapas.
Use o Architect após o Detective. Ele sintetiza tudo em diagramas C4, ERD completo e mapa de integrações.

📝 Writer: o tabelião¶
O tabelião transforma o que foi descoberto em contratos formais, precisos e rastreáveis. Cada cláusula tem grau de certeza declarado. O documento vale como contrato: um agente de IA pode reimplementar o sistema a partir dele.
Use o Writer após o Architect. Ele gera specs SDD, OpenAPI e user stories com rastreabilidade de código.

⚖️ Reviewer: o revisor de specs¶
O Reviewer pega os contratos do Writer e tenta furar: "Isso é contradição. Esse ponto não tem prova. Essa regra some se o usuário fizer X." Ele não quer destruir, quer garantir que o que ficou de pé seja sólido.
Use o Reviewer após o Writer. Ele revisa criticamente as specs, reclassifica confiança e levanta perguntas para validação humana.

🖼️ Visor: o ilustrador forense¶
O ilustrador forense trabalha só com imagens. Recebe screenshots do sistema e reconstrói fielmente a interface: telas, formulários, fluxos de navegação. Não precisa que o sistema esteja rodando. Só das fotos.
Use o Visor quando tiver screenshots disponíveis. Ele documenta a UI sem precisar de acesso ao sistema.

🗄️ Data Master: o geólogo¶
O geólogo mapeia o subsolo: a camada que ninguém vê mas que sustenta tudo. Tabelas, relacionamentos, constraints, triggers, procedures. A fundação invisível sobre a qual a aplicação está construída.
Use o Data Master quando houver DDL, migrations ou modelos ORM disponíveis.

🎨 Design System: o estilista¶
O estilista cataloga o guarda-roupa: paleta de cores, tipografia, espaçamentos, tokens de design. As "regras de moda" que governam a aparência do sistema.
Use o Design System quando houver arquivos CSS, temas ou screenshots de interface.

Sequência recomendada¶
/reversa → orquestra tudo automaticamente

Ou manualmente:
Scout → Archaeologist (N sessões) → Detective → Architect → Writer → Reviewer

Opcionais em qualquer fase:
Visor · Data Master · Design System
Reversa (Orquestrador)¶
Comando: /reversa Fase: Orquestração

🎼 O regente de orquestra¶
Um regente não toca nenhum instrumento. Ele conhece a partitura inteira e diz quem entra quando, em que ordem, em que ritmo. Sem ele, cada músico tocaria sua parte sem se conectar com os outros. Com ele, tudo vira música.

O que faz¶
O orquestrador central é o primeiro e o último a entrar em cena. Ele não escreve código, não analisa módulos, não gera specs. Ele conhece a partitura inteira e sabe quem precisa entrar quando, em que ordem e em que ritmo.
Sem ele, cada agente tocaria sua parte sem se conectar com os outros. Com ele, tudo vira música.

Responsabilidades¶
    • Verifica se existe uma análise em andamento (lê .reversa/state.json) 
    • Na primeira sessão: cria o plano de exploração personalizado e apresenta ao usuário 
    • Em sessões subsequentes: retoma exatamente de onde parou 
    • Executa os agentes do plano sequencialmente, um por vez 
    • Salva checkpoints após cada agente concluir 
    • Apresenta resumo breve do que foi gerado a cada etapa 
    • Avisa quando o contexto está se esgotando e salva o estado antes de parar 
    • Verifica se há uma nova versão disponível e avisa discretamente 

Comportamento especial após o Scout¶
Depois que o Scout termina, o Reversa lê o surface.json gerado e personaliza a Fase 2 do plano. Em vez de uma tarefa genérica "analisar o código", o plano vira uma tarefa por módulo identificado:
- [ ] Arqueólogo: análise do módulo `auth`
- [ ] Arqueólogo: análise do módulo `orders`
- [ ] Arqueólogo: análise do módulo `payments`

Regras que ele nunca quebra¶
    • Nunca executa múltiplos agentes ao mesmo tempo sem pedido explícito do usuário 
    • Nunca desvia da sequência do plano aprovado sem avisar 
    • Nunca apaga, modifica ou sobrescreve arquivos pré-existentes do projeto 

Como ativar¶
Claude Code / Cursor / Gemini CLICodex e engines sem slash commands
/reversa
Para retomar uma análise interrompida, basta ativar novamente. O estado salvo é lido automaticamente.
Scout¶
Comando: /reversa-scout Fase: 1 - Reconhecimento

🗺️ O corretor de imóveis¶
O corretor faz o primeiro tour no imóvel. Não abre gavetas, não lê documentos, não mexe em nada. Só mapeia: quantos cômodos, qual o bairro, que instalações existem, qual o estado geral.

O que faz¶
O Scout é o primeiro a entrar no projeto. Ele faz o tour inicial: não abre gavetas, não lê todos os documentos, não mexe em nada. Só mapeia o território.
Quantos módulos existem? Qual linguagem? Qual framework? Quais as dependências críticas? Onde é o ponto de entrada da aplicação? O Scout responde tudo isso sem precisar ler uma linha de lógica de negócio.

O que ele analisa¶
    • Estrutura de pastas: árvore completa do projeto (excluindo node_modules, .git, dist, build e similares) 
    • Tecnologias e frameworks: linguagens identificadas por extensão de arquivo, frameworks e bibliotecas via arquivos de configuração (package.json, requirements.txt, go.mod, etc.) 
    • Pontos de entrada: main, index, app, server, bootstrap; arquivos de configuração; CI/CD; Docker 
    • Schema de banco (superficial): apenas lista arquivos DDL, migrations e ORM. O Data Master faz a análise detalhada. 
    • Cobertura de testes: frameworks de teste identificados e estimativa de cobertura por contagem de arquivos 

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/inventory.md	Inventário completo do projeto
_reversa_sdd/dependencies.md	Dependências com versões
.reversa/context/surface.json	Dados estruturados para os demais agentes
O surface.json é especialmente importante: o Reversa o usa para personalizar as tarefas da Fase 2 com base nos módulos identificados.

Quando usar manualmente¶
Você raramente vai precisar chamar o Scout diretamente. O orquestrador faz isso automaticamente na Fase 1. Mas se você quiser atualizar o inventário do projeto depois de uma refatoração grande, pode chamar diretamente:
/reversa-scout
Archaeologist¶
Comando: /reversa-archaeologist Fase: 2 - Escavação

⛏️ O escavador¶
O arqueólogo escava o terreno com paciência, camada por camada. Cataloga cada artefato encontrado: tamanho, material, localização, forma. Ele não interpreta a civilização, só descreve com precisão o que está lá.

O que faz¶
O Archaeologist escava o código com paciência, camada por camada. Cataloga cada artefato encontrado: tamanho, forma, localização, estrutura. Ele não interpreta a civilização, não tira conclusões sobre o negócio. Só descreve com precisão o que está lá.
É um trabalho meticuloso e repetitivo, e isso é exatamente o que o torna valioso. O Detective e o Architect vão precisar do que ele catalogou para fazer o trabalho interpretativo.

O que ele analisa por módulo¶
    • Fluxo de controle: funções e métodos principais, condicionais complexas, loops com lógica de negócio, tratamento de erros e exceções 
    • Algoritmos e lógica: algoritmos não-triviais, transformações de dados, cálculos e fórmulas, lógica de validação 
    • Estruturas de dados: modelos, entidades, DTOs, interfaces; dicionário de dados com campos, tipos, obrigatoriedade e valores padrão 
    • Metadados e configurações: constantes e enums com nomes de domínio, feature flags, parâmetros configuráveis por ambiente 

Um módulo por sessão¶
O Archaeologist analisa um módulo por vez, de propósito. Para projetos com muitos módulos, isso significa várias sessões. Mas é a abordagem certa:
    • Preserva qualidade: análise profunda de um módulo é melhor que análise rasa de vinte 
    • Conserva contexto: não esgota a janela de contexto do agente 
    • Permite revisão incremental: você pode revisar o resultado de cada módulo antes de continuar 

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/code-analysis.md	Análise técnica consolidada
_reversa_sdd/data-dictionary.md	Dicionário completo de dados
_reversa_sdd/flowcharts/[modulo].md	Fluxograma em Mermaid por módulo
.reversa/context/modules.json	Dados estruturados por módulo para os próximos agentes

Escala de confiança¶
O Archaeologist usa a escala de confiança em tudo que produz:
    • 🟢 para o que ele leu diretamente no código 
    • 🟡 para o que ele inferiu de padrões 
    • 🔴 para o que estava ilegível, obfuscado ou dependente de dados externos 

Detective¶
Comando: /reversa-detective Fase: 3 - Interpretação

🔍 Sherlock Holmes¶
Sherlock Holmes chega depois do arqueólogo. Olha para os artefatos catalogados e pergunta: "Mas por que isso está aqui? Quem colocou? O que isso revela sobre quem viveu aqui?" Ele não escava. Ele interpreta.

O que faz¶
O Detective chega depois do Archaeologist. Olha para tudo que foi catalogado e pergunta: "Mas por que isso está aqui? Quem colocou isso? O que isso revela sobre quem construiu esse sistema?"
Ele não escava mais código. Ele interpreta o que foi escavado. É o especialista em extrair o conhecimento tácito que nunca foi documentado: as regras de negócio que vivem em condicionais, as decisões arquiteturais que só existem no histórico git, as restrições que aparecem em validações sem comentário nenhum.

O que ele analisa¶
Arqueologia git¶
O Detective lê o histórico de commits como um diário do projeto:
    • Mensagens que revelam decisões de negócio ou técnicas 
    • Commits de fix/hotfix: indicam comportamentos que "deveriam funcionar assim mas não funcionavam" 
    • Grandes refatorações: indicam mudanças de requisitos que ninguém documentou 
    • Reverts com motivo aparente 
    • Tudo isso vira fonte para ADRs retroativos 
Regras de negócio implícitas¶
    • Condicionais complexas com lógica de domínio 
    • Validações e restrições nos modelos 
    • Constantes e enums com nomes de negócio (aqueles que revelam muito sobre como o domínio pensa) 
    • Comentários antigos: são evidências de intenções passadas 
    • TODOs e FIXMEs: intenções não implementadas que podem revelar requisitos esquecidos 
Máquinas de estado¶
Para cada entidade com campo de status/estado, o Detetive mapeia:
    • Todos os valores possíveis 
    • Transições permitidas e seus gatilhos 
    • Diagrama de estados em Mermaid 
Permissões e papéis¶
    • Papéis de usuário no sistema 
    • Permissões por papel 
    • Restrições de acesso a funcionalidades e dados 
    • Tudo em formato de matriz de permissões 

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/domain.md	Glossário e regras de domínio
_reversa_sdd/state-machines.md	Máquinas de estado em Mermaid
_reversa_sdd/permissions.md	Matriz de permissões
_reversa_sdd/adrs/[numero]-[titulo].md	Um ADR por decisão identificada

Uma nota sobre confiança¶
O Detective é rigoroso com a escala de confiança. A maior parte do que ele extrai é 🟡 INFERIDO, e ele sabe disso. A arte é inferir bem e marcar honestamente onde há incerteza.
Regras de negócio inferidas do código são hipóteses até serem validadas por alguém que conhece o negócio de verdade.
Architect¶
Comando: /reversa-architect Fase: 3 - Interpretação

📐 O cartógrafo¶
O cartógrafo visita um território e produz mapas formais: planta baixa, mapa de elevação, planta estrutural. Alguém que nunca pisou lá consegue entender tudo olhando para os mapas.

O que faz¶
O Architect visita o território que foi escavado e interpretado, e produz mapas formais. A ideia é que alguém que nunca pisou no projeto consiga entender a estrutura completa olhando apenas para o que o Architect produziu.
Ele trabalha junto com o Detective na Fase 3. Enquanto o Detective extrai o porquê (regras de negócio, decisões), o Architect sintetiza o como (estrutura, componentes, integrações).

O que ele produz¶
Diagramas C4¶
O Architect gera os três níveis do modelo C4:
Contexto (Nível 1): o sistema no centro, os usuários ao redor, os sistemas externos com que se integra e os protocolos de comunicação.
Containers (Nível 2): aplicações, serviços, bancos de dados, filas e caches, com a tecnologia de cada um e como se comunicam entre si.
Componentes (Nível 3): para os containers mais relevantes, os componentes internos e suas responsabilidades.
Todos os diagramas são gerados em Mermaid, prontos para renderizar em qualquer Markdown.
ERD completo¶
Todas as entidades com atributos principais, relacionamentos com cardinalidades (1:1, 1:N, N:M), chaves primárias e estrangeiras. Em Mermaid (erDiagram).
Integrações externas¶
APIs REST/GraphQL consumidas e produzidas, webhooks, eventos, mensagens, protocolos e formatos de dados.
Dívidas técnicas¶
Código duplicado, padrões inconsistentes, dependências críticas desatualizadas e ausência de testes em módulos críticos.
Spec Impact Matrix¶
Uma matriz que mostra qual componente impacta qual. Útil para saber o raio de blast de uma mudança antes de fazer.

Arquivos gerados¶
Arquivo	Conteúdo
_reversa_sdd/architecture.md	Visão geral arquitetural
_reversa_sdd/c4-context.md	Diagrama C4: Contexto
_reversa_sdd/c4-containers.md	Diagrama C4: Containers
_reversa_sdd/c4-components.md	Diagrama C4: Componentes
_reversa_sdd/erd-complete.md	ERD completo em Mermaid
_reversa_sdd/traceability/spec-impact-matrix.md	Matriz de impacto


Writer¶
Comando: /reversa-writer Fase: 4 - Geração

📝 O tabelião¶
O tabelião transforma o que foi descoberto em contratos formais, precisos e rastreáveis. Cada cláusula tem grau de certeza declarado. O documento vale como contrato: um agente de IA pode reimplementar o sistema a partir dele.

O que faz¶
O Writer transforma o que foi descoberto nas três fases anteriores em contratos formais: precisos, rastreáveis e suficientemente detalhados para que um agente de IA, sem acesso ao código original, possa reimplementar a funcionalidade com fidelidade.
Specs não são documentação para humanos lerem numa tarde tranquila. São contratos operacionais.

O fluxo de trabalho¶
O Writer nunca gera tudo de uma vez. Projetos grandes têm muitos componentes, e gerar tudo em uma resposta consome contexto excessivo e impede revisão incremental. O fluxo é assim:
1. Montar e apresentar o plano¶
Antes de gerar qualquer arquivo, o Writer lê todos os artefatos das fases anteriores e monta uma lista completa do que vai gerar:
📋 Plano de geração: 12 itens

SDD:
  [ ] 1. sdd/auth.md
  [ ] 2. sdd/orders.md
  [ ] 3. sdd/payments.md

OpenAPI:
  [ ] 4. openapi/api-v1.yaml

User Stories:
  [ ] 5. user-stories/checkout.md

Rastreabilidade:
  [ ] 6. traceability/code-spec-matrix.md

Digite CONTINUAR para iniciar.
Você aprova (ou ajusta) o plano antes de qualquer geração.
2. Gerar um item por vez¶
Para cada item: gera o arquivo, salva, avisa o que foi concluído e o que vem a seguir, e para. Você confirma "CONTINUAR" antes do próximo. Isso permite revisar cada spec antes de avançar.
3. Code/Spec Matrix por último¶
O último item sempre é a matriz de rastreabilidade: qual arquivo de código corresponde a qual spec, com o nível de cobertura de cada um.

Formato das specs SDD¶
Cada spec segue um template fixo com seções obrigatórias:
    • Visão geral do componente 
    • Responsabilidades com classificação MoSCoW (Must / Should / Could / Won't) 
    • Fluxos e regras de negócio documentadas 
    • Requisitos não funcionais (inferidos do código, não inventados) 
    • Critérios de aceitação no formato Dado / Quando / Então, com happy path e cenários de falha 
Cada afirmação é marcada com 🟢, 🟡 ou 🔴. Sem exceções.

Arquivos gerados¶
Arquivo	Conteúdo
_reversa_sdd/sdd/[componente].md	Spec por componente
_reversa_sdd/openapi/[api].yaml	Spec de API (se aplicável)
_reversa_sdd/user-stories/[fluxo].md	User stories (se aplicável)
_reversa_sdd/traceability/code-spec-matrix.md	Matriz código-spec


Reviewer¶
Comando: /reversa-reviewer Fase: 5 - Revisão

⚖️ O revisor de specs¶
O Reviewer pega os contratos do Writer e tenta furar: "Isso é contradição. Esse ponto não tem prova. Essa regra some se o usuário fizer X." Ele não quer destruir, quer garantir que o que ficou de pé seja sólido.

O que faz¶
O Reviewer pega os contratos gerados pelo Writer e tenta furar. Não para destruir, mas para garantir que o que sobrar seja sólido.
Ele procura: contradições internas dentro de uma mesma spec, conflitos entre specs diferentes, afirmações marcadas como 🟢 que na verdade são inferências, comportamentos óbvios que ninguém documentou. Se achar, ele aponta, corrige e reclassifica.

Bônus: revisão cruzada via Codex¶
Se o plugin do Codex estiver ativo na sessão, o Reviewer oferece uma opção especial: solicitar que o Codex faça uma revisão independente antes da sua própria análise.
A vantagem é ter uma segunda opinião de uma LLM diferente da que gerou as specs. Diferentes modelos cometem erros diferentes, e a revisão cruzada pega coisas que uma revisão única pode deixar passar.
Se o Codex não estiver disponível, o Reviewer segue normalmente sem mencionar o assunto.

O processo de revisão¶
Revisão por spec¶
Para cada spec em _reversa_sdd/sdd/:
    • As regras fazem sentido em conjunto? Há contradições internas? 
    • Há comportamentos óbvios não especificados? 
    • Afirmações marcadas como 🟢: o Reviewer volta ao código original para checar. Reclassifica se necessário. 
Revisão cruzada entre specs¶
    • Specs que conflitam entre si 
    • Dependências declaradas que não batem com as reais no código 
    • Specs que deveriam existir mas não foram geradas 
Validação das matrizes¶
    • code-spec-matrix.md: está completa? Há arquivos sem spec? 
    • spec-impact-matrix.md: reflete as dependências reais? 
Perguntas para você¶
Para cada lacuna 🔴 que só um humano que conhece o negócio pode resolver, o Reviewer cria uma pergunta formatada. Dependendo do answer_mode configurado:
chat (padrão): as perguntas aparecem direto no chat, uma a uma. Você responde na conversa e ele atualiza as specs em tempo real.
file: o Reviewer cria _reversa_sdd/questions.md com todas as perguntas. Você preenche com calma e avisa quando terminar.

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/questions.md	Perguntas para validação humana
_reversa_sdd/confidence-report.md	Contagem de 🟢/🟡/🔴 por spec e percentual geral
_reversa_sdd/gaps.md	Lacunas que ficaram sem resposta
_reversa_sdd/cross-review-result.md	Apontamentos do Codex (se revisão cruzada solicitada)
Specs em _reversa_sdd/sdd/ são atualizadas in-place com as reclassificações.
Visor¶
Comando: /reversa-visor Fase: Qualquer

🖼️ O ilustrador forense¶
O ilustrador forense trabalha só com imagens. Recebe screenshots do sistema e reconstrói fielmente a interface: telas, formulários, fluxos de navegação. Não precisa que o sistema esteja rodando. Só das fotos.

O que faz¶
O ilustrador forense trabalha só com imagens. Você manda screenshots e ele reconstrói fielmente o que está lá: telas, formulários, fluxos de navegação, estados de interface. Não precisa que o sistema esteja rodando. Só das fotos.
Muito útil para sistemas que rodam em ambientes difíceis de acessar: produção bloqueada, banco de dados inacessível, servidor legado que ninguém sabe configurar mais.
Requer suporte a imagens
O Visor funciona apenas com modelos que aceitam imagens como entrada. Claude, GPT-4o e Gemini suportam. Verifique se sua engine suporta antes de usar.

Como usar¶
Quando você ativa o Visor, ele pede as screenshots:
"[Nome], para documentar a interface, envie screenshots das telas do sistema. Pode enviar uma por vez ou várias de uma vez. Priorize as telas principais e os fluxos mais importantes."
Mande as imagens e ele cuida do resto.

O que ele documenta por tela¶
    • Inventário: nome e propósito da tela, estado atual (carregando, vazio, preenchido, erro), contexto de uso 
    • Formulários: campos, labels, tipos, placeholders, obrigatoriedade, validações visíveis, botões de ação 
    • Tabelas e listagens: colunas, ações por linha, paginação, filtros 
    • Navegação: menus, submenus, breadcrumbs, links 
    • Feedback: mensagens de sucesso/erro/alerta, modais, confirmações, tooltips 
    • Estados: compara a mesma tela em estados diferentes quando você fornece múltiplas screenshots dela 

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/ui/inventory.md	Inventário completo de telas
_reversa_sdd/ui/flow.md	Fluxo de navegação em Mermaid
_reversa_sdd/ui/screens/[nome-da-tela].md	Spec detalhada por tela
Data Master¶
Comando: /reversa-data-master Fase: Qualquer

🗄️ O geólogo¶
O geólogo mapeia o subsolo: a camada que ninguém vê mas que sustenta tudo. Tabelas, relacionamentos, constraints, triggers, procedures. A fundação invisível sobre a qual a aplicação está construída.

O que faz¶
O geólogo mapeia o subsolo: a camada que ninguém vê mas que sustenta tudo. Tabelas, relacionamentos, constraints, triggers, stored procedures. A fundação invisível sobre a qual a aplicação está construída.
O Scout faz uma varredura superficial do banco (só lista os arquivos). O Data Master é a análise completa, profunda e formal.

Fontes de análise¶
O Data Master usa o que estiver disponível no projeto:
    1. Arquivos DDL: .sql com CREATE TABLE, ALTER TABLE 
    2. Migrations: Laravel, Rails, Flyway, Liquibase, Alembic, Prisma 
    3. Modelos ORM: Eloquent, ActiveRecord, SQLAlchemy, Hibernate, TypeORM 
    4. Screenshots: de ferramentas como DBeaver, pgAdmin, MySQL Workbench 
    5. Conexão direta: apenas SELECT; nunca INSERT, UPDATE, DELETE, DROP 

O que ele documenta¶
Inventário de tabelas¶
Lista todas as tabelas com nome e propósito inferido, agrupadas por domínio de negócio.
Estrutura detalhada¶
Para cada tabela: colunas com nome, tipo, tamanho, nullable e default; PKs e FKs; índices; constraints.
Relacionamentos¶
Todos os relacionamentos com cardinalidades (1:1, 1:N, N:M), tabelas de junção e relacionamentos polimórficos.
Regras de negócio no banco¶
Triggers (condição, evento, ação), stored procedures e funções (parâmetros, lógica, retorno), views e materialized views, check constraints com lógica de negócio.
ERD completo¶
Gerado em Mermaid (erDiagram). Para bancos grandes, gera ERDs parciais por domínio mais um ERD geral simplificado.

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/database/erd.md	ERD completo em Mermaid
_reversa_sdd/database/data-dictionary.md	Todas as tabelas e colunas
_reversa_sdd/database/relationships.md	Relacionamentos detalhados
_reversa_sdd/database/business-rules.md	Regras de negócio no banco
_reversa_sdd/database/procedures.md	Stored procedures e funções (se existirem)
Design System¶
Comando: /reversa-design-system Fase: Qualquer

🎨 O estilista¶
O estilista cataloga o guarda-roupa: paleta de cores, tipografia, espaçamentos, tokens de design. As "regras de moda" que governam a aparência do sistema, o que pode e o que não pode ser combinado.

O que faz¶
O estilista cataloga o guarda-roupa do sistema: paleta de cores, tipografia, espaçamentos, tokens de design. As "regras de moda" que governam a aparência do projeto, o que pode e o que não pode ser combinado.
Útil quando você precisa reescrever a interface ou criar novos componentes mantendo consistência visual com o que já existe.

Fontes de análise¶
O Design System usa o que estiver disponível:
    1. CSS/SCSS/LESS: variáveis CSS (--color-primary) e variáveis Sass ($color-primary) 
    2. Tailwind CSS: tailwind.config.js com tema customizado 
    3. Bibliotecas de UI: MUI (createTheme), Chakra UI (extendTheme), Mantine, Ant Design 
    4. styled-components / Emotion: objetos de tema via ThemeProvider 
    5. Arquivos de tokens: Style Dictionary, tokens.json, design-tokens.yaml 
    6. Storybook: se existir, analisa stories para variantes de componentes 
    7. Screenshots: como complemento visual para confirmar tokens 

O que ele documenta¶
Paleta de cores¶
Cores primárias, secundárias e de destaque; cores neutras; cores de feedback (sucesso, erro, alerta, informação); variações (50 a 900 ou light/main/dark) com valores em hex/rgb/hsl.
Tipografia¶
Famílias de fontes com fallbacks, escala de tamanhos, pesos disponíveis, line-height e letter-spacing padrão, hierarquia (h1 a h6, body, caption, label, code).
Espaçamento e layout¶
Escala de espaçamento base, grid (colunas, gutter, largura máxima), breakpoints (sm, md, lg, xl, 2xl em px).
Outros tokens¶
Border-radius, sombras e elevações, z-index, transições e easing functions, opacidades semânticas.
Componentes¶
Se houver biblioteca de componentes própria: lista de componentes, variantes e props principais.

O que ele produz¶
Arquivo	Conteúdo
_reversa_sdd/design-system/color-palette.md	Paleta completa com valores
_reversa_sdd/design-system/typography.md	Sistema tipográfico
_reversa_sdd/design-system/spacing.md	Espaçamento, grid e breakpoints
_reversa_sdd/design-system/tokens.md	Todos os tokens em tabela
_reversa_sdd/design-system/design-system.md	Documento consolidado
Saídas geradas¶
Tudo que o Reversa produz vai para a pasta _reversa_sdd/ (ou o nome que você configurar em config.toml). O projeto legado nunca é tocado.
O conjunto de artefatos gerados depende do nível de documentação escolhido no início da análise:
Legenda	Nível
(todos)	Gerado nos 3 níveis
(completo+)	Apenas nos níveis completo e detalhado
(detalhado)	Apenas no nível detalhado

Estrutura completa¶
_reversa_sdd/
├── inventory.md              # Inventário do projeto — todos
├── dependencies.md           # Dependências com versões — todos
├── code-analysis.md          # Análise técnica por módulo — todos
├── data-dictionary.md        # Dicionário completo de dados — completo+
├── domain.md                 # Glossário e regras de negócio — todos
├── state-machines.md         # Máquinas de estado em Mermaid — completo+
├── permissions.md            # Matriz de permissões — completo+
├── architecture.md           # Visão arquitetural geral — todos
├── c4-context.md             # Diagrama C4: Contexto — todos
├── c4-containers.md          # Diagrama C4: Containers — completo+
├── c4-components.md          # Diagrama C4: Componentes — completo+
├── erd-complete.md           # ERD completo em Mermaid — completo+
├── deployment.md             # Diagrama de infraestrutura — detalhado
├── confidence-report.md      # Relatório de confiança 🟢🟡🔴 — todos
├── gaps.md                   # Lacunas sem resposta — completo+
├── questions.md              # Perguntas para validação humana — todos
├── sdd/                      # Specs por componente — todos
│   └── [componente].md
│
├── openapi/                  # Specs de API — completo+
│   └── [api].yaml
│
├── user-stories/             # User stories — completo+
│   └── [fluxo].md
│
├── adrs/                     # Decisões arquiteturais retroativas — completo+
│   └── [numero]-[titulo].md
│
├── flowcharts/               # Fluxogramas em Mermaid — completo+
│   └── [modulo].md
│
├── ui/                       # Specs de interface (Visor)
│   ├── inventory.md
│   ├── flow.md
│   └── screens/
│       └── [tela].md
│
├── database/                 # Specs de banco de dados (Data Master)
│   ├── erd.md
│   ├── data-dictionary.md
│   ├── relationships.md
│   ├── business-rules.md
│   └── procedures.md
│
├── design-system/            # Tokens de design (Design System)
│   ├── color-palette.md
│   ├── typography.md
│   ├── spacing.md
│   ├── tokens.md
│   └── design-system.md
│
└── traceability/
    ├── spec-impact-matrix.md # Qual spec impacta qual — completo+
    └── code-spec-matrix.md   # Arquivo de código → spec correspondente — completo+

Rastreabilidade¶
Dois arquivos conectam tudo:
traceability/code-spec-matrix.md: mapeia cada arquivo de código para a spec correspondente, com o nível de cobertura. Você sabe o que está coberto e o que não está.
traceability/spec-impact-matrix.md: mapeia qual componente impacta qual. Antes de mexer em alguma coisa, você sabe o raio de blast da mudança.

Não commitando o que não precisa¶
Sugestão de .gitignore para não versionar as saídas do Reversa junto com o código (a não ser que você queira):
# Saídas do Reversa (opcional: remova se quiser versionar as specs)
_reversa_sdd/

# Configuração pessoal do Reversa (nunca commitar)
.reversa/config.user.toml

Próximo passo¶
Specs em mãos? Veja Desenvolvendo com as specs para a ordem recomendada de construção do sistema.
Desenvolvendo com as specs¶
Depois que o Reversa gerou todas as specs em _reversa_sdd/, você pode copiar esses arquivos para qualquer máquina e começar a construir o sistema do zero. Veja a ordem recomendada.

Antes de escrever uma linha de código¶
Comece lendo esses três arquivos:
Arquivo	Por que ler primeiro
_reversa_sdd/confidence-report.md	Mostra o que tem alta confiança (verde) vs. lacunas (vermelho). Evita implementar algo baseado em inferência errada.
_reversa_sdd/gaps.md	Lista o que o Reversa não conseguiu determinar. Preencha manualmente antes de começar.
_reversa_sdd/architecture.md + diagramas C4	Mostra a visão macro: camadas, módulos, fronteiras do sistema.

Ordem de implementação (bottom-up)¶
1. database/  +  erd-complete.md            (estrutura de dados, migrations)
2. domain.md  +  sdd/[entidades-core]       (regras de negócio centrais)
3. sdd/[serviços] ordenados por dependência (use dependencies.md como guia)
4. openapi/   +  contratos de API           (se houver)
5. ui/                                      (camada de apresentação por último)

Qual sdd/ vem primeiro¶
Abra _reversa_sdd/traceability/code-spec-matrix.md. Ele lista cada spec e suas dependências.
Implemente primeiro as specs que não dependem de nenhuma outra (folhas da árvore de dependências), e suba em direção às specs que integram múltiplos componentes.

Mantendo a rastreabilidade durante o desenvolvimento¶
Use a _reversa_sdd/traceability/code-spec-matrix.md como referência durante o desenvolvimento para saber qual trecho de código implementado corresponde a qual spec. Isso mantém a rastreabilidade precisa conforme o código cresce.

Veja também¶
    • Saídas geradas: lista completa dos arquivos produzidos pelo Reversa 
    • Escala de confiança: como interpretar os marcadores 🟢🟡🔴 nas specs 
Contribuindo¶
Contribuições são bem-vindas. Se você encontrou um bug, tem uma ideia para um novo agente, ou quer melhorar alguma coisa, o processo é simples.

Antes de enviar um PR¶
Abra uma issue primeiro para discutir o que você quer mudar. Isso evita trabalho perdido nos dois lados, especialmente para mudanças maiores.

Setup local¶
git clone https://github.com/sandeco/reversa.git
cd reversa
npm install

Estrutura do projeto¶
reversa/
├── agents/             ← cada agente tem sua pasta com SKILL.md
├── bin/                ← ponto de entrada do CLI (reversa.js)
├── lib/
│   ├── commands/       ← implementação dos comandos CLI
│   └── installer/      ← lógica de instalação e detecção de engines
├── templates/          ← templates de config e arquivos de entrada por engine
└── docs/               ← documentação (você está aqui)

Adicionando um novo agente¶
    1. Crie a pasta agents/reversa-[nome]/ 
    2. Crie o SKILL.md seguindo o formato dos agentes existentes (frontmatter obrigatório: name, description, license, compatibility, metadata) 
    3. Adicione a pasta references/ se o agente precisar de schemas ou templates de referência 
    4. Atualize lib/installer/ para incluir o novo agente na lista de instalação 

Licença¶
MIT. Veja LICENSE para os detalhes.
