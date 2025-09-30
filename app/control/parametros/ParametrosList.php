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

class ParametrosList extends TStandardList
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
        parent::setActiveRecord('Parametros');
        parent::setDefaultOrder('parametros_id');
        parent::addFilterField('nome_banca', 'like', 'nome_banca');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);
        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        $this->form = new BootstrapFormBuilder('form_search_parametros_list');
        $this->form->setFormTitle('Parâmetros do Sistema');

        $nome_banca = new TEntry('nome_banca');

        $this->form->addFields([new TLabel('Nome da Banca')], [$nome_banca]);

        $nome_banca->setSize('100%');
        
        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('parametros_id', 'ID', 'center', 50);
        $column_nome_banca = new TDataGridColumn('nome_banca', 'Nome Banca', 'left');
        $column_valor_milhar = new TDataGridColumn('valor_milhar_brinde', 'Milhar Brinde a partir de:', 'right');
        $column_qtde_mi = new TDataGridColumn('qtde_num_mi', 'Qtd Milhar Invertida', 'center');
        $column_qtde_ci = new TDataGridColumn('qtde_num_ci', 'Qtd Centena Invertida', 'center');
        $column_qtde_mci = new TDataGridColumn('qtde_num_mci', 'Qtd Milhar Centena Invertida', 'center');

        // Transformador para valor monetário
        $column_valor_milhar->setTransformer(function($value, $object, $row) {
            if ($value) {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return '';
        });

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome_banca);
        $this->datagrid->addColumn($column_valor_milhar);
        $this->datagrid->addColumn($column_qtde_mi);
        $this->datagrid->addColumn($column_qtde_ci);
        $this->datagrid->addColumn($column_qtde_mci);

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'parametros_id');
        $column_id->setAction($order_id);

        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome_banca');
        $column_nome_banca->setAction($order_nome);

        $action_edit = new TDataGridAction(['ParametrosForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('parametros_id');
        $this->datagrid->addAction($action_edit);

        // Não adicionar ação de delete pois só pode haver um registro
        // $action_del = new TDataGridAction(array($this, 'onDelete'));

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

        $form_search = new \Adianti\Widget\Form\TForm('form_search_nome');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($nome_banca, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        // Botão para adicionar/editar (sempre vai para edição do único registro)
        $panel->addHeaderActionLink('', new TAction(['ParametrosForm', 'onEdit'],  ['register_state' => 'false']), 'fa:plus');
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
        $dropdown->addAction( '10', new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '10']) );
        $dropdown->addAction( '20', new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '20']) );
        $dropdown->addAction( '50', new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '50']) );
        $dropdown->addAction( '100', new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '100']) );
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
            $obj->nome_banca = TSession::getValue(get_class($this).'_filter_data')->nome_banca;
            TForm::sendData('form_search_nome', $obj);
        }
    }

    // Sobrescrever o método onDelete para impedir exclusão
    public function onDelete($param)
    {
        new TMessage('error', 'Não é possível excluir o registro de parâmetros do sistema.');
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

    
}