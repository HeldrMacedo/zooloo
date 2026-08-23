---
description: Regras e fontes de consulta para desenvolvimento no Adianti Framework
trigger: model_decision
---

# Diretrizes de Desenvolvimento Adianti Framework

Sempre que o usuário solicitar a criação, refatoração, manutenção ou dúvida sobre o **Adianti Framework** (PHP):

1. **Fontes de Consulta Obrigatórias**:
   - **Exemplos Atualizados (Versão 8.4)**: `/home/helder/Desenvolvimento/Adianti8.4/tutor`
   - **Core do Framework (Versão 8.4)**: `/home/helder/Desenvolvimento/Adianti8.4/framework`
   - **Livro Oficial Conceitual (Versão 7)**: `/home/helder/Desenvolvimento/zooloo/adianti7pdf_compress.pdf`

2. **Utilitário de Busca Rápida**:
   - Utilize o script helper:
     `python3 .agents/skills/adianti-expert/scripts/search_adianti.py "<termo>" [--source tutor|framework|book]`
   - Para ler uma página específica do livro:
     `python3 .agents/skills/adianti-expert/scripts/search_adianti.py --page <numero>`

3. **Padrões de Código**:
   - Utilizar componentes modernos compatíveis com **Bootstrap 5** (`adminbs5`), como `BootstrapFormBuilder`.
   - Garantir compatibilidade com **PHP 8.2+**.
   - Gerenciar transações com `TTransaction::open(...)`, `TTransaction::close()` e `TTransaction::rollback()` dentro de blocos `try/catch`.
