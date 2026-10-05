# CLAUDE.md

@AGENTS.md

## Específico do Claude Code

- O mapa canônico para todos os agentes é o `AGENTS.md` (importado acima). Não duplique
  regras aqui — edite o `AGENTS.md` ou o doc de destino em `docs/`.
- Skills do superpowers sugerem `docs/superpowers/specs|plans/`. Neste repo, specs vão em
  `docs/design-docs/` e planos em `docs/exec-plans/active/`.
- A skill `adianti-expert` está em `.agents/skills/` (não em `.claude/skills/`); use o script
  direto: `python3 .agents/skills/adianti-expert/scripts/search_adianti.py "<termo>" [--source tutor|framework|book]`.
- Antes de afirmar que terminou: `composer check` no container `applications_www`.
