❯ Como posso usar essa documentação para criar o sistema?

● A documentação gerada tem três usos práticos principais:

  ---
  1. Para implementar funcionalidades novas

  Use as User Stories como especificação de comportamento:

  _reversa_sdd/user-stories/us-*.md

  Cada US tem critérios Gherkin (Dado/Quando/Então) prontos — são a especificação exata do que o código deve fazer. Por
  exemplo, para implementar a Grade de Comissão: abra us-configuracoes.md → US-CFG-08.

  ---
  2. Para implementar a REST API (app móvel)

  Use os specs OpenAPI como contrato:

  _reversa_sdd/openapi/spec-*.yaml

  Cada endpoint tem body, response e cenários de erro documentados. O flowcharts/rest-api.md tem os fluxogramas das
  triggers que disparam automaticamente no banco.

  ---
  3. Para saber o que fazer primeiro

  O backlog priorizado está no confidence-report.md. Os itens mais críticos que já têm decisão tomada:

  ┌─────────────┬─────────────────────────────────────────────────────────────────┬────────────────────────────────┐
  │ Prioridade  │                           O que fazer                           │          Arquivo guia          │
  ├─────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────────┤
  │ 🔴          │ Migrar MD5 → bcrypt em GerenteForm, VendedorForm,               │ adrs/003-md5-para-senhas.md    │
  │ Segurança   │ ApplicationAuthenticationService                                │                                │
  ├─────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────────┤
  │ 🔴 Bug      │ Adicionar validação de hora_sorteio em ResultadoForm::onSave()  │ us-resultado-sorteio.md        │
  │             │                                                                 │ RN-RE-09                       │
  ├─────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────────┤
  │ 🔴 Bug      │ Corrigir refreshToken para checar $decoded['expires'] < time()  │ us-app-movel.md US-APP-01      │
  ├─────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────────┤
  │ 🟡 Feature  │ Criar vw_sorteio no banco applications + corrigir ResultadoList │ us-resultado-sorteio.md        │
  │             │                                                                 │ US-RES-05                      │
  ├─────────────┼─────────────────────────────────────────────────────────────────┼────────────────────────────────┤
  │ 🟡 Feature  │ Implementar Grade de Comissão                                   │ us-configuracoes.md US-CFG-08  │
  └─────────────┴─────────────────────────────────────────────────────────────────┴────────────────────────────────┘

  ---
  Fluxo sugerido para cada tarefa

  1. Abrir a User Story correspondente → entender critérios de aceitação
  2. Consultar flowchart do módulo → entender o fluxo de controle
  3. Consultar data-dictionary.md → entender as tabelas envolvidas
  4. Implementar
  5. Testar contra os critérios Gherkin da US

  ---
  Quer começar por algum item específico? Posso abrir a spec correspondente e ajudar a implementar diretamente.

✻ Baked for 18s

● How is Claude doing this session? (optional)
  1: Bad    2: Fine   3: Good   0: Dismiss