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
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class QuininhaResultadoList extends TPage
{
    
    const FILTRO_BANCA = IntJogo::FILTRO_BANCA_QUINHA;
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_search_quininha_resultado');
        $this->form->setFormTitle('Quininha — Resultado dos Sorteios');

        $data_sorteio = new TDate('data_sorteio');
        $data_sorteio->setMask('dd/mm/yyyy');
        $data_sorteio->setDatabaseMask('yyyy-mm-dd');

        $this->form->addFields([new TLabel('Data do Sorteio:')], [$data_sorteio]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id        = new TDataGridColumn('sorteio_id',        'ID',                 'center', '8%');
        $col_extracao  = new TDataGridColumn('descricao',         'Extração',           'left',   '20%');
        $col_data      = new TDataGridColumn('data_sorteio',      'Data Sorteio',       'center', '12%');
        $col_hora      = new TDataGridColumn('hora_sorteio',      'Hora Sorteio',       'center', '12%');
        $col_situacao  = new TDataGridColumn('situacao',          'Situação',           'center', '10%');
        $col_numeros   = new TDataGridColumn('numeros_sorteados', 'Números Sorteados',  'left',   '28%');

        foreach ([$col_id, $col_extracao, $col_data, $col_hora, $col_situacao, $col_numeros] as $c) {
            $this->datagrid->addColumn($c);
        }

        $col_data->setTransformer(fn($v) => TDate::date2br($v));

        $col_situacao->setTransformer(function ($value) {
            if ($value === 'A') return "<span class='label label-success'>Aberto</span>";
            if ($value === 'F') return "<span class='label label-danger'>Encerrado</span>";
            return $value;
        });

        $col_numeros->setTransformer(function ($value) {
            if (empty($value)) {
                return "<span class='text-muted'>Sem resultado</span>";
            }
            return $value;
        });

        $action_edit = new TDataGridAction(['QuininhaResultadoForm', 'onEdit'], ['key' => '{sorteio_id}']);
        $action_edit->setUseButton(true);
        $action_edit->setButtonClass('btn btn-default btn-sm');
        $action_edit->setLabel('Resultado');
        $action_edit->setImage('fa:edit blue');
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

    public static function buildCriteria($filter_data, ?array $param = null): TCriteria
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('filtro_banca', '=', self::FILTRO_BANCA));

        if (!empty($filter_data->data_sorteio)) {
            $criteria->add(new TFilter('data_sorteio', '=', $filter_data->data_sorteio));
        }
        if (!empty($filter_data->extracao_id)) {
            $criteria->add(new TFilter('extracao_id', '=', $filter_data->extracao_id));
        }

        $criteria->setProperty('order', 'data_sorteio desc, hora_sorteio desc');
        if ($param !== null) {
            $criteria->setProperties($param);
        }
        return $criteria;
    }

    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        $this->form->setData($data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onClear()
    {
        TSession::setValue(__CLASS__ . '_filter_data', null);
        $this->form->clear();
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('permission');

            $filter_data = TSession::getValue(__CLASS__ . '_filter_data') ?: new stdClass();
            $limit = 10;

            $criteria = self::buildCriteria($filter_data, $param);
            $criteria->setProperty('limit', $limit);

            $repository = new TRepository('VwSorteio');
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $criteria->setProperty('order', null);
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
            $this->loaded = true;
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        if (!$this->loaded && (!isset($_GET['method']) || !in_array($_GET['method'], ['onReload', 'onSearch', 'onClear']))) {
            $this->onReload();
        }
        parent::show();
    }
}
