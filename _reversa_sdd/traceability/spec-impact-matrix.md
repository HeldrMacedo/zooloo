# Spec Impact Matrix — zooloo

> Gerado pelo Reversa Architect em 2026-04-30
> Confiança: 🟢 CONFIRMADO | 🟡 INFERIDO

Esta matriz mapeia o impacto de mudanças em um componente sobre os demais.
Use para avaliar o raio de blast de qualquer modificação antes de implementar.

---

## Legenda

| Símbolo | Significado |
|---|---|
| 🔴 | Impacto direto — quebra garantida sem adaptação |
| 🟡 | Impacto indireto — pode requerer adaptação |
| ⬜ | Sem impacto relevante |

---

## Matriz de Impacto

| Mudança em ↓ \ Impactado → | Area | Gerente | Extracao | Modalidade | Vendedor | Terminal | AreaExtracao | AreaCotacao | AreaLimite | ExtracaoDescarga | PalpiteCotado | Parametros | MovSorteio | MovJb | REST API | UI Web |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **Area** | — | 🟡 filtra por área | ⬜ | ⬜ | 🟡 filtra por área | ⬜ | 🔴 chave FK | 🔴 chave FK | 🔴 chave FK | ⬜ | ⬜ | ⬜ | ⬜ | 🔴 mov_jb.area_id | 🟡 sorteio/bilhete filtram por área | 🟡 combos de área |
| **Gerente** | ⬜ | — | ⬜ | ⬜ | 🟡 filtra gerente por área | ⬜ | 🟡 checkUserPermissions | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🟡 filtros de área |
| **Extracao** | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | 🔴 chave FK | 🔴 chave FK | ⬜ | 🔴 chave FK | ⬜ | ⬜ | 🔴 chave FK (gera sorteios) | ⬜ | 🔴 SorteioRestService | 🟡 combos de extração |
| **Modalidade** | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | 🔴 chave FK | 🔴 chave FK | 🔴 chave FK | 🔴 chave FK | ⬜ | ⬜ | ⬜ | 🔴 ModalidadeRestService | 🟡 combos de modalidade |
| **Vendedor** | ⬜ | ⬜ | ⬜ | ⬜ | — | 🔴 chave FK | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🔴 mov_jb.vendedor_id | 🔴 BilheteRestService (permissões) | 🟡 campos de permissão |
| **Terminal** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🔴 mov_jb.terminal_id | 🔴 JWT (contém terminal_id) | ⬜ |
| **AreaExtracao** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🟡 sorteios só na área ativa | ⬜ | 🔴 BilheteRestService (valida extração na área) | 🟡 toggle status |
| **AreaCotacao** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🟡 valor do prêmio | 🔴 ModalidadeRestService (retorna cotação) | ⬜ |
| **AreaLimite** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | 🟡 fallback para limite_global | ⬜ | 🟡 COALESCE no limite | 🔴 BilheteRestService (valida limite) | ⬜ |
| **ExtracaoDescarga** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | 🟡 limita apostas por número | 🔴 BilheteRestService (verifica descarga) | ⬜ |
| **PalpiteCotado** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | ⬜ | ⬜ | ⬜ | 🔴 ModalidadeRestService (injeta cotações) | ⬜ |
| **Parametros** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🟡 fallback limite | ⬜ | ⬜ | — | ⬜ | 🟡 limite_global fallback | 🟡 BilheteRestService (limite_global) | ⬜ |
| **MovSorteio** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | — | 🔴 via mov_jb_sorteio | 🔴 SorteioRestService, BilheteRestService | 🔴 ResultadoForm |
| **MovJb** | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🟡 via jb_sorteio | — | 🔴 BilheteRestService, CaixaRestService | ⬜ |
| **IntJogo** | ⬜ | ⬜ | ⬜ | 🔴 jogo_id FK | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🟡 ModalidadeRestService | 🟡 onChangeJogo callback |
| **SystemUser** | ⬜ | 🔴 usuario_id | ⬜ | ⬜ | 🔴 usuario_id | 🔴 usuario_id (terminal) | 🟡 checkUserPermissions | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | 🔴 autenticação JWT | 🔴 login/sessão |

---

## Módulos de Alto Impacto (Hub Nodes)

Os componentes com mais dependências externas — mudanças aqui têm o maior raio de blast:

| Componente | Dependentes diretos | Risco de mudança |
|---|---|---|
| **Area** | Gerente, Vendedor, AreaExtracao, AreaCotacao, AreaLimite, MovJb, REST API | 🔴 Muito alto |
| **Extracao** | AreaExtracao, AreaCotacao, ExtracaoDescarga, MovSorteio, REST API | 🔴 Muito alto |
| **Modalidade** | AreaCotacao, AreaLimite, ExtracaoDescarga, PalpiteCotado, MovJbSortPalpite, REST API | 🔴 Muito alto |
| **BilheteRestService** | Vendedor, Terminal, MovJb, AreaExtracao, AreaLimite, ExtracaoDescarga, Parametros | 🔴 Muito alto |
| **MovSorteio** | MovJb, ResultadoForm, SorteioRestService, BilheteRestService | 🔴 Alto |
| **Vendedor** | Terminal, MovJb, BilheteRestService | 🟡 Alto |
| **Parametros** | AreaLimite (fallback), BilheteRestService | 🟡 Médio |

---

## Cenários de Mudança de Alto Risco

### Cenário 1: Alterar schema de `cad_area`
- Impacta: Gerente, Vendedor, AreaExtracao, AreaCotacao, AreaLimite, MovJb, checkUserPermissions, JWT payload (area_id)
- **Ação necessária:** Migração de dados + revisão de todos os JOINs e FKs + revisar payload do JWT

### Cenário 2: Alterar política de geração de ID (IDPOLICY='max' → 'serial')
- Impacta: **TODAS** as 19 entidades + queries de INSERT concorrentes
- **Risco:** Race condition em ambiente multi-usuário com `MAX+1`
- **Ação necessária:** Migrar para sequences PostgreSQL nativas em todas as tabelas

### Cenário 3: Trocar algoritmo de hash de senha (MD5 → bcrypt)
- Impacta: GerenteForm, VendedorForm, ApplicationAuthenticationRestService, LoginForm
- **Ação:** Necessário reset ou re-hash de todas as senhas existentes de Gerentes e Vendedores

### Cenário 4: Adicionar novo campo obrigatório em `cad_vendedor`
- Impacta: VendedorForm, VendedorList, VendedorRestService, BilheteRestService (usa Vendedor para validações), JWT (se for identidade)
- **Ação:** Migração de banco + form + REST API + testes

### Cenário 5: Alterar TTL do JWT (de 1h para outro valor)
- Impacta: ApplicationAuthenticationRestService, todos os clients do app móvel
- **Ação:** Coordenação com time do app React Native + atualizar `application.php`
