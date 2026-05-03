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
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class GeralComissaoList extends TPage
{
    protected $form;
    protected $datagrid;
    private   $mode = 'vendedor';

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_geral_comissao');
        $this->form->setFormTitle('Geral Comissão');

        $data_ini    = new TDate('data_ini');
        $data_fim    = new TDate('data_fim');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $vendedor_id = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('d/m/Y'));
        $data_fim->setValue(date('d/m/Y'));

        foreach ([$area_id, $extracao_id, $vendedor_id, $modalidade_id] as $f) {
            $f->setSize('100%'); $f->setDefaultOption(true);
        }

        $this->form->addFields([new TLabel('Data Ini:')], [$data_ini], [new TLabel('Data Fim:')], [$data_fim]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Extração:')], [$extracao_id]);
        $this->form->addFields([new TLabel('Vendedor:')], [$vendedor_id], [new TLabel('Modalidade:')], [$modalidade_id]);

        $this->form->addAction('Geral Vendedor', new TAction([$this, 'onSearchVendedor']), 'fa:user blue');
        $this->form->addAction('Geral Área', new TAction([$this, 'onSearchArea']), 'fa:map-marker-alt green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->buildColumns('vendedor');

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        parent::add($container);
    }

    private function buildColumns($mode)
    {
        $this->datagrid->clear();
        $label = $mode === 'vendedor' ? 'Vendedor' : 'Área';
        $col_label    = new TDataGridColumn('agrupador', $label, 'left', '20%');
        $col_extracao = new TDataGridColumn('extracao', 'Extração', 'left', '18%');
        $col_modal    = new TDataGridColumn('modalidade', 'Modalidade', 'left', '16%');
        $col_total    = new TDataGridColumn('total', 'Total', 'right', '14%');
        $col_comissao = new TDataGridColumn('comissao', 'Comissão', 'right', '14%');
        $col_liquido  = new TDataGridColumn('liquido', 'Líquido', 'right', '14%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_total->setTransformer($fmt_brl);
        $col_comissao->setTransformer($fmt_brl);
        $col_liquido->setTransformer($fmt_brl);

        foreach ([$col_label,$col_extracao,$col_modal,$col_total,$col_comissao,$col_liquido] as $c) {
            $this->datagrid->addColumn($c);
        }
        $this->datagrid->createModel();
    }

    public function onSearchVendedor($param)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filter', (array) $data);
        TSession::setValue(__CLASS__.'_mode', 'vendedor');
        $this->buildColumns('vendedor');
        $this->execSearch('vendedor', (array) $data);
    }

    public function onSearchArea($param)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filter', (array) $data);
        TSession::setValue(__CLASS__.'_mode', 'area');
        $this->buildColumns('area');
        $this->execSearch('area', (array) $data);
    }

    public function onClear($param)
    {
        TSession::setValue(__CLASS__.'_filter_data', null);
        TSession::setValue(__CLASS__.'_filter', null);
        $this->form->clear();
        $this->datagrid->clear();
    }

    private function execSearch($mode, $filter)
    {
        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $where  = ["cancelado = 'N'"];
            $params = [];

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
            if (!empty($filter['modalidade_id'])) {
                $where[] = 'modalidade_id = :modalidade_id';
                $params[':modalidade_id'] = $filter['modalidade_id'];
            }

            $cond = implode(' AND ', $where);

            if ($mode === 'vendedor') {
                $sql = "
                    SELECT
                        v.nome AS agrupador,
                        e.descricao AS extracao,
                        m.apresentacao AS modalidade,
                        SUM(p.total_sorteio) AS total,
                        SUM(p.comissao_sorteio) AS comissao,
                        SUM(p.total_sorteio - p.comissao_sorteio) AS liquido
                    FROM vw_vendajb p
                    JOIN cad_vendedor v ON v.vendedor_id = p.vendedor_id
                    JOIN cad_extracao e ON e.extracao_id = p.extracao_id
                    JOIN cad_modalidade m ON m.modalidade_id = p.modalidade_id
                    WHERE {$cond}
                    GROUP BY v.vendedor_id, v.nome, e.extracao_id, e.descricao, m.modalidade_id, m.apresentacao
                    ORDER BY v.nome, e.descricao, m.apresentacao
                ";
            } else {
                $sql = "
                    SELECT
                        a.descricao AS agrupador,
                        e.descricao AS extracao,
                        m.apresentacao AS modalidade,
                        SUM(p.total_sorteio) AS total,
                        SUM(p.comissao_sorteio) AS comissao,
                        SUM(p.total_sorteio - p.comissao_sorteio) AS liquido
                    FROM vw_vendajb p
                    JOIN cad_area a ON a.area_id = p.area_id
                    JOIN cad_extracao e ON e.extracao_id = p.extracao_id
                    JOIN cad_modalidade m ON m.modalidade_id = p.modalidade_id
                    WHERE {$cond}
                    GROUP BY a.area_id, a.descricao, e.extracao_id, e.descricao, m.modalidade_id, m.apresentacao
                    ORDER BY a.descricao, e.descricao, m.apresentacao
                ";
            }

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
                new TMessage('info', 'Não existe resultado para esta data!');
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
