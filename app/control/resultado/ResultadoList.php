<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Database\TDatabase;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Util\TXMLBreadCrumb;

class ResultadoList extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;
    protected $filter_criteria;
    
    public function __construct()
    {        
        parent::__construct();
        
        parent::setDatabase('permission');
        parent::setActiveRecord('MovSorteio');
        parent::setDefaultOrder('data_sorteio', 'desc');
        parent::addFilterField('data_sorteio', '=', 'data_sorteio');
        parent::addFilterField('extracao_id', '=', 'extracao_id');
        // Cria o formulário de busca
        $this->form = new BootstrapFormBuilder('form_search_resultado');
        $this->form->setFormTitle('Resultado dos Sorteios');
        
        // Campos de busca
        $data_sorteio = new TDate('data_sorteio');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        
        $data_sorteio->setMask('dd/mm/yyyy');
        $data_sorteio->setDatabaseMask('yyyy-mm-dd');
        
        $extracao_id->enableSearch();
        //$extracao_id->setDefaultOption(false);
        
        // Adiciona os campos ao formulário
        $this->form->addFields([new TLabel('Data do Sorteio:')], [$data_sorteio]);
        $this->form->addFields([new TLabel('Extração:')], [$extracao_id]);
        
        // Botões
        $btn_search = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $btn_clear = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        
        // Mantém o formulário preenchido
        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));
        
        
        // Cria o datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        // Colunas do datagrid
        $col_id = new TDataGridColumn('sorteio_id', 'ID', 'center', '8%');
        $col_extracao = new TDataGridColumn('extracao->descricao', 'Extração', 'left', '20%');
        $col_data = new TDataGridColumn('data_sorteio', 'Data Sorteio', 'center', '12%');
        $col_hora = new TDataGridColumn('hora_sorteio', 'Hora Sorteio', 'center', '12%');
        $col_situacao = new TDataGridColumn('situacao', 'Situação', 'center', '10%');
        $col_numeros = new TDataGridColumn('numeros_sorteados', 'Números Sorteados', 'left', '28%');
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_extracao);
        $this->datagrid->addColumn($col_data);
        $this->datagrid->addColumn($col_hora);
        $this->datagrid->addColumn($col_situacao);
        $this->datagrid->addColumn($col_numeros);
        
        // Transformadores
        $col_data->setTransformer(function($value) {
            return TDate::date2br($value);
        });
        
        $col_situacao->setTransformer(function($value) {
            if ($value == 'A') {
                return "<span class='label label-success'>Aberto</span>";
            } elseif ($value == 'F') {
                return "<span class='label label-danger'>Encerrado</span>";
            }
            return $value;
        });
        
        $col_numeros->setTransformer(function($value) {
            if (empty($value)) {
                return "<span class='text-muted'>Sem resultado</span>";
            }
            return $value;
        });
        
        // Ações do datagrid
        $action_edit = new TDataGridAction(['ResultadoForm', 'onEdit'], ['key' => '{sorteio_id}']);
        $action_edit->setUseButton(true);
        $action_edit->setButtonClass('btn btn-default btn-sm');
        $action_edit->setLabel('Resultado');
        $action_edit->setImage('fa:edit blue');
        
        $this->datagrid->addAction($action_edit);
        
        // Cria o modelo do datagrid
        $this->datagrid->createModel();
        
        // Navegação de páginas
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // Painel que agrupa datagrid e navegação
        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);
        
        // Container vertical
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }

    public function onClear()
    {
        $this->form->clear();        
    }
    
}

