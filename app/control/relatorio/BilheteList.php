<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class BilheteList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_bilhete_list');
        $this->form->setFormTitle('Listagem de Bilhetes Gerados');

        $data_ini    = new TDate('data_ini');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');

        $data_ini->setMask('dd/mm/yyyy');
        $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));

        foreach ([$area_id, $extracao_id] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }

        $this->form->addFields(
            [new TLabel('Data Inicial:')], [$data_ini],
            [new TLabel('Área:')], [$area_id],
            [new TLabel('Extração:')], [$extracao_id]
        );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
        }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_extracao   = new TDataGridColumn('extracao', 'Extração', 'left', '15%');
        $col_vendedor   = new TDataGridColumn('vendedor', 'Vendedor', 'left', '15%');
        $col_nsu        = new TDataGridColumn('nsu', 'NSU', 'center', '8%');
        $col_poule      = new TDataGridColumn('poule', 'Poule', 'center', '8%');
        $col_data       = new TDataGridColumn('data_hora', 'Data', 'center', '14%');
        $col_jogo       = new TDataGridColumn('jogo', 'Jogo', 'left', '18%');
        $col_apostado   = new TDataGridColumn('valor_palpites', 'Apostado', 'right', '11%');
        $col_total_apos = new TDataGridColumn('total_sorteio', 'Total Apostado', 'right', '11%');

        $col_nsu->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));
        $col_poule->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));
        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y H:i:s', strtotime($v)) : '');

        $col_jogo->setTransformer(function($v, $object) {
            $palpites = str_replace(',', ' ', $object->palpites ?? '');
            return trim(($object->modalidade ?? '') . ' | ' . $palpites);
        });

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_apostado->setTransformer($fmt_brl);
        $col_total_apos->setTransformer($fmt_brl);

        foreach ([$col_extracao, $col_vendedor, $col_nsu, $col_poule, $col_data, $col_jogo, $col_apostado, $col_total_apos] as $c) {
            $this->datagrid->addColumn($c);
        }
        $this->datagrid->createModel();

        $this->footerTotal = new TElement('div');
        $this->footerTotal->style = 'text-align:right;padding:8px;font-weight:bold;';

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->footerTotal);
        $this->panel = $panel;

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        parent::add($container);
    }

    public function onSearch($param)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filter', (array) $data);
        $this->form->setData($data);
        $this->onReload($param);
    }

    public function onClear($param)
    {
        TSession::setValue(__CLASS__.'_filter_data', null);
        TSession::setValue(__CLASS__.'_filter', null);
        $this->form->clear();
        $this->datagrid->clear();
        $this->footerTotal->clearChildren();

        $data = (object) [
            'data_ini'    => date('Y-m-d'),
            'area_id'     => '',
            'extracao_id' => ''
        ];
        $this->form->setData($data);
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) {
            $filter = ['data_ini' => date('Y-m-d')];
        }

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $data_val    = !empty($filter['data_ini']) ? $filter['data_ini'] : date('Y-m-d');
            $data_inicio = $data_val . ' 00:00:00';
            $data_fim    = date('Y-m-d 00:00:00', strtotime($data_val . ' +1 day'));

            $where  = [
                'vmjb.data_hora >= :data_inicio',
                'vmjb.data_hora < :data_fim',
                "vmjb.situacao = 'ATIVO'"
            ];
            $params = [
                ':data_inicio' => $data_inicio,
                ':data_fim'    => $data_fim,
            ];

            if (!empty($filter['area_id'])) {
                $where[] = 'vmjb.area_id = :area_id';
                $params[':area_id'] = (int) $filter['area_id'];
            }

            if (!empty($filter['extracao_id'])) {
                $where[] = 'vmjb.extracao_id = :extracao_id';
                $params[':extracao_id'] = (int) $filter['extracao_id'];
            }

            $sql = "
                SELECT
                    vmjb.extracao,
                    vmjb.vendedor,
                    vmjb.nsu,
                    vmjb.poule,
                    vmjb.data_hora,
                    vmjb.valor_palpites,
                    vmjb.total_sorteio,
                    vmjb.modalidade,
                    vmjb.palpites
                FROM vw_vendajb vmjb
                WHERE " . implode(' AND ', $where) . "
                ORDER BY vmjb.nsu DESC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $soma_apostado = 0;
            $soma_total    = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                $soma_apostado += (float)$row->valor_palpites;
                $soma_total    += (float)$row->total_sorteio;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add("Total Apostado: {$fmt($soma_apostado)} | Total Geral: {$fmt($soma_total)}");

            $this->loaded = true;

            if (empty($rows)) {
                new TMessage('info', 'Não existe resultado para esta data!');
            }

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
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

