# Legado allsystem (referência)

Sistema Java (JHipster 5.7, Spring Boot + Angular) em `../jballsystem/allsystem/`. Usa o banco
`jb`. **Não roda em produção para o zooloo** — serve para entender regra de negócio não
documentada. Consulte antes de inventar comportamento.

## Onde está cada coisa

| O quê | Caminho (a partir de `../jballsystem/allsystem/`) |
|---|---|
| Entidades | `src/main/java/br/com/allsystem/app/domain/` |
| Repositórios | `src/main/java/br/com/allsystem/app/repository/` |
| Serviços (regras) | `src/main/java/br/com/allsystem/app/service/` |
| Controllers REST | `src/main/java/br/com/allsystem/app/web/rest/` |
| Configuração | `src/main/java/br/com/allsystem/app/config/` |
| Front: contas/usuários | `src/main/webapp/app/account/` |
| Front: admin | `src/main/webapp/app/admin/` |
| Front: entidades | `src/main/webapp/app/entities/` |
| Front: models | `src/main/webapp/app/shared/model/` |
| Modelo de entidades | `jhipster-jdl.jh` |
| Mudanças de banco | `scripts_banco_dados/` (`mudanca_05_milhar_instantanea`, `mudanca_06_novas_modalidades`, `mudanca_09_seninha_quininha`, `mudacao_11_milhar_brinde_progressiva`) |
| Casos de teste (regras) | `Casos de testes.docx` |
| Notas soltas | `Correcoes.txt`, `Observacoes.txt`, `docker_cmds.txt` |

## Paridade

Tela a tela: `docs/references/comparativo-allsystem-zooloo.md`.
