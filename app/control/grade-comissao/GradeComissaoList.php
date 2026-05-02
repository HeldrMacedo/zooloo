<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class GradeComissaoList extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('permission');
        parent::setActiveRecord('GradeComissao');
        parent::setDefaultOrder('descricao', 'asc');
        parent::addFilterField('descricao', 'like', 'descricao');

        $this->form = new BootstrapFormBuilder('form_search_grade_comissao');
        $this->form->setFormTitle('Grade de Comissão');

        $descricao = new TEntry('descricao');
        $descricao->setSize('100%');

        $this->form->addFields([new TLabel('Descrição:')], [$descricao]);
        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $btn = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $btn->class = 'btn btn-sm btn-primary';
        $btn_clear = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $btn_clear->class = 'btn btn-sm btn-default';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id   = new TDataGridColumn('grade_comissao_id', 'ID', 'center', '8%');
        $col_desc = new TDataGridColumn('descricao', 'Descrição', 'left');

        $order_id = new TAction([$this, 'onReload']);
        $order_id->setParameter('order', 'grade_comissao_id');
        $col_id->setAction($order_id);

        $order_desc = new TAction([$this, 'onReload']);
        $order_desc->setParameter('order', 'descricao');
        $col_desc->setAction($order_desc);

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_desc);

        $action_itens = new TDataGridAction(['GradeComissaoItensForm', 'onLoad']);
        $action_itens->setButtonClass('btn btn-default btn-sm');
        $action_itens->setLabel('Itens');
        $action_itens->setImage('fa:list blue');
        $action_itens->setField('grade_comissao_id');
        $this->datagrid->addAction($action_itens);

        $action_edit = new TDataGridAction(['GradeComissaoForm', 'onEdit'], ['register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default btn-sm');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue');
        $action_edit->setField('grade_comissao_id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setButtonClass('btn btn-default btn-sm');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('grade_comissao_id');
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        $panel->addHeaderActionLink('', new TAction(['GradeComissaoForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);
    }

    public function onClear($param)
    {
        TSession::setValue(__CLASS__.'_filter_data', null);
        $this->form->clear();
        $this->onReload();
    }

    public function onDelete($param)
    {
        $action = new TAction([$this, 'onConfirmDelete']);
        $action->setParameter('key', $param['grade_comissao_id']);
        new TQuestion('Deseja excluir esta grade de comissão? Os itens associados também serão excluídos.', $action);
    }

    public function onConfirmDelete($param)
    {
        try {
            TTransaction::open('permission');
            $grade = new GradeComissao($param['key']);

            // Remove itens antes de remover a grade (FK constraint)
            foreach ($grade->get_itens() as $item) {
                $item->delete();
            }

            $grade->delete();
            TTransaction::close();
            $this->onReload($param);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
