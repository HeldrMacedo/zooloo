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

class ApuracaoList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_apuracao');
        $this->form->setFormTitle('Apuração por Sorteio');

        $data_ini    = new TDate('data_ini');
        $data_fim    = new TDate('data_fim');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('d/m/Y'));
        $data_fim->setValue(date('d/m/Y'));

        foreach ([$area_id, $extracao_id] as $f) {
            $f->setSize('100%'); $f->setDefaultOption(true);
        }

        $this->form->addFields([new TLabel('Data Ini:')], [$data_ini], [new TLabel('Data Fim:')], [$data_fim]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Extração:')], [$extracao_id]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_numero   = new TDataGridColumn('sorteio_numero', 'Nº', 'center', '7%');
        $col_data     = new TDataGridColumn('data_sorteio', 'Data', 'center', '9%');
        $col_extracao = new TDataGridColumn('extracao', 'Extração', 'left', '16%');
        $col_numeros  = new TDataGridColumn('numeros_sorteados', 'Números Sorteados', 'center', '16%');
        $col_situacao = new TDataGridColumn('situacao', 'Status', 'center', '8%');
        $col_total    = new TDataGridColumn('total_apostado', 'Total Apost.', 'right', '11%');
        $col_comissao = new TDataGridColumn('comissao', 'Comissão', 'right', '11%');
        $col_liquido  = new TDataGridColumn('liquido', 'Líquido', 'right', '11%');
        $col_premio   = new TDataGridColumn('total_premio', 'Total Prêmio', 'right', '11%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        foreach ([$col_total,$col_comissao,$col_liquido,$col_premio] as $c) {
            $c->setTransformer($fmt_brl);
        }
        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y', strtotime($v)) : '');
        $col_numero->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));
        $col_situacao->setTransformer(function($v) {
            return $v === 'F'
                ? "<span class='label label-default'>Encerrado</span>"
                : "<span class='label label-success'>Aberto</span>";
        });
        $col_numeros->setTransformer(fn($v) => $v ?: '<em class="text-muted">—</em>');

        foreach ([$col_numero,$col_data,$col_extracao,$col_numeros,$col_situacao,$col_total,$col_comissao,$col_liquido,$col_premio] as $c) {
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

            $whereMs  = [];
            $whereJb  = ["js.cancelado = 'N'"];
            $params   = [];

            if (!empty($filter['data_ini'])) {
                $whereMs[] = 'ms.data_sorteio >= :data_ini';
                $params[':data_ini'] = $filter['data_ini'];
            }
            if (!empty($filter['data_fim'])) {
                $whereMs[] = 'ms.data_sorteio <= :data_fim';
                $params[':data_fim'] = $filter['data_fim'];
            }
            if (!empty($filter['extracao_id'])) {
                $whereMs[] = 'ms.extracao_id = :extracao_id';
                $params[':extracao_id'] = $filter['extracao_id'];
            }
            if (!empty($filter['area_id'])) {
                $whereJb[] = 'js.area_id = :area_id';
                $params[':area_id'] = $filter['area_id'];
            }

            $condMs = $whereMs ? 'WHERE ' . implode(' AND ', $whereMs) : '';
            $condJb = implode(' AND ', $whereJb);

            $sql = "
                SELECT
                    ms.sorteio_numero,
                    ms.data_sorteio,
                    e.descricao AS extracao,
                    ms.numeros_sorteados,
                    ms.situacao,
                    COALESCE(SUM(js.total_sorteio), 0) AS total_apostado,
                    COALESCE(SUM(js.comissao_sorteio), 0) AS comissao,
                    COALESCE(SUM(js.total_sorteio - js.comissao_sorteio), 0) AS liquido,
                    COALESCE(SUM(js.sorteado_valor), 0) AS total_premio
                FROM mov_sorteio ms
                JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                LEFT JOIN vw_vendajb js ON js.sorteio_id = ms.sorteio_id AND {$condJb}
                {$condMs}
                GROUP BY ms.sorteio_id, ms.sorteio_numero, ms.data_sorteio, e.descricao, ms.numeros_sorteados, ms.situacao
                ORDER BY ms.data_sorteio DESC, e.descricao
            ";

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
