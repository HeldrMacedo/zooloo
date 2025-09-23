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
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class AreaLimiteList extends TStandardList
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
        parent::setActiveRecord('AreaLimite');
        parent::setDefaultOrder('area_limite_id');
        parent::addFilterField('area_limite_id', '=', 'area_limite_id');
        parent::addFilterField('area_id', '=', 'area_id');
        parent::addFilterField('modalidade_id', '=', 'modalidade_id');
        parent::addFilterField('limite_palpite', '>=', 'limite_palpite_min');
        parent::addFilterField('limite_palpite', '<=', 'limite_palpite_max');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);
        parent::setAfterLoadCallback([$this, 'onAfterLoad']);

        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        // Formulário de busca
        $this->form = new BootstrapFormBuilder('form_search_area_limite_list');
        $this->form->setFormTitle('Área Limite');

        $id = new TEntry('area_limite_id');
        $area_id = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');
        $limite_palpite_min = new TEntry('limite_palpite_min');
        $limite_palpite_max = new TEntry('limite_palpite_max');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Área')], [$area_id]);
        $this->form->addFields([new TLabel('Modalidade')], [$modalidade_id]);
        $this->form->addFields([new TLabel('Limite Mín.')], [$limite_palpite_min], [new TLabel('Limite Máx.')], [$limite_palpite_max]);

        $id->setSize('30%');
        $area_id->setSize('100%');
        $modalidade_id->setSize('100%');
        $limite_palpite_min->setSize('100%');
        $limite_palpite_max->setSize('100%');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        // Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('area_limite_id', 'Id', 'center', 50);
        $column_area = new TDataGridColumn('area->descricao', 'Área', 'left');
        $column_modalidade = new TDataGridColumn('modalidade->apresentacao', 'Modalidade', 'left');
        $column_limite = new TDataGridColumn('limite_palpite', 'Limite Palpite', 'right');

        // Transformador para formatação de moeda
        $column_limite->setTransformer(function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_area);
        $this->datagrid->addColumn($column_modalidade);
        $this->datagrid->addColumn($column_limite);

        // Ordenação
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'area_limite_id');
        $column_id->setAction($order_id);

        $order_area = new TAction(array($this, 'onReload'));
        $order_area->setParameter('order', 'area_id');
        $column_area->setAction($order_area);

        // Ações do datagrid
        $action_edit = new TDataGridAction(['AreaLimiteForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('area_limite_id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('area_limite_id');
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        // Navegação de páginas
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        // Panel principal
        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        // Busca rápida
        $area_search = new TEntry('area_search');
        $btnf = TButton::create('find', [$this, 'onQuickSearch'], '', 'fa:search');
        $btnf->style = 'height: 37px; margin-right:4px;';

        $form_search = new \Adianti\Widget\Form\TForm('form_search_area');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($area_search, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        // Botões do header
        $panel->addHeaderActionLink('', new TAction(['AreaLimiteForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus');
        $this->filter_label = $panel->addHeaderActionLink(_t('Filters'), new TAction([$this, 'onShowCurtainFilters']), 'fa:filter');

        // Dropdown de exportação
        $dropdown = new TDropDown(_t('Export'), 'fa:list');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction(_t('Save as CSV'), new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table fa-fw blue');
        $dropdown->addAction(_t('Save as PDF'), new TAction([$this, 'onExportPDF'], ['register_state' => 'false', 'static'=>'1']), 'far:file-pdf fa-fw red');
        $dropdown->addAction(_t('Save as XML'), new TAction([$this, 'onExportXML'], ['register_state' => 'false', 'static'=>'1']), 'fa:code fa-fw green');
        $panel->addHeaderWidget($dropdown);

        // Dropdown de limite de registros
        $dropdown = new TDropDown(TSession::getValue(__CLASS__ . '_limit') ?? '10', '');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction(10, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '10']));
        $dropdown->addAction(20, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '20']));
        $dropdown->addAction(50, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '50']));
        $dropdown->addAction(100, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '100']));
        $dropdown->addAction(1000, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '1000']));
        $panel->addHeaderWidget($dropdown);

        // Atualização do label de filtros
        if (TSession::getValue(get_class($this).'_filter_counter') > 0)
        {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel(_t('Filters') . ' ('. TSession::getValue(get_class($this).'_filter_counter').')');
        }

        // Container principal
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($panel);

        parent::add($container);
    }

    public function onAfterLoad($objects)
    {
        foreach ($objects as $object)
        {
            $object->area = $object->get_area();
            $object->modalidade = $object->get_modalidade();
        }
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
            $obj->area_search = TSession::getValue(get_class($this).'_filter_data')->area_id ?? '';
            TForm::sendData('form_search_area', $obj);
        }
    }

    public function onQuickSearch($param)
    {
        $data = new stdClass;
        $data->area_id = $param['area_search'];
        $this->onSearch($data);
    }

    public static function onChangeLimit($param)
    {
        TSession::setValue(__CLASS__ . '_limit', $param['limit']);
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

            $embed = new self;
            $embed->form->addHeaderWidget($btn_close);

            $page->add($embed->form);
            $page->show();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onDelete($param)
    {
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param);
        
        new TQuestion('Deseja realmente excluir este registro?', $action);
    }

    public function Delete($param)
    {
        try
        {
            $key = $param['key'];
            TTransaction::open('permission');
            $object = new AreaLimite($key, FALSE);
            $object->delete();
            TTransaction::close();
            
            $this->onReload($param);
            new TMessage('info', 'Registro excluído com sucesso');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}