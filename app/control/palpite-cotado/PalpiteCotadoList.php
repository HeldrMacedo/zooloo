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
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class PalpiteCotadoList extends TStandardList
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
        parent::setActiveRecord('PalpiteCotado');
        parent::setDefaultOrder('palpite_cotado_id');
        parent::addFilterField('palpite_cotado_id', '=', 'palpite_cotado_id');
        parent::addFilterField('modalidade_id', '=', 'modalidade_id');
        parent::addFilterField('palpite', 'like', 'palpite');
        parent::addFilterField('ativo', '=', 'ativo');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        $this->form = new BootstrapFormBuilder('form_search_palpite_cotado_list');
        $this->form->setFormTitle('Palpite Cotado');

        $id = new TEntry('palpite_cotado_id');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', '{jogo->descricao}', 'ordem');
        $palpite = new TEntry('palpite');
        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Modalidade')], [$modalidade_id]);
        $this->form->addFields([new TLabel('Palpite')], [$palpite]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $id->setSize('30%');
        $modalidade_id->setSize('100%');
        $palpite->setSize('100%');
        $palpite->setMask('9999');
        $ativo->setSize('100%');
        
        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('palpite_cotado_id', 'Id', 'center', 50);
        $column_modalidade = new TDataGridColumn('modalidade->jogo->descricao', 'Modalidade', 'left');
        $column_palpite = new TDataGridColumn('palpite', 'Palpite', 'center');
        $column_cotacao = new TDataGridColumn('cotacao', 'Cotação (%)', 'right');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '10%');

        // Transformadores de dados
        $column_palpite->setTransformer(function($value, $object, $row) {
            return str_pad($value, 4, '0', STR_PAD_LEFT);
        });
        
        $column_cotacao->setTransformer(function($value, $object, $row) {
            return number_format($value, 2, ',', '.') . '%';
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

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_modalidade);
        $this->datagrid->addColumn($column_palpite);
        $this->datagrid->addColumn($column_cotacao);
        $this->datagrid->addColumn($column_ativo);

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'palpite_cotado_id');
        $column_id->setAction($order_id);

        $order_modalidade = new TAction(array($this, 'onReload'));
        $order_modalidade->setParameter('order', 'modalidade_id');
        $column_modalidade->setAction($order_modalidade);

        $action_edit = new TDataGridAction(['PalpiteCotadoForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('palpite_cotado_id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('palpite_cotado_id');
        $this->datagrid->addAction($action_del);

        $action_onoff = new TDataGridAction(array($this, 'onTurnOnOff'));
        $action_onoff->setButtonClass('btn btn-default');
        $action_onoff->setLabel(_t('Activate/Deactivate'));
        $action_onoff->setImage('fa:power-off orange');
        $action_onoff->setField('palpite_cotado_id');
        $this->datagrid->addAction($action_onoff);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        $btnf = TButton::create('find', [$this, 'onSearch'], '', 'fa:search');
        $btnf->style= 'height: 37px; margin-right:4px;';

        $form_search = new \Adianti\Widget\Form\TForm('form_search_palpite');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($palpite, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        $panel->addHeaderActionLink('', new TAction(['PalpiteCotadoForm', 'onEdit'],  ['register_state' => 'false']), 'fa:plus');
        $this->filter_label = $panel->addHeaderActionLink(_t('Filters'), new TAction([$this, 'onShowCurtainFilters']), 'fa:filter');
        
        $dropdown = new TDropDown(_t('Export'), 'fa:list');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( _t('Save as CSV'), new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table fa-fw blue' );
        $dropdown->addAction( _t('Save as PDF'), new TAction([$this, 'onExportPDF'], ['register_state' => 'false', 'static'=>'1']), 'far:file-pdf fa-fw red' );
        $dropdown->addAction( _t('Save as XML'), new TAction([$this, 'onExportXML'], ['register_state' => 'false', 'static'=>'1']), 'fa:code fa-fw green' );
        $panel->addHeaderWidget( $dropdown );

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

    public function onAfterSearch($datagrid, $options)
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
            $obj->palpite = TSession::getValue(get_class($this).'_filter_data')->palpite;
            TForm::sendData('form_search_palpite', $obj);
        }
    }

    public static function onChangeLimit($param)
    {
        TSession::setValue(__CLASS__ . '_limit', $param['limit'] );
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
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

    public function onTurnOnOff($param)
    {
        try
        {
            TTransaction::open('unit_a');
            $palpite_cotado = PalpiteCotado::find($param['palpite_cotado_id']);
            if ($palpite_cotado instanceof PalpiteCotado) {
                $palpite_cotado->ativo = $palpite_cotado->ativo == 'S' ? 'N' : 'S';
                $palpite_cotado->store();
            }

            TTransaction::close();

            $this->onReload($param);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}