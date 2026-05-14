<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class LotinhaExtracaoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_lotinha_extracao');
        $this->form->setFormTitle('Lotinha — Extrações');

        $descricao = new TEntry('descricao');
        $descricao->setSize('100%');

        $this->form->addFields([new TLabel('Descrição:')], [$descricao]);
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_id        = new TDataGridColumn('extracao_id',      'ID',           'left');
        $col_descricao = new TDataGridColumn('descricao',        'Descrição',    'left');
        $col_mobile    = new TDataGridColumn('descricao_mobile', 'Desc. Mobile', 'left');
        $col_hora      = new TDataGridColumn('hora_limite',      'Hora Limite',  'center');
        $col_seg       = new TDataGridColumn('segunda',          'Seg',          'center');
        $col_ter       = new TDataGridColumn('terca',            'Ter',          'center');
        $col_qua       = new TDataGridColumn('quarta',           'Qua',          'center');
        $col_qui       = new TDataGridColumn('quinta',           'Qui',          'center');
        $col_sex       = new TDataGridColumn('sexta',            'Sex',          'center');
        $col_sab       = new TDataGridColumn('sabado',           'Sáb',          'center');
        $col_dom       = new TDataGridColumn('domingo',          'Dom',          'center');

        $fmt_sim_nao = fn($v) => $v === 'S'
            ? "<span class='label label-success'>S</span>"
            : "<span class='label label-default'>N</span>";

        foreach ([$col_seg, $col_ter, $col_qua, $col_qui, $col_sex, $col_sab, $col_dom] as $c) {
            $c->setTransformer($fmt_sim_nao);
        }

        foreach ([$col_id, $col_descricao, $col_mobile, $col_hora, $col_seg, $col_ter, $col_qua, $col_qui, $col_sex, $col_sab, $col_dom] as $c) {
            $this->datagrid->addColumn($c);
        }

        $action_edit = new TDataGridAction(['LotinhaExtracaoForm', 'onEdit'], ['key' => '{extracao_id}', 'register_state' => 'false']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('far:edit blue');
        $this->datagrid->addAction($action_edit);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        parent::add($container);
    }

    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        $this->form->setData($data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onReload($param = null)
    {
        try
        {
            TTransaction::open('permission');

            $repository = new TRepository('Extracao');
            $limit = 10;

            $criteria = new TCriteria;
            $criteria->add(new TFilter('filtro_banca', '=', 4));

            $filter_data = TSession::getValue(__CLASS__ . '_filter_data');
            if (!empty($filter_data->descricao)) {
                $criteria->add(new TFilter('descricao', 'like', '%' . $filter_data->descricao . '%'));
            }

            $criteria->setProperty('order', 'descricao');
            $criteria->setProperty('limit', $limit);
            $criteria->setProperties($param);

            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'], ['onReload', 'onSearch'])))) {
            $this->onReload();
        }
        parent::show();
    }
}
