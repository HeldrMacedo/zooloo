# Dependências do Projeto — zooloo

> Gerado pelo Reversa Scout em 2026-04-30
> Fonte: `composer.json` (raiz)

---

## Runtime (PHP — Composer)

| Pacote | Versão | Propósito |
|---|---|---|
| `phpmailer/phpmailer` | ^6.0 | Envio de e-mails |
| `tburry/pquery` | ^1.1 | Manipulação de HTML (jQuery-like em PHP) |
| `picqer/php-barcode-generator` | ^3.0 | Geração de códigos de barras |
| `dompdf/dompdf` | ^3.0 | Geração de PDF a partir de HTML |
| `bacon/bacon-qr-code` | ^3.0 | Geração de QR Codes |
| `firebase/php-jwt` | ^6.0 | JWT (autenticação REST) |
| `linfo/linfo` | ^4.0 | Informações do sistema (dashboard admin) |
| `adianti/plugins` | dev-master | Plugins oficiais do Adianti Framework |
| `adianti/pdfdesigner` | ^1.0 | Designer de PDF do Adianti |
| `adianti/barcode-document` | ^1.0 | Documentos com código de barras |
| `adianti/html-document` | ^1.0 | Documentos HTML |
| `adianti/studio-forms` | ^1.0 | Studio de formulários |
| `adianti/table-writers` | ^1.0 | Exportação de tabelas (XLS, CSV, etc.) |
| `pablodalloglio/ole` | dev-master | Suporte a formato OLE (Excel legado) |
| `pablodalloglio/spreadsheet_excel_writer` | dev-master | Geração de planilhas Excel |
| `pablodalloglio/fpdf` | dev-master | Geração de PDF (FPDF) |
| `pablodalloglio/phprtflite` | dev-master | Geração de RTF |
| `spomky-labs/otphp` | ^11.0 | OTP/2FA (autenticação de dois fatores) |
| `jfcherng/php-diff` | ^6.16 | Diff de arquivos (visualização de mudanças) |

---

## Runtime (PHP — Embutido no `lib/`)

| Biblioteca | Versão | Propósito |
|---|---|---|
| **Adianti Framework** | 8.1 | Framework MVC principal |
| **Bootstrap** | 5.x | UI/CSS |
| **jQuery** | — | DOM e Ajax |

---

## Infraestrutura (Docker)

| Componente | Imagem/Versão | Propósito |
|---|---|---|
| Apache/PHP | `php:8.2-apache` | Servidor web + runtime PHP |
| PostgreSQL | `postgres:15` | Banco de dados principal |
| Node.js | `node:20.10` | Toolchain JS (build-time) |
| Composer | `composer:latest` | Gerenciador de dependências PHP |
| MongoDB | `pecl/mongodb` | Banco NoSQL (extensão PHP) |

---

## Extensões PHP (instaladas no Dockerfile)

| Extensão | Propósito |
|---|---|
| `pdo` | PDO base |
| `pdo_pgsql` | Driver PostgreSQL |
| `pgsql` | PostgreSQL nativo |
| `gd` | Processamento de imagens |
| `bcmath` | Precisão matemática |
| `intl` | Internacionalização |
| `zip` | Compressão |
| `mongodb` | Driver MongoDB |

---

## Scripts Composer

| Script | Comando |
|---|---|
| `test` | `php tests/run.php` |

---

## Gerenciador de Pacotes

- **PHP:** Composer (`composer.json` / `composer.lock`)
- **JS:** Não utilizado (assets são estáticos em `lib/`)

---

## Subprojeto `venda-online/`

Possui `composer.json` e `vendor/` próprios com dependências similares
(dompdf, firebase/php-jwt, phpmailer, picqer, bacon/bacon-qr-code).
É uma instalação Adianti independente.
