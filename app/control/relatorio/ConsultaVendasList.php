<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TFilter;
use Adianti\Database\TCriteria;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ConsultaVendasList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_consulta_vendas');
        $this->form->setFormTitle('Consulta de Vendas');

        $data_ini   = new TDate('data_ini');
        $data_fim   = new TDate('data_fim');
        $area_id    = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $vendedor_id = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');
        $situacao   = new TCombo('situacao');
        $nsu        = new TEntry('nsu');

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('d/m/Y'));
        $data_fim->setValue(date('d/m/Y'));

        $situacao->addItems(['' => 'TODOS', 'ATIVO' => 'ATIVO', 'CANCELADO' => 'CANCELADO']);
        $situacao->setValue('');

        foreach ([$area_id, $extracao_id, $vendedor_id, $situacao] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }
        $nsu->placeholder = 'Busca exclusiva por NSU';

        $this->form->addFields([new TLabel('Data Ini:')], [$data_ini], [new TLabel('Data Fim:')], [$data_fim]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Extração:')], [$extracao_id]);
        $this->form->addFields([new TLabel('Vendedor:')], [$vendedor_id], [new TLabel('Situação:')], [$situacao]);
        $this->form->addFields([new TLabel('NSU (exclusivo):')], [$nsu]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_nsu       = new TDataGridColumn('nsu', 'NSU', 'center', '7%');
        $col_data      = new TDataGridColumn('data_hora', 'Data/Hora', 'center', '14%');
        $col_vendedor  = new TDataGridColumn('vendedor', 'Vendedor', 'left', '16%');
        $col_modalidade = new TDataGridColumn('apresentacao', 'Modalidade', 'left', '12%');
        $col_situacao  = new TDataGridColumn('situacao', 'Situação', 'center', '8%');
        $col_extracao  = new TDataGridColumn('extracao', 'Extração', 'left', '12%');
        $col_comissao  = new TDataGridColumn('comissao_sorteio', 'Comissão', 'right', '9%');
        $col_total     = new TDataGridColumn('total_sorteio', 'Total', 'right', '9%');
        $col_previsto  = new TDataGridColumn('previsao_premio', 'Prev. Prêmio', 'right', '9%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_comissao->setTransformer($fmt_brl);
        $col_total->setTransformer($fmt_brl);
        $col_previsto->setTransformer($fmt_brl);

        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y H:i:s', strtotime($v)) : '');

        $col_nsu->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));

        $col_situacao->setTransformer(function($v) {
            return $v === 'CANCELADO'
                ? "<span class='label label-danger'>CANCELADO</span>"
                : "<span class='label label-success'>ATIVO</span>";
        });

        foreach ([$col_nsu,$col_data,$col_vendedor,$col_modalidade,$col_situacao,$col_extracao,$col_comissao,$col_total,$col_previsto] as $c) {
            $this->datagrid->addColumn($c);
        }

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

            $where  = ['1=1'];
            $params = [];

            // Modo NSU exclusivo
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
                if (!empty($filter['situacao'])) {
                    $where[] = 'situacao = :situacao';
                    $params[':situacao'] = $filter['situacao'];
                }
            }

            $sql = 'SELECT * FROM vw_vendajb WHERE ' . implode(' AND ', $where) . ' ORDER BY data_hora DESC LIMIT 500';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $total_comissao = 0;
            $total_venda = 0;
            $total_previsto = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                if ($row->situacao !== 'CANCELADO') {
                    $total_comissao += (float)$row->comissao_sorteio;
                    $total_venda    += (float)$row->total_sorteio;
                    $total_previsto += (float)$row->previsao_premio;
                }
            }
            $this->datagrid->updatePage();

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $panel = new TPanelGroup();
            $panel->add($this->datagrid)->style = 'overflow-x:auto';
            $panel->addFooter($this->pageNavigation);
            $panel->addFooter("<div style='text-align:right;padding:8px'><strong>Comissão: {$fmt($total_comissao)} | Total: {$fmt($total_venda)} | Prev. Prêmio: {$fmt($total_previsto)}</strong></div>");

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
