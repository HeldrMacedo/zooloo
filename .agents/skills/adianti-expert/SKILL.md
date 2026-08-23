---
name: adianti-expert
description: Use when creating, modifying, debugging, or designing features, controllers, models, forms, datagrids, repositories, services, or architecture using Adianti Framework (PHP).
---

# Adianti Expert Skill

Especialista em desenvolvimento de aplicações modernas com o **Adianti Framework**, combinando a arquitetura conceitual e padrões ORM documentados no **Livro do Adianti 7** com os exemplos práticos e atualizados do **Adianti 8.4 Tutor**.

---

## 📚 Fontes Oficiais de Conhecimento e Prioridade

Ao criar ou modificar qualquer componente no Adianti, consulte sempre as fontes locais na seguinte ordem:

```
┌────────────────────────────────────────────────────────┐
│ 1. ADIANTI 8.4 TUTOR (Mais Atualizado / Sintaxe 8.4)   │
│    /home/helder/Desenvolvimento/Adianti8.4/tutor       │
│    • Exemplos práticos de Forms, Datagrids, Containers │
│    • Padrão Bootstrap 5 (adminbs5) e PHP 8.2+          │
└──────────────────────────┬─────────────────────────────┘
                           │
┌──────────────────────────▼─────────────────────────────┐
│ 2. ADIANTI 8.4 FRAMEWORK CORE (Assinaturas e Métodos)  │
│    /home/helder/Desenvolvimento/Adianti8.4/framework   │
│    • Classes base em lib/adianti/ (Core, Control, etc.)│
└──────────────────────────┬─────────────────────────────┘
                           │
┌──────────────────────────▼─────────────────────────────┐
│ 3. LIVRO ADIANTI 7 (Conceitos, ORM, Padrões e Ciclo)   │
│    /home/helder/Desenvolvimento/zooloo/                │
│    adianti7pdf_compress.pdf                            │
│    • Teoria de TRecord, TCriteria, TRepository         │
│    • Relacionamentos 1:1, 1:N, N:N e Transações PDO   │
└────────────────────────────────────────────────────────┘
```

---

## 🛠️ Ferramenta de Busca Rápida (Script de Consulta)

O script unificado `search_adianti.py` permite pesquisar rapidamente nos três recursos em uma única chamada:

```bash
# Pesquisar em todas as fontes (Tutor 8.4, Framework e Livro)
python3 .agents/skills/adianti-expert/scripts/search_adianti.py "TTransaction"

# Pesquisar apenas nos exemplos do Tutor 8.4
python3 .agents/skills/adianti-expert/scripts/search_adianti.py "BFieldList" --source tutor

# Pesquisar nos métodos do Framework 8.4 Core
python3 .agents/skills/adianti-expert/scripts/search_adianti.py "AdiantiRecordService" --source framework

# Pesquisar e ler trechos conceituais do Livro Adianti 7
python3 .agents/skills/adianti-expert/scripts/search_adianti.py "active record" --source book

# Ler uma página específica do Livro Adianti 7
python3 .agents/skills/adianti-expert/scripts/search_adianti.py --page 45

# Listar todas as categorias e diretórios de exemplos do Tutor 8.4
python3 .agents/skills/adianti-expert/scripts/search_adianti.py --list-tutor
```

---

## 🗺️ Mapa de Exemplos do Adianti 8.4 Tutor

Principais referências dentro de `/home/helder/Desenvolvimento/Adianti8.4/tutor/app/control/`:

| Categoria | Caminho no Tutor | O que consultar |
|---|---|---|
| **Formulários** | `Presentation/Forms/` | Campos de texto, combo boxes, autocompletes, datas, máscaras, validações, upload, botões de ação e layouts responsivos (`BootstrapFormBuilder`). |
| **Datagrids / Tabelas** | `Presentation/Datagrid/` | Listagens com paginação, ordenação, colunas transformadas, ações inline, filtros rápidos, checkboxes de seleção em lote e exportação. |
| **Padrões de Cadastro** | `Organization/StandardControls/` e `CompleteRegistrations/` | CRUDs padrão (`TStandardList`, `TStandardForm`, `TStandardSeek`, Mestre-Detalhe, formulários com abas). |
| **Operações em Lote** | `Organization/BatchOperations/` | Atualizações e exclusões em lote via Datagrid com seleção. |
| **Containers & Layout** | `Presentation/Containers/` | `TNotebook` (abas), `TPanelGroup`, `THBox`, `TVBox`, `TCardView`, `TExpander`. |
| **Persistência / ORM** | `Persistence/` e `dbsamples/` | `ActiveRecord`, `ObjectStore`, `Criteria`, `Repository`, transações, agregações, filtros compostos e carga tardia (`Lazy Load`). |
| **Relatórios / Gráficos** | `Presentation/Report/` e `Presentation/Chart/` | Geração de relatórios PDF/HTML/CSV e gráficos interativos (Chart.js / Google Charts). |
| **Diálogos e Notificações** | `Presentation/Dialogs/` | `TMessage`, `TQuestion`, `TToast`, janelas modais (`TWindow`). |

---

## 🧩 Padrões Arquiteturais Adianti (Guia Prático)

### 1. Active Record (Model)
```php
<?php
use Adianti\Database\TRecord;

class Cliente extends TRecord
{
    const TABLENAME = 'cliente';
    const PRIMARYKEY= 'id';
    const IDPOLICY  = 'serial'; // 'max', 'serial', etc.
    
    // Relacionamento 1:N (Lazy loading ou get)
    public function get_cidade()
    {
        return Cidade::find($this->cidade_id);
    }
}
```

### 2. Transações e Persistência Segura
```php
use Adianti\Database\TTransaction;

try {
    TTransaction::open('applications'); // Abre transação com a base configurada
    
    $cliente = new Cliente;
    $cliente->nome = 'Exemplo';
    $cliente->ativo = 'S';
    $cliente->store();
    
    TTransaction::close(); // Commit
} catch (Exception $e) {
    TTransaction::rollback(); // Rollback em caso de falha
    new TMessage('error', $e->getMessage());
}
```

### 3. Consulta com Critérios e Repositório
```php
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

TTransaction::open('applications');

$criteria = new TCriteria;
$criteria->add(new TFilter('ativo', '=', 'S'));
$criteria->add(new TFilter('valor', '>=', 100));
$criteria->setProperty('order', 'nome');
$criteria->setProperty('direction', 'asc');

$repository = new TRepository('Cliente');
$clientes = $repository->load($criteria);

TTransaction::close();
```

### 4. Controller de Cadastro Padrão (Bootstrap 5)
```php
<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;

class ClienteForm extends TPage
{
    private $form;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->setFormTitle('Cadastro de Cliente');
        
        $id     = new TEntry('id');
        $nome   = new TEntry('nome');
        $status = new TCombo('status');
        
        $id->setEditable(FALSE);
        $status->addItems(['A' => 'Ativo', 'I' => 'Inativo']);
        
        $this->form->addFields([new TLabel('Código')], [$id]);
        $this->form->addFields([new TLabel('Nome*')], [$nome]);
        $this->form->addFields([new TLabel('Status')], [$status]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        
        parent::add($this->form);
    }
    
    public function onSave($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();
            
            TTransaction::open('applications');
            $object = new Cliente;
            $object->fromArray((array) $data);
            $object->store();
            TTransaction::close();
            
            $data->id = $object->id;
            $this->form->setData($data);
            new TMessage('info', 'Registro salvo com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
```

---

## ⚠️ Diferenças e Cuidados (Versão 7 vs Versão 8.4)

1. **Tema e CSS**:
   - Adianti 8.4 utiliza temas modernos baseados em **Bootstrap 5** (como `adminbs5`). Classes como `BootstrapFormBuilder` e ícones FontAwesome (ex: `fas:`, `far:`, `fa:`) são o padrão recomendado.
2. **PHP 8.2+ Compatibility**:
   - Evitar propriedades dinâmicas não declaradas em classes (usar atributos declarados ou herança padrão de `TRecord`).
   - Assinaturas de métodos estritos e tipagem PHP 8.2+.
3. **Rotas e Parâmetros**:
   - Utilização de `AdiantiCoreApplication::run()` com suporte a rotas amigáveis e proteção de requests (`strict_request`).
