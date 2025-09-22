<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
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

class AreaCotacaoList extends TStandardList
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
        parent::setActiveRecord('AreaCotacao');
        parent::setDefaultOrder('area_cotacao_id');
        parent::addFilterField('area_cotacao_id', '=', 'area_cotacao_id');
        parent::addFilterField('area_id', '=', 'area_id');
        parent::addFilterField('extracao_id', '=', 'extracao_id');
        parent::addFilterField('modalidade_id', '=', 'modalidade_id');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        $this->form = new BootstrapFormBuilder('form_search_area_cotacao_list');
        $this->form->setFormTitle('Área Cotação');

        // Filtrar apenas áreas ativas
        $criteriaArea = new TCriteria;
        $criteriaArea->add(new TFilter('ativo', '=', 'S'));

        // Filtrar apenas extrações ativas
        $criteriaExtracao = new TCriteria;
        $criteriaExtracao->add(new TFilter('ativo', '=', 'S'));

        // Filtrar apenas modalidades ativas
        $criteriaModalidade = new TCriteria;
        $criteriaModalidade->add(new TFilter('ativo', '=', 'S'));

        $id = new TEntry('area_cotacao_id');
        $area = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao', 'descricao', $criteriaArea);
        $extracao = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao', 'descricao', $criteriaExtracao);
        $modalidade = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', '{jogo_id} - {apresentacao}', 'apresentacao', $criteriaModalidade);

        $area->enableSearch();
        $extracao->enableSearch();
        $modalidade->enableSearch();

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Área')], [$area]);
        $this->form->addFields([new TLabel('Extração')], [$extracao]);
        $this->form->addFields([new TLabel('Modalidade')], [$modalidade]);

        $id->setSize('30%');
        $area->setSize('100%');
        $extracao->setSize('100%');
        $modalidade->setSize('100%');
        
        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('area_cotacao_id', 'ID', 'center', '8%');
        $column_area = new TDataGridColumn('area->descricao', 'Área', 'left', '25%');
        $column_extracao = new TDataGridColumn('extracao->descricao', 'Extração', 'left', '25%');
        $column_modalidade = new TDataGridColumn('modalidade->apresentacao', 'Modalidade', 'left', '25%');
        $column_multiplicador = new TDataGridColumn('multiplicador', 'Multiplicador', 'right', '17%');

        // Transformador para extração (mostrar "TODAS" quando for NULL)
        $column_extracao->setTransformer(function($value, $object, $row) {
            return $value ?: 'TODAS';
        });

        // Transformador para multiplicador (formato moeda)
        $column_multiplicador->setTransformer(function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_area);
        $this->datagrid->addColumn($column_extracao);
        $this->datagrid->addColumn($column_modalidade);
        $this->datagrid->addColumn($column_multiplicador);

        // Ordenação das colunas
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'area_cotacao_id');
        $column_id->setAction($order_id);

        $order_area = new TAction(array($this, 'onReload'));
        $order_area->setParameter('order', 'area->descricao');
        $column_area->setAction($order_area);

        // Ações do datagrid
        $action_edit = new TDataGridAction(['AreaCotacaoForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('area_cotacao_id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('area_cotacao_id');
        $this->datagrid->addAction($action_del);

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

        $form_search = new \Adianti\Widget\Form\TForm('form_search_area');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($area, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        $panel->addHeaderActionLink('', new TAction(['AreaCotacaoForm', 'onEdit'],  ['register_state' => 'false']), 'fa:plus');
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
            $obj->area_id = TSession::getValue(get_class($this).'_filter_data')->area_id;
            TForm::sendData('form_search_area', $obj);
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

    /**
     * Método customizado para carregar dados com relacionamentos
     */
    public function onAfterLoad($objects, $options = null)
    {
        if ($objects)
        {
            foreach ($objects as $object)
            {
                // Carregar relacionamentos
                $object->area = $object->get_area();
                $object->extracao = $object->get_extracao();
                $object->modalidade = $object->get_modalidade();
                
                // Criar descrição da modalidade
                if ($object->modalidade) {
                    $jogo = $object->modalidade->get_jogo();
                    $object->modalidade_descricao = $jogo ? $jogo->descricao : $object->modalidade->apresentacao;
                }
            }
        }
        
        return $objects;
    }

    /**
     * Excluir registro
     */
    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);

        new TQuestion('Deseja realmente excluir este registro?', $action);
    }

    /**
     * Confirmar exclusão
     */
    public function Delete($param)
    {
        try
        {
            $key = $param['area_cotacao_id'];
            TTransaction::open('permission');
            $object = new AreaCotacao($key, FALSE);
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