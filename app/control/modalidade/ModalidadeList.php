<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ModalidadeList extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('permission');
        parent::setActiveRecord('Modalidade');
        parent::setDefaultOrder('ordem');
        parent::addFilterField('apresentacao', 'like', 'apresentacao');
        parent::addFilterField('ativo', '=', 'ativo');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        $this->form = new BootstrapFormBuilder('form_search_modalidade_list');
        $this->form->setFormTitle('Modalidade');

        $id = new TEntry('modalidade_id');
        $apresentacao = new TEntry('apresentacao');
        $ativo = new TCombo('ativo');
        
        
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Apresentação')], [$apresentacao]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $id->setSize('30%');
        $apresentacao->setSize('100%');
        $ativo->setSize('100%');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_jogo                = new TDataGridColumn('{jogo->descricao}', 'Jogo', 'left');
        $column_apresentacao        = new TDataGridColumn('apresentacao', 'Apresentação', 'left');
        $column_ordem               = new TDataGridColumn('ordem', 'Ordem', 'center');
        $column_multiplicador       = new TDataGridColumn('multiplicador', 'Multiplicador', 'right');
        $column_limite_palpite      = new TDataGridColumn('limite_palpite', 'Limite Palpite', 'right');
        $column_limite_descarga     = new TDataGridColumn('limite_descarga', 'Limite Descarga', 'right');
        $column_limite_aceite       = new TDataGridColumn('limite_aceite', 'Limite Aceite', 'right');
        $column_ativo               = new TDataGridColumn('ativo', 'Ativo', 'center');


        $column_limite_palpite->setTransformer(function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });
        
        $column_limite_descarga->setTransformer(function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });
        
        $column_limite_aceite->setTransformer(function($value, $object, $row) {
            if ($value === null || $value == '') {
                return 'N/A';
            }
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

  
        $column_multiplicador->setTransformer(function($value, $object, $row) {
            if ($value === null || $value == '') {
                return 'N/A';
            }
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

    
        $column_ativo->setTransformer(function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:10pt;";
            $div->add($label);
            return $div;
        });

        $this->datagrid->addColumn($column_jogo);
        $this->datagrid->addColumn($column_apresentacao);
        $this->datagrid->addColumn($column_multiplicador);
        $this->datagrid->addColumn($column_limite_palpite);
        $this->datagrid->addColumn($column_limite_descarga);
        $this->datagrid->addColumn($column_limite_aceite);
        $this->datagrid->addColumn($column_ordem);
        $this->datagrid->addColumn($column_ativo);

        // Ordenação por apresentação
        $order_apresentacao = new TAction(array($this, 'onReload'));
        $order_apresentacao->setParameter('order', 'apresentacao');
        $column_apresentacao->setAction($order_apresentacao);

        // Ordenação por ordem
        $order_ordem = new TAction(array($this, 'onReload'));
        $order_ordem->setParameter('order', 'ordem');
        $column_ordem->setAction($order_ordem);

        // Ações do datagrid
        $action_edit = new TDataGridAction(['ModalidadeForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('modalidade_id');
        $this->datagrid->addAction($action_edit);

        // $action_delete = new TDataGridAction(array($this, 'onDelete'), ['register_state' => 'false']);
        // $action_delete->setButtonClass('btn btn-default');
        // $action_delete->setLabel(_t('Delete'));
        // $action_delete->setImage('far:trash-alt red');
        // $action_delete->setField('modalidade_id');
        // $this->datagrid->addAction($action_delete);

        $action_onoff = new TDataGridAction(array($this, 'onTurnOnOff'));
        $action_onoff->setButtonClass('btn btn-default');
        $action_onoff->setLabel(_t('Activate/Deactivate'));
        $action_onoff->setImage('fa:power-off orange');
        $action_onoff->setField('modalidade_id');
        $this->datagrid->addAction($action_onoff);

        // Ações para alterar ordem (similar ao Angular)
        $action_order_up = new TDataGridAction(array($this, 'onOrderUp'));
        $action_order_up->setButtonClass('btn btn-default');
        $action_order_up->setLabel('Subir');
        $action_order_up->setImage('fa:arrow-up green');
        $action_order_up->setField('modalidade_id');
        $this->datagrid->addAction($action_order_up);

        $action_order_down = new TDataGridAction(array($this, 'onOrderDown'));
        $action_order_down->setButtonClass('btn btn-default');
        $action_order_down->setLabel('Descer');
        $action_order_down->setImage('fa:arrow-down orange');
        $action_order_down->setField('modalidade_id');
        $this->datagrid->addAction($action_order_down);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        // Busca rápida
        $btnf = TButton::create('find', [$this, 'onSearch'], '', 'fa:search');
        $btnf->style= 'height: 37px; margin-right:4px;';

        $form_search = new \Adianti\Widget\Form\TForm('form_search_apresentacao');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($apresentacao, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        // Botão adicionar
        $panel->addHeaderActionLink('', new TAction(['ModalidadeForm', 'onEdit'],  ['register_state' => 'false']), 'fa:plus');
        $this->filter_label = $panel->addHeaderActionLink(_t('Filters'), new TAction([$this, 'onShowCurtainFilters']), 'fa:filter');
   
        // Dropdown de exportação
        $dropdown = new TDropDown(_t('Export'), 'fa:list');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( _t('Save as CSV'), new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table fa-fw blue' );
        $dropdown->addAction( _t('Save as PDF'), new TAction([$this, 'onExportPDF'], ['register_state' => 'false', 'static'=>'1']), 'far:file-pdf fa-fw red' );
        $dropdown->addAction( _t('Save as XML'), new TAction([$this, 'onExportXML'], ['register_state' => 'false', 'static'=>'1']), 'fa:code fa-fw green' );
        $panel->addHeaderWidget( $dropdown );

        // Dropdown de limite de registros
        $dropdown = new TDropDown( TSession::getValue(__CLASS__ . '_limit') ?? '10', '');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( 10,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '10']) );
        $dropdown->addAction( 20,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '20']) );
        $dropdown->addAction( 50,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '50']) );
        $dropdown->addAction( 100,  new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '100']) );
        $dropdown->addAction( 1000, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '1000']) );
        $panel->addHeaderWidget( $dropdown );

        if (TSession::getValue(get_class($this).'_filter_counter') > 0)
        {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel(_t('Filters') . ' ('. TSession::getValue(get_class($this).'_filter_counter').')');
        }

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($panel);

        parent::add($container);
    }

    public static function onShowCurtainFilters()
    {
        try
        {
            $page = TPage::create();
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('override', 'true');
            $page->setPageName(__CLASS__);

            $btn_close = new TButton('closeCurtain');
            $btn_close->onClick = "Template.closeRightPanel();";
            $btn_close->setLabel("Fechar");
            $btn_close->setImage('fas:times');

            // instantiate self class, populate filters in construct
            $embed = new self;
            $embed->form->addHeaderWidget($btn_close);

            // embed form inside curtain
            $page->add($embed->form);
            $page->show();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onTurnOnOff($param = null)
    {
        try
        {
            TTransaction::open('permission');
            $modalidade = Modalidade::find($param['modalidade_id']);
            if ($modalidade instanceof Modalidade) {
                $modalidade->ativo = $modalidade->ativo == 'S' ? 'N' : 'S';
                $modalidade->store();
            }

            TTransaction::close();

            $this->onReload($param);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onDelete($param = null)
    {
        try
        {
            TTransaction::open('permission');
            $modalidade = Modalidade::find($param['modalidade_id']);
            if ($modalidade instanceof Modalidade) {
                $modalidade->delete();
            }

            TTransaction::close();

            $this->onReload($param);
            new TMessage('info', 'Modalidade excluída com sucesso!');
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onOrderUp($param = null)
    {
        try
        {
            TTransaction::open('permission');
            $modalidade = Modalidade::find($param['modalidade_id']);
            
            if ($modalidade instanceof Modalidade && $modalidade->ordem > 1) {
                // Busca a modalidade com ordem anterior
                $modalidade_anterior = Modalidade::where('ordem', '=', $modalidade->ordem - 1)->first();
                
                if ($modalidade_anterior) {
                    // Troca as ordens
                    $ordem_temp = $modalidade->ordem;
                    $modalidade->ordem = $modalidade_anterior->ordem;
                    $modalidade_anterior->ordem = $ordem_temp;
                    
                    $modalidade->store();
                    $modalidade_anterior->store();
                }
            }

            TTransaction::close();
            $this->onReload($param);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onOrderDown($param = null)
    {
        try
        {
            TTransaction::open('permission');
            $modalidade = Modalidade::find($param['modalidade_id']);
            
            if ($modalidade instanceof Modalidade) {
                // Busca a modalidade com ordem posterior
                $modalidade_posterior = Modalidade::where('ordem', '=', $modalidade->ordem + 1)->first();
                
                if ($modalidade_posterior) {
                    // Troca as ordens
                    $ordem_temp = $modalidade->ordem;
                    $modalidade->ordem = $modalidade_posterior->ordem;
                    $modalidade_posterior->ordem = $ordem_temp;
                    
                    $modalidade->store();
                    $modalidade_posterior->store();
                }
            }

            TTransaction::close();
            $this->onReload($param);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onAfterSearch()
    {
        if (TSession::getValue(get_class($this).'_filter_counter') > 0)
        {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel(_t('Filters') . ' ('. TSession::getValue(get_class($this).'_filter_counter').')');
        }
        else 
        {
            $this->filter_label->class = 'btn btn-default';
            $this->filter_label->setLabel(_t('Filters'));
        } 

        if (!empty(TSession::getValue(get_class($this).'_filter_data')))
        {
            $obj = new stdClass;
            $obj->apresentacao = TSession::getValue(get_class($this).'_filter_data')->apresentacao;
            TForm::sendData('form_search_apresentacao', $obj);
        }
    }

    public static function onChangeLimit($param)
    {
        TSession::setValue(__CLASS__ . '_limit', $param['limit'] );
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
    }
}