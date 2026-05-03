<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class BilheteList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_bilhete_list');
        $this->form->setFormTitle('Listagem de Bilhetes Gerados');

        $data_ini    = new TDate('data_ini');
        $data_fim    = new TDate('data_fim');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $vendedor_id = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');
        $cancelado   = new TCombo('cancelado');
        $nsu         = new TEntry('nsu');

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('d/m/Y'));
        $data_fim->setValue(date('d/m/Y'));

        $cancelado->addItems(['' => 'TODOS', 'N' => 'Ativos', 'S' => 'Cancelados']);
        $cancelado->setValue('N');

        foreach ([$area_id, $extracao_id, $vendedor_id, $cancelado] as $f) {
            $f->setSize('100%'); $f->setDefaultOption(true);
        }
        $nsu->placeholder = 'Busca exclusiva por NSU';

        $this->form->addFields([new TLabel('Data Ini:')], [$data_ini], [new TLabel('Data Fim:')], [$data_fim]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Extração:')], [$extracao_id]);
        $this->form->addFields([new TLabel('Vendedor:')], [$vendedor_id], [new TLabel('Status:')], [$cancelado]);
        $this->form->addFields([new TLabel('NSU (exclusivo):')], [$nsu]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_nsu      = new TDataGridColumn('nsu', 'NSU', 'center', '7%');
        $col_data     = new TDataGridColumn('data_hora', 'Data/Hora', 'center', '12%');
        $col_vendedor = new TDataGridColumn('vendedor', 'Vendedor', 'left', '14%');
        $col_extracao = new TDataGridColumn('extracao', 'Extração', 'left', '13%');
        $col_palpites = new TDataGridColumn('palpites', 'Palpites', 'left', '12%');
        $col_modal    = new TDataGridColumn('apresentacao', 'Modalidade', 'left', '11%');
        $col_total    = new TDataGridColumn('total_sorteio', 'Total', 'right', '9%');
        $col_situacao = new TDataGridColumn('situacao', 'Situação', 'center', '8%');
        $col_cancel   = new TDataGridColumn('cancelado', 'Cancelado', 'center', '8%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_total->setTransformer($fmt_brl);
        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y H:i', strtotime($v)) : '');
        $col_nsu->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));
        $col_cancel->setTransformer(function($v) {
            return $v === 'S'
                ? "<span class='label label-danger'>Sim</span>"
                : "<span class='label label-success'>Não</span>";
        });
        $col_situacao->setTransformer(function($v) {
            $map = ['A' => 'Aberto', 'F' => 'Fechado', 'P' => 'Pago'];
            return $map[$v] ?? $v;
        });

        foreach ([$col_nsu,$col_data,$col_vendedor,$col_extracao,$col_palpites,$col_modal,$col_total,$col_situacao,$col_cancel] as $c) {
            $this->datagrid->addColumn($c);
        }
        $this->datagrid->createModel();

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';

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
        $this->onReload($param);
    }

    public function onClear($param)
    {
        TSession::setValue(__CLASS__.'_filter_data', null);
        TSession::setValue(__CLASS__.'_filter', null);
        $this->form->clear();
        $this->datagrid->clear();
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) return;

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $where  = [];
            $params = [];

            if (!empty($filter['nsu'])) {
                $where[] = 'nsu = :nsu';
                $params[':nsu'] = (int) $filter['nsu'];
            } else {
                if (!empty($filter['data_ini'])) {
                    $where[] = 'DATE(data_hora) >= :data_ini';
                    $params[':data_ini'] = $filter['data_ini'];
                }
                if (!empty($filter['data_fim'])) {
                    $where[] = 'DATE(data_hora) <= :data_fim';
                    $params[':data_fim'] = $filter['data_fim'];
                }
                if (!empty($filter['area_id'])) {
                    $where[] = 'area_id = :area_id';
                    $params[':area_id'] = $filter['area_id'];
                }
                if (!empty($filter['extracao_id'])) {
                    $where[] = 'extracao_id = :extracao_id';
                    $params[':extracao_id'] = $filter['extracao_id'];
                }
                if (!empty($filter['vendedor_id'])) {
                    $where[] = 'vendedor_id = :vendedor_id';
                    $params[':vendedor_id'] = $filter['vendedor_id'];
                }
                if ($filter['cancelado'] !== '') {
                    $where[] = 'cancelado = :cancelado';
                    $params[':cancelado'] = $filter['cancelado'];
                }
            }

            $cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql  = "SELECT * FROM vw_vendajb {$cond} ORDER BY data_hora DESC LIMIT 500";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
            }
            $this->datagrid->updatePage();

            if (empty($rows)) {
                new TMessage('info', 'Nenhum bilhete encontrado!');
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
