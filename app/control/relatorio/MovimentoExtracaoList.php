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

class MovimentoExtracaoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_movimento_extracao');
        $this->form->setFormTitle('Movimento Geral por Extração');

        $data_ini    = new TDate('data_ini');
        $data_fim    = new TDate('data_fim');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');

        $data_ini->setMask('dd/mm/yyyy');
        $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));

        $data_fim->setMask('dd/mm/yyyy');
        $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setValue(date('Y-m-d'));

        foreach ([$area_id, $extracao_id] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }

        $this->form->addFields(
            [new TLabel('Data Inicial:')], [$data_ini],
            [new TLabel('Data Final:')], [$data_fim]
        );
        $this->form->addFields(
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

        $col_extracao = new TDataGridColumn('extracao', 'Extração', 'left', '20%');
        $col_vendedor = new TDataGridColumn('vendedor', 'Vendedor', 'left', '20%');
        $col_apurado  = new TDataGridColumn('apurado', 'Apurado', 'right', '12%');
        $col_comissao = new TDataGridColumn('comissao', 'Comissão', 'right', '12%');
        $col_liquido  = new TDataGridColumn('liquido', 'Líquido', 'right', '12%');
        $col_premio   = new TDataGridColumn('premio', 'Prêmio', 'right', '12%');
        $col_total    = new TDataGridColumn('total', 'Total', 'right', '12%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        foreach ([$col_apurado, $col_comissao, $col_liquido, $col_premio, $col_total] as $c) {
            $c->setTransformer($fmt_brl);
        }

        foreach ([$col_extracao, $col_vendedor, $col_apurado, $col_comissao, $col_liquido, $col_premio, $col_total] as $c) {
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
            'data_fim'    => date('Y-m-d'),
            'area_id'     => '',
            'extracao_id' => ''
        ];
        $this->form->setData($data);
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) {
            $filter = [
                'data_ini' => date('Y-m-d'),
                'data_fim' => date('Y-m-d')
            ];
        }

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $data_ini_val = !empty($filter['data_ini']) ? $filter['data_ini'] : date('Y-m-d');
            $data_fim_val = !empty($filter['data_fim']) ? $filter['data_fim'] : date('Y-m-d');

            $data_inicio = $data_ini_val . ' 00:00:00';
            $data_fim    = date('Y-m-d 00:00:00', strtotime($data_fim_val . ' +1 day'));

            $where = [
                "mb.data_hora >= :data_inicio",
                "mb.data_hora < :data_fim",
                "mb.cancelado = 'N'"
            ];
            $params = [
                ':data_inicio' => $data_inicio,
                ':data_fim'    => $data_fim,
            ];

            if (!empty($filter['area_id'])) {
                $where[] = 'mb.area_id = :area_id';
                $params[':area_id'] = (int) $filter['area_id'];
            }

            if (!empty($filter['extracao_id'])) {
                $where[] = 'ms.extracao_id = :extracao_id';
                $params[':extracao_id'] = (int) $filter['extracao_id'];
            }

            $sql = "
                SELECT
                    cade.descricao AS extracao,
                    cadv.nome AS vendedor,
                    SUM(mbs.total_sorteio) AS apurado,
                    SUM(mbs.comissao_sorteio) AS comissao,
                    SUM(mbs.total_sorteio) - SUM(mbs.comissao_sorteio) AS liquido,
                    SUM(mbs.sorteado_valor) AS premio,
                    SUM(mbs.total_sorteio) - SUM(mbs.comissao_sorteio) - SUM(mbs.sorteado_valor) AS total
                FROM mov_jb_sorteio mbs
                INNER JOIN mov_jb mb ON mb.jb_id = mbs.jb_id
                INNER JOIN mov_sorteio ms ON ms.sorteio_id = mbs.sorteio_id
                INNER JOIN cad_extracao cade ON cade.extracao_id = ms.extracao_id
                INNER JOIN cad_vendedor cadv ON cadv.vendedor_id = mb.vendedor_id
                INNER JOIN cad_area cada ON cada.area_id = mb.area_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY cade.descricao, cadv.nome
                ORDER BY cade.descricao, cadv.nome
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $tot_apurado  = 0;
            $tot_comissao = 0;
            $tot_liquido  = 0;
            $tot_premio   = 0;
            $tot_geral    = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                $tot_apurado  += (float) $row->apurado;
                $tot_comissao += (float) $row->comissao;
                $tot_liquido  += (float) $row->liquido;
                $tot_premio   += (float) $row->premio;
                $tot_geral    += (float) $row->total;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add(
                "Apurado: {$fmt($tot_apurado)} | " .
                "Comissão: {$fmt($tot_comissao)} | " .
                "Líquido: {$fmt($tot_liquido)} | " .
                "Prêmio: {$fmt($tot_premio)} | " .
                "Total Geral: {$fmt($tot_geral)}"
            );

            $this->loaded = true;

            if (empty($rows)) {
                new TMessage('info', 'Não existe resultado para este período!');
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
