<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class GradeComissaoItensForm extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_grade_comissao_itens');
        $this->form->setFormTitle('Itens da Grade de Comissão');

        $grade_comissao_id = new TEntry('grade_comissao_id');
        $grade_comissao_id->setEditable(false);
        $grade_comissao_id->style = 'display:none';

        $grade_nome = new TEntry('grade_nome');
        $grade_nome->setEditable(false);
        $grade_nome->setSize('100%');

        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');
        $modalidade_id->enableSearch();
        $modalidade_id->setSize('100%');

        $comissao = new TNumeric('comissao', 2, ',', '.', false);
        $comissao->setSize('100%');
        $comissao->placeholder = '0,00';

        $this->form->addFields([$grade_comissao_id]);
        $this->form->addFields([new TLabel('Grade:')], [$grade_nome]);
        $this->form->addContent(['<hr>']);
        $this->form->addFields(
            [new TLabel('Modalidade:')], [$modalidade_id],
            [new TLabel('Comissão (%):')], [$comissao]
        );

        $this->form->addAction('Adicionar Item', new TAction([$this, 'onAddItem']), 'fa:plus green');
        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        // Datagrid de itens existentes
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_modalidade = new TDataGridColumn('modalidade->apresentacao', 'Modalidade', 'left');
        $col_comissao   = new TDataGridColumn('comissao', 'Comissão (%)', 'right', '20%');

        $col_comissao->setTransformer(function($value) {
            return number_format($value, 2, ',', '.') . '%';
        });

        $this->datagrid->addColumn($col_modalidade);
        $this->datagrid->addColumn($col_comissao);

        $action_del = new TDataGridAction([$this, 'onDeleteItem']);
        $action_del->setButtonClass('btn btn-default btn-sm');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red');
        $action_del->setField('grade_comissao_itens_id');
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Itens cadastrados');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add($panel);
        parent::add($container);
    }

    public function onLoad($param)
    {
        if (empty($param['grade_comissao_id'])) {
            return;
        }
        try {
            TTransaction::open('permission');
            $grade = new GradeComissao($param['grade_comissao_id']);
            TTransaction::close();

            $obj = new stdClass;
            $obj->grade_comissao_id = $grade->grade_comissao_id;
            $obj->grade_nome        = $grade->descricao;
            $this->form->setData($obj);

            $this->loadDatagrid($grade->grade_comissao_id);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function loadDatagrid($grade_comissao_id)
    {
        try {
            TTransaction::open('permission');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('grade_comissao_id', '=', $grade_comissao_id));
            $itens = (new TRepository('GradeComissaoItens'))->load($criteria);
            TTransaction::close();

            $this->datagrid->clear();
            if ($itens) {
                foreach ($itens as $item) {
                    $this->datagrid->addItem($item);
                }
            }
            $this->datagrid->updatePage();
        } catch (Exception $e) {
            TTransaction::rollback();
        }
    }

    public function onAddItem($param)
    {
        try {
            $data = $this->form->getData();

            if (empty($data->grade_comissao_id)) {
                throw new Exception('Grade não identificada.');
            }
            if (empty($data->modalidade_id)) {
                throw new Exception('Selecione uma modalidade.');
            }
            if ($data->comissao === '' || $data->comissao === null) {
                throw new Exception('Informe o percentual de comissão.');
            }

            $comissao = (float) str_replace(['.', ','], ['', '.'], $data->comissao);
            if ($comissao < 0 || $comissao > 100) {
                throw new Exception('Comissão deve estar entre 0% e 100%.');
            }

            TTransaction::open('permission');

            // Validar unicidade (grade_comissao_id + modalidade_id)
            $criteria = new TCriteria;
            $criteria->add(new TFilter('grade_comissao_id', '=', $data->grade_comissao_id));
            $criteria->add(new TFilter('modalidade_id',    '=', $data->modalidade_id));
            $existentes = (new TRepository('GradeComissaoItens'))->load($criteria);

            if (!empty($existentes)) {
                throw new Exception('Já existe comissão para esta modalidade nesta grade.');
            }

            $item = new GradeComissaoItens;
            $item->grade_comissao_id = $data->grade_comissao_id;
            $item->modalidade_id     = $data->modalidade_id;
            $item->comissao          = $comissao;
            $item->store();

            TTransaction::close();

            // Limpa campos de entrada e recarrega datagrid
            $obj = new stdClass;
            $obj->grade_comissao_id = $data->grade_comissao_id;
            $obj->grade_nome        = $data->grade_nome;
            $this->form->setData($obj);

            $this->loadDatagrid($data->grade_comissao_id);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onDeleteItem($param)
    {
        $data = $this->form->getData();
        $action = new TAction([$this, 'onConfirmDeleteItem']);
        $action->setParameter('item_id', $param['grade_comissao_itens_id']);
        $action->setParameter('grade_comissao_id', $data->grade_comissao_id);
        new TQuestion('Deseja remover este item?', $action);
    }

    public function onConfirmDeleteItem($param)
    {
        try {
            TTransaction::open('permission');
            $item = new GradeComissaoItens($param['item_id']);
            $item->delete();
            TTransaction::close();

            $this->loadDatagrid($param['grade_comissao_id']);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
