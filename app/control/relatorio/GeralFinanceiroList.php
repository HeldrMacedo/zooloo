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
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class GeralFinanceiroList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_geral_financeiro');
        $this->form->setFormTitle('Movimento Geral Financeiro');

        $data_ini = new TDate('data_ini');
        $data_fim = new TDate('data_fim');
        $tipo     = new TCombo('tipo');
        $area_id  = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');

        $data_ini->setMask('dd/mm/yyyy');
        $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));

        $data_fim->setMask('dd/mm/yyyy');
        $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setValue(date('Y-m-d'));

        $tipo->addItems([
            0 => 'TODOS',
            1 => 'JOGOS',
            2 => 'QUININHA',
            3 => 'SENINHA',
            4 => 'BILHETINHO'
        ]);
        $tipo->setValue(0);
        $tipo->setDefaultOption(false);
        $tipo->setSize('100%');

        $area_id->setSize('100%');
        $area_id->setDefaultOption(true);

        $this->form->addFields(
            [new TLabel('Data Inicial:')], [$data_ini],
            [new TLabel('Data Final:')], [$data_fim]
        );
        $this->form->addFields(
            [new TLabel('Tipo:')], [$tipo],
            [new TLabel('Área:')], [$area_id]
        );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
        }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_area        = new TDataGridColumn('area', 'Área', 'left', '14%');
        $col_vendedor    = new TDataGridColumn('vendedor', 'Vendedor', 'left', '16%');
        $col_apurado     = new TDataGridColumn('apurado', 'Apurado', 'right', '10%');
        $col_comissao    = new TDataGridColumn('comissao', 'Comissão', 'right', '10%');
        $col_total       = new TDataGridColumn('total', 'Total', 'right', '10%');
        $col_premio      = new TDataGridColumn('premio', 'Valor Prêmio', 'right', '10%');
        $col_total_geral = new TDataGridColumn('total_geral', 'Total Geral', 'right', '10%');
        $col_pago        = new TDataGridColumn('premio_pago', 'Prêmio Pago', 'right', '10%');
        $col_diferenca   = new TDataGridColumn('diferenca', 'Diferença', 'right', '10%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        foreach ([$col_apurado, $col_comissao, $col_total, $col_premio, $col_total_geral, $col_pago, $col_diferenca] as $c) {
            $c->setTransformer($fmt_brl);
        }

        foreach ([$col_area, $col_vendedor, $col_apurado, $col_comissao, $col_total, $col_premio, $col_total_geral, $col_pago, $col_diferenca] as $c) {
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
            'data_ini' => date('Y-m-d'),
            'data_fim' => date('Y-m-d'),
            'tipo'     => 0,
            'area_id'  => ''
        ];
        $this->form->setData($data);
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) {
            $filter = [
                'data_ini' => date('Y-m-d'),
                'data_fim' => date('Y-m-d'),
                'tipo'     => 0
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
                "mov_jb.data_hora >= :data_inicio",
                "mov_jb.data_hora < :data_fim",
                "mov_jb.cancelado = 'N'"
            ];
            $params = [
                ':data_inicio' => $data_inicio,
                ':data_fim'    => $data_fim,
            ];

            if (!empty($filter['tipo']) && (int)$filter['tipo'] > 0) {
                $where[] = "int_jogo.filtro_banca = :filtro";
                $params[':filtro'] = (int) $filter['tipo'];
            }

            if (!empty($filter['area_id'])) {
                $where[] = 'mov_jb.area_id = :area_id';
                $params[':area_id'] = (int) $filter['area_id'];
            }

            $sql = "
                SELECT
                    cad_area.descricao AS area,
                    cad_vendedor.nome AS vendedor,
                    SUM(mov_jb_sorteio.total_sorteio) AS apurado,
                    SUM(mov_jb_sorteio.comissao_sorteio) AS comissao,
                    SUM(mov_jb_sorteio.total_sorteio - mov_jb_sorteio.comissao_sorteio) AS total,
                    SUM(mov_jb_sorteio.sorteado_valor) AS premio,
                    (SUM(mov_jb_sorteio.total_sorteio - mov_jb_sorteio.comissao_sorteio) - SUM(mov_jb_sorteio.sorteado_valor)) AS total_geral,
                    SUM(mov_jb_sorteio.sorteado_valor_pago) AS premio_pago,
                    (SUM(mov_jb_sorteio.sorteado_valor) - SUM(mov_jb_sorteio.sorteado_valor_pago)) AS diferenca
                FROM mov_jb
                JOIN cad_area ON mov_jb.area_id = cad_area.area_id
                JOIN cad_vendedor ON mov_jb.vendedor_id = cad_vendedor.vendedor_id
                JOIN mov_jb_sorteio ON mov_jb.jb_id = mov_jb_sorteio.jb_id
                JOIN cad_modalidade ON cad_modalidade.modalidade_id = mov_jb_sorteio.modalidade_id
                JOIN int_jogo ON int_jogo.jogo_id = cad_modalidade.jogo_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY cad_area.descricao, cad_vendedor.nome
                ORDER BY cad_vendedor.nome
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $tot_apurado     = 0;
            $tot_comissao    = 0;
            $tot_total       = 0;
            $tot_premio      = 0;
            $tot_total_geral = 0;
            $tot_pago        = 0;
            $tot_diferenca   = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                $tot_apurado     += (float) $row->apurado;
                $tot_comissao    += (float) $row->comissao;
                $tot_total       += (float) $row->total;
                $tot_premio      += (float) $row->premio;
                $tot_total_geral += (float) $row->total_geral;
                $tot_pago        += (float) $row->premio_pago;
                $tot_diferenca   += (float) $row->diferenca;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add(
                "Apurado: {$fmt($tot_apurado)} | " .
                "Comissão: {$fmt($tot_comissao)} | " .
                "Total: {$fmt($tot_total)} | " .
                "Prêmio: {$fmt($tot_premio)} | " .
                "Total Geral: {$fmt($tot_total_geral)} | " .
                "Prêmio Pago: {$fmt($tot_pago)} | " .
                "Diferença: {$fmt($tot_diferenca)}"
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
