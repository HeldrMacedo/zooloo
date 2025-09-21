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

class VendedorList extends TStandardList
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
        parent::setActiveRecord('Vendedor');
        parent::setDefaultOrder('nome');
        parent::addFilterField('vendedor_id', '=', 'vendedor_id');
        parent::addFilterField('nome', 'like', 'nome');
        parent::addFilterField('area_id', '=', 'area_id');
        parent::addFilterField('coletor_id', '=', 'coletor_id');
        parent::addFilterField('ativo', '=', 'ativo');
        parent::addFilterField('pode_cancelar', '=', 'pode_cancelar');
        parent::addFilterField('exibe_comissao', '=', 'exibe_comissao');
        parent::addFilterField('exibe_premiacao', '=', 'exibe_premiacao');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        $this->form = new BootstrapFormBuilder('form_search_vendedor_list');
        $this->form->setFormTitle('Vendedores');

        $criteriaArea = new TCriteria;
        $criteriaArea->add(new TFilter('ativo', '=', 'S'));
        $criteriaColetor = new TCriteria;
        $criteriaColetor->add(new TFilter('ativo', '=', 'S'));

        $id = new TEntry('vendedor_id');
        $nome = new TEntry('nome');
        $area = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao', 'descricao', $criteriaArea);
        $coletor = new TDBCombo('coletor_id', 'permission', 'Gerente', 'coletor_id', 'nome', 'nome', $criteriaColetor);
        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');
        
        $pode_cancelar = new TCombo('pode_cancelar');
        $pode_cancelar->addItems(['S' => 'Sim', 'N' => 'Não']);
        
        $exibe_comissao = new TCombo('exibe_comissao');
        $exibe_comissao->addItems(['S' => 'Sim', 'N' => 'Não']);
        
        $exibe_premiacao = new TCombo('exibe_premiacao');
        $exibe_premiacao->addItems(['NENHUMA' => 'Nenhuma', 'TODAS' => 'Todas', 'SOMENTE_GANHADORES' => 'Somente Ganhadores']);
      

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Área')], [$area]);
        $this->form->addFields([new TLabel('Setorista')], [$coletor]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);
        $this->form->addFields([new TLabel('Pode Cancelar')], [$pode_cancelar]);
        $this->form->addFields([new TLabel('Exibe Comissão')], [$exibe_comissao]);
        $this->form->addFields([new TLabel('Exibe Premiação')], [$exibe_premiacao]);

        $id->setSize('30%');
        $nome->setSize('100%');
        $area->setSize('100%');
        $coletor->setSize('100%');
        $ativo->setSize('100%');
        $pode_cancelar->setSize('100%');
        $exibe_comissao->setSize('100%');
        $exibe_premiacao->setSize('100%');
        
        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('vendedor_id', 'Id', 'center', 50);
        $column_area = new TDataGridColumn('area->descricao', 'Área', 'left', '15%');
        $column_nome = new TDataGridColumn('nome', 'Vendedor', 'left', '20%');
        $column_login = new TDataGridColumn('usuario->login', 'Login', 'left', '15%');
        $column_comissao = new TDataGridColumn('comissao', 'Comissão', 'center', '8%');
        $column_exibe_comissao = new TDataGridColumn('exibe_comissao', 'Exibe Comissão', 'center', '10%');
        $column_limite_venda = new TDataGridColumn('limite_venda', 'Limite Venda', 'right', '12%');
        $column_pode_cancelar = new TDataGridColumn('pode_cancelar', 'Pode Cancelar', 'center', '10%');
        $column_exibe_premiacao = new TDataGridColumn('exibe_premiacao', 'Exibe Premiação', 'center', '12%');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '8%');

        // Transformadores para formatação
        $column_comissao->setTransformer(function($value, $object, $row) {
            return $value . '%';
        });

        $column_exibe_comissao->setTransformer(function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? 'NÃO' : 'SIM';
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:10pt;";
            $div->add($label);
            return $div;
        });

        $column_limite_venda->setTransformer(function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

        $column_pode_cancelar->setTransformer(function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? 'NÃO' : 'SIM';
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:10pt;";
            $div->add($label);
            return $div;
        });

        $column_exibe_premiacao->setTransformer(function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = match($value)
            {
                'S' => 'SIM',
                'U' => 'ÚLTIMO',
                default => 'NÃO',
            };
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:10pt;";
            $div->add($label);
            return $div;
        });

        $column_ativo->setTransformer(function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? 'NÃO' : 'SIM';
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:10pt;";
            $div->add($label);
            return $div;
        });

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_area);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_login);
        $this->datagrid->addColumn($column_comissao);
        $this->datagrid->addColumn($column_exibe_comissao);
        $this->datagrid->addColumn($column_limite_venda);
        $this->datagrid->addColumn($column_pode_cancelar);
        $this->datagrid->addColumn($column_exibe_premiacao);
        $this->datagrid->addColumn($column_ativo);

        // Ordenação das colunas
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'vendedor_id');
        $column_id->setAction($order_id);

        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);

        $order_area = new TAction(array($this, 'onReload'));
        $order_area->setParameter('order', 'area->descricao');
        $column_area->setAction($order_area);

        // Ações do datagrid
        $action_edit = new TDataGridAction(['VendedorForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('vendedor_id');
        $this->datagrid->addAction($action_edit);

        // $action_del = new TDataGridAction(array($this, 'onDelete'));
        // $action_del->setButtonClass('btn btn-default');
        // $action_del->setLabel(_t('Delete'));
        // $action_del->setImage('far:trash-alt red');
        // $action_del->setField('vendedor_id');
        // $this->datagrid->addAction($action_del);

        $action_onoff = new TDataGridAction(array($this, 'onTurnOnOff'));
        $action_onoff->setButtonClass('btn btn-default');
        $action_onoff->setLabel(_t('Activate/Deactivate'));
        $action_onoff->setImage('fa:power-off orange');
        $action_onoff->setField('vendedor_id');
        $this->datagrid->addAction($action_onoff);

        // Ação avançada (baseada no Angular)
        // $action_advanced = new TDataGridAction(array($this, 'onAdvanced'));
        // $action_advanced->setButtonClass('btn btn-default');
        // $action_advanced->setLabel('Avançado');
        // $action_advanced->setImage('fa:cog purple');
        // $action_advanced->setField('vendedor_id');
        // $this->datagrid->addAction($action_advanced);

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
        $form_search->add($nome, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        $panel->addHeaderActionLink('', new TAction(['VendedorForm', 'onEdit'],  ['register_state' => 'false']), 'fa:plus');
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
            $obj->nome = TSession::getValue(get_class($this).'_filter_data')->nome;
            TForm::sendData('form_search_nome', $obj);
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
            TTransaction::open('permission');
            $vendedor = Vendedor::find($param['vendedor_id']);
            if ($vendedor instanceof Vendedor) {
                $vendedor->ativo = $vendedor->ativo == 'S' ? 'N' : 'S';
                $vendedor->store();

                // Ativar/desativar usuário associado
                if ($vendedor->usuario_id) {
                    $user = SystemUser::find($vendedor->usuario_id);
                    if ($user) {
                        $user->active = $user->active == 'Y' ? 'N' : 'Y';
                        $user->store();
                    }
                }
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

    public function onAdvanced($param)
    {
        try
        {
            TTransaction::open('permission');
            $vendedor = Vendedor::find($param['vendedor_id']);
            TTransaction::close();

            if ($vendedor) {
                // Redirecionar para tela de configurações avançadas do vendedor
                AdiantiCoreApplication::loadPage('VendedorAdvancedForm', 'onEdit', ['vendedor_id' => $vendedor->vendedor_id]);
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onDelete($param)
    {
        try
        {
            TTransaction::open('permission');
            $vendedor = Vendedor::find($param['vendedor_id']);
            
            if ($vendedor) {
                // Verificar se pode deletar (baseado na lógica Java)
                // Deletar usuário associado primeiro
                if ($vendedor->usuario_id) {
                    $user = SystemUser::find($vendedor->usuario_id);
                    if ($user) {
                        $user->delete();
                    }
                }
                
                $vendedor->delete();
                
                new TMessage('info', 'Vendedor excluído com sucesso');
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
}