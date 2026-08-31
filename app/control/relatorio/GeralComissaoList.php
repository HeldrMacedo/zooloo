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

class GeralComissaoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;
    protected $mode;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_geral_comissao');
        $this->form->setFormTitle('Geral Comissão');

        $data_ini      = new TDate('data_ini');
        $data_fim      = new TDate('data_fim');
        $area_id       = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id   = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $vendedor_id   = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');

        $data_ini->setMask('dd/mm/yyyy');
        $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));

        $data_fim->setMask('dd/mm/yyyy');
        $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setValue(date('Y-m-d'));

        foreach ([$area_id, $extracao_id, $vendedor_id, $modalidade_id] as $f) {
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
        $this->form->addFields(
            [new TLabel('Vendedor:')], [$vendedor_id],
            [new TLabel('Modalidade:')], [$modalidade_id]
        );

        $this->form->addAction('Geral Vendedor', new TAction([$this, 'onSearchVendedor']), 'fa:user blue');
        $this->form->addAction('Geral Área', new TAction([$this, 'onSearchArea']), 'fa:map-marker-alt green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
        }

        // Determina o modo atual
        $method = $_GET['method'] ?? '';
        if ($method === 'onSearchArea') {
            $this->mode = 'area';
        } elseif ($method === 'onSearchVendedor') {
            $this->mode = 'vendedor';
        } else {
            $this->mode = TSession::getValue(__CLASS__.'_mode') ?? 'vendedor';
        }
        TSession::setValue(__CLASS__.'_mode', $this->mode);

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $label_agrupador = ($this->mode === 'area') ? 'Área' : 'Vendedor';
        $col_agrupador   = new TDataGridColumn('agrupador', $label_agrupador, 'left', '22%');
        $col_extracao    = new TDataGridColumn('extracao', 'Extração', 'left', '18%');
        $col_modal       = new TDataGridColumn('modalidade', 'Modalidade', 'left', '18%');
        $col_total       = new TDataGridColumn('total', 'Total', 'right', '14%');
        $col_comissao    = new TDataGridColumn('comissao', 'Comissão', 'right', '14%');
        $col_liquido     = new TDataGridColumn('liquido', 'Líquido', 'right', '14%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_total->setTransformer($fmt_brl);
        $col_comissao->setTransformer($fmt_brl);
        $col_liquido->setTransformer($fmt_brl);

        foreach ([$col_agrupador, $col_extracao, $col_modal, $col_total, $col_comissao, $col_liquido] as $c) {
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

    public function onSearchVendedor($param)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filter', (array) $data);
        TSession::setValue(__CLASS__.'_mode', 'vendedor');
        $this->form->setData($data);
        $this->onReload($param);
    }

    public function onSearchArea($param)
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filter', (array) $data);
        TSession::setValue(__CLASS__.'_mode', 'area');
        $this->form->setData($data);
        $this->onReload($param);
    }

    public function onClear($param)
    {
        TSession::setValue(__CLASS__.'_filter_data', null);
        TSession::setValue(__CLASS__.'_filter', null);
        TSession::setValue(__CLASS__.'_mode', 'vendedor');
        $this->form->clear();
        $this->datagrid->clear();
        $this->footerTotal->clearChildren();

        $data = (object) [
            'data_ini'      => date('Y-m-d'),
            'data_fim'      => date('Y-m-d'),
            'area_id'       => '',
            'extracao_id'   => '',
            'vendedor_id'   => '',
            'modalidade_id' => ''
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

        $this->mode = TSession::getValue(__CLASS__.'_mode') ?? 'vendedor';

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

            if (!empty($filter['vendedor_id'])) {
                $where[] = 'mb.vendedor_id = :vendedor_id';
                $params[':vendedor_id'] = (int) $filter['vendedor_id'];
            }

            if (!empty($filter['modalidade_id'])) {
                $where[] = 'p.modalidade_id = :modalidade_id';
                $params[':modalidade_id'] = (int) $filter['modalidade_id'];
            }

            $agrupador_campo = ($this->mode === 'area') ? 'a.descricao' : 'v.nome';

            $sql = "
                SELECT
                    {$agrupador_campo} AS agrupador,
                    e.descricao AS extracao,
                    m.apresentacao AS modalidade,
                    SUM(p.total_sorteio) AS total,
                    SUM(p.comissao_sorteio) AS comissao,
                    SUM(p.total_sorteio) - SUM(p.comissao_sorteio) AS liquido
                FROM mov_jb_sorteio p
                INNER JOIN mov_jb mb ON mb.jb_id = p.jb_id
                INNER JOIN cad_area a ON a.area_id = mb.area_id
                INNER JOIN cad_vendedor v ON v.vendedor_id = mb.vendedor_id
                INNER JOIN mov_sorteio ms ON ms.sorteio_id = p.sorteio_id
                INNER JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                INNER JOIN cad_modalidade m ON m.modalidade_id = p.modalidade_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY {$agrupador_campo}, e.descricao, m.apresentacao
                ORDER BY {$agrupador_campo}, e.descricao, m.apresentacao
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $tot_total    = 0;
            $tot_comissao = 0;
            $tot_liquido  = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                $tot_total    += (float) $row->total;
                $tot_comissao += (float) $row->comissao;
                $tot_liquido  += (float) $row->liquido;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add(
                "Total: {$fmt($tot_total)} | " .
                "Comissão: {$fmt($tot_comissao)} | " .
                "Líquido: {$fmt($tot_liquido)}"
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
        if (!$this->loaded && (!isset($_GET['method']) || !in_array($_GET['method'], ['onReload', 'onSearchVendedor', 'onSearchArea', 'onClear']))) {
            $this->onReload();
        }
        parent::show();
    }
}
