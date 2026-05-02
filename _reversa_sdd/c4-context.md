# C4 — Nível 1: Contexto do Sistema

> Gerado pelo Reversa Architect em 2026-04-30
> Confiança: 🟢 CONFIRMADO

```mermaid
C4Context
    title Diagrama de Contexto — Zooloo (Sistema de Gestão de Banca de Loteria)

    Person(admin, "Administrador", "Gerencia configurações, usuários, extrações e resultados via interface web")
    Person(gerente, "Gerente/Coletor", "Supervisiona vendedores da sua área. Acesso web restrito à própria área")
    Person(vendedor_app, "Vendedor (App Móvel)", "Registra bilhetes, consulta sorteios e gerencia caixa via app React Native")

    System(zooloo, "Zooloo", "Sistema PHP/Adianti 8.1 de gestão de banca de Jogo do Bicho. Fornece interface web administrativa e API REST para o app móvel")

    System_Ext(allsystem, "AllSystem (Legado Java)", "Sistema original Java/Spring Boot/JHipster. Banco jb compartilhado. Não deve ser modificado — referência de regras de negócio")
    System_Ext(postgres, "PostgreSQL 15", "Banco applications (sistema PHP) e banco jb (negócio legado)")
    System_Ext(smtp, "Servidor SMTP", "Envio de e-mails via PHPMailer. Usado para notificações do sistema")
    System_Ext(ldap, "LDAP Server", "Autenticação corporativa opcional. Configuração presente mas status de uso desconhecido")
    System_Ext(mongodb, "MongoDB", "Extensão instalada no Docker. Uso efetivo não identificado no código analisado")
    System_Ext(react_native, "App React Native", "Aplicativo móvel para vendedores. Planejamento documentado em docs/mobile-planejamento.md")

    Rel(admin, zooloo, "Usa", "HTTPS/Web Browser")
    Rel(gerente, zooloo, "Usa (restrito à área)", "HTTPS/Web Browser")
    Rel(vendedor_app, zooloo, "Usa REST API", "HTTPS/JWT")
    Rel(react_native, zooloo, "Consome REST API", "HTTPS/JWT Bearer")

    Rel(zooloo, postgres, "Lê e grava dados", "TCP/PostgreSQL 5432")
    Rel(allsystem, postgres, "Lê e grava dados", "TCP/PostgreSQL 5432")
    Rel(zooloo, smtp, "Envia e-mails", "SMTP")
    Rel(zooloo, ldap, "Autentica usuários (opcional)", "LDAP")
    Rel(zooloo, mongodb, "Uso não confirmado", "TCP/MongoDB")
```

## Personas

| Persona | Acesso | Restrições |
|---|---|---|
| **Administrador** | Interface web completa | Nenhuma |
| **Gerente/Coletor** | Interface web parcial | Restrito à própria área |
| **Vendedor** | App móvel (REST API) | Terminal vinculado, permissões granulares |
| **App React Native** | REST API `/rest.php` | JWT Bearer obrigatório |

## Sistemas Externos

| Sistema | Integração | Status |
|---|---|---|
| AllSystem (Java) | Banco `jb` compartilhado | 🟢 Ativo (banco compartilhado) |
| PostgreSQL 15 | Banco principal | 🟢 Ativo |
| PHPMailer/SMTP | Envio de e-mails | 🟢 Instalado |
| LDAP | Autenticação alternativa | 🟡 Configurado, uso incerto |
| MongoDB | Armazenamento alternativo | 🔴 Lacuna — instalado, sem uso confirmado |
| React Native | App móvel vendedor | 🟡 Em planejamento |
