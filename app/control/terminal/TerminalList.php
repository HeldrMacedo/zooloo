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

class TerminalList extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;
    protected $filter_label;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('permission');
        parent::setActiveRecord('Terminal');
        parent::setDefaultOrder('terminal_id', 'desc');
        parent::addFilterField('terminal_id', '=', 'terminal_id');
        parent::addFilterField('serial', 'like', 'serial');
        parent::addFilterField('vendedor_id', '=', 'vendedor_id');
        parent::addFilterField('tipo', '=', 'tipo');
        parent::addFilterField('ativo', '=', 'ativo');
        parent::addFilterField('multi_usuario', '=', 'multi_usuario');
        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        $this->form = new BootstrapFormBuilder('form_search_terminal');
        $this->form->setFormTitle('Terminais');

        $id = new TEntry('terminal_id');
        $serial = new TEntry('serial');
        $vendedor = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome', 'nome');
        $tipo = new TCombo('tipo');
        $tipo->addItems(['APP' => 'APP', 'POS' => 'POS', 'COLETOR' => 'Coletor']);
        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $multi = new TCombo('multi_usuario');
        $multi->addItems(['S' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Serial')], [$serial]);
        $this->form->addFields([new TLabel('Vendedor')], [$vendedor]);
        $this->form->addFields([new TLabel('Tipo')], [$tipo]);
        $this->form->addFields([new TLabel('Multi usuário')], [$multi]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $id->setSize('30%');
        $serial->setSize('100%');
        $vendedor->setSize('100%');
        $tipo->setSize('100%');
        $multi->setSize('100%');
        $ativo->setSize('100%');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('terminal_id', 'Id', 'center', 50);
        $column_serial = new TDataGridColumn('serial', 'Serial', 'left', '25%');
        $column_vendedor = new TDataGridColumn('vendedor->nome', 'Vendedor', 'left', '25%');
        $column_tipo = new TDataGridColumn('tipo', 'Tipo', 'center', '10%');
        $column_multi = new TDataGridColumn('multi_usuario', 'Multi usuário', 'center', '12%');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '10%');

        $snTransformer = function ($value) {
            $class = ($value == 'N') ? 'danger' : 'success';
            $label = ($value == 'N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class = "label label-{$class}";
            $div->style = 'text-shadow:none; font-size:10pt;';
            $div->add($label);
            return $div;
        };
        $column_multi->setTransformer($snTransformer);
        $column_ativo->setTransformer($snTransformer);

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_serial);
        $this->datagrid->addColumn($column_vendedor);
        $this->datagrid->addColumn($column_tipo);
        $this->datagrid->addColumn($column_multi);
        $this->datagrid->addColumn($column_ativo);

        $order_id = new TAction([$this, 'onReload']);
        $order_id->setParameter('order', 'terminal_id');
        $column_id->setAction($order_id);

        $order_serial = new TAction([$this, 'onReload']);
        $order_serial->setParameter('order', 'serial');
        $column_serial->setAction($order_serial);

        $action_edit = new TDataGridAction(['TerminalForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('terminal_id');
        $this->datagrid->addAction($action_edit);

        $action_onoff = new TDataGridAction([$this, 'onTurnOnOff']);
        $action_onoff->setButtonClass('btn btn-default');
        $action_onoff->setLabel(_t('Activate/Deactivate'));
        $action_onoff->setImage('fa:power-off orange');
        $action_onoff->setField('terminal_id');
        $this->datagrid->addAction($action_onoff);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        $btnf = TButton::create('find', [$this, 'onSearch'], '', 'fa:search');
        $btnf->style = 'height: 37px; margin-right:4px;';

        $form_search = new TForm('form_search_serial');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($serial, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);
        $panel->addHeaderActionLink('', new TAction(['TerminalForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus');
        $this->filter_label = $panel->addHeaderActionLink(_t('Filters'), new TAction([$this, 'onShowCurtainFilters']), 'fa:filter');

        $dropdown = new TDropDown(TSession::getValue(__CLASS__ . '_limit') ?? '10', '');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction(10, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static' => '1', 'limit' => '10']));
        $dropdown->addAction(20, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static' => '1', 'limit' => '20']));
        $dropdown->addAction(50, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static' => '1', 'limit' => '50']));
        $dropdown->addAction(100, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static' => '1', 'limit' => '100']));
        $panel->addHeaderWidget($dropdown);

        if (TSession::getValue(get_class($this).'_filter_counter') > 0) {
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
        if (TSession::getValue(get_class($this).'_filter_counter') > 0) {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel(_t('Filters') . ' ('. TSession::getValue(get_class($this).'_filter_counter').')');
        } else {
            $this->filter_label->class = 'btn btn-default';
            $this->filter_label->setLabel(_t('Filters'));
        }

        if (!empty(TSession::getValue(get_class($this).'_filter_data'))) {
            $obj = new stdClass;
            $obj->serial = TSession::getValue(get_class($this).'_filter_data')->serial ?? null;
            TForm::sendData('form_search_serial', $obj);
        }
    }

    public static function onChangeLimit($param)
    {
        TSession::setValue(__CLASS__ . '_limit', $param['limit']);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
    }

    public static function onShowCurtainFilters($param = null)
    {
        try {
            $page = TPage::create();
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('override', 'true');
            $page->setPageName(__CLASS__);

            $btn_close = new TButton('closeCurtain');
            $btn_close->onClick = "Template.closeRightPanel();";
            $btn_close->setLabel('Fechar');
            $btn_close->setImage('fas:times');

            $embed = new self;
            $embed->form->addHeaderWidget($btn_close);

            $page->add($embed->form);
            $page->show();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onTurnOnOff($param)
    {
        try {
            TTransaction::open('permission');
            $terminal = Terminal::find($param['terminal_id']);
            if ($terminal instanceof Terminal) {
                $terminal->ativo = $terminal->ativo == 'S' ? 'N' : 'S';
                $terminal->store();
            }
            TTransaction::close();
            $this->onReload($param);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
