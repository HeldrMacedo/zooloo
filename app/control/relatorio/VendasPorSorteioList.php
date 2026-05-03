<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class VendasPorSorteioList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_vendas_sorteio');
        $this->form->setFormTitle('Vendas por Sorteio');

        $btn = $this->form->addAction('Atualizar', new TAction([$this, 'onLoad']), 'fa:sync blue');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_numero   = new TDataGridColumn('sorteio_numero', 'Nº Sorteio', 'center', '10%');
        $col_extracao = new TDataGridColumn('descricao', 'Extração', 'left', '22%');
        $col_data     = new TDataGridColumn('data_sorteio', 'Data', 'center', '10%');
        $col_hora     = new TDataGridColumn('hora_sorteio', 'Hora', 'center', '8%');
        $col_situacao = new TDataGridColumn('situacao', 'Status', 'center', '10%');
        $col_total    = new TDataGridColumn('total_sorteio', 'Total Apostado', 'right', '14%');
        $col_comissao = new TDataGridColumn('comissao', 'Comissão', 'right', '13%');
        $col_liquido  = new TDataGridColumn('liquido', 'Líquido', 'right', '13%');

        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y', strtotime($v)) : '');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_total->setTransformer($fmt_brl);
        $col_comissao->setTransformer($fmt_brl);
        $col_liquido->setTransformer($fmt_brl);

        $col_numero->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));

        $col_situacao->setTransformer(function($v) {
            return $v === 'F'
                ? "<span class='label label-default'>Encerrado</span>"
                : "<span class='label label-success'>Aberto</span>";
        });

        foreach ([$col_numero,$col_extracao,$col_data,$col_hora,$col_situacao,$col_total,$col_comissao,$col_liquido] as $c) {
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

        $this->onLoad([]);
    }

    public function onLoad($param = [])
    {
        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sql = "
                SELECT
                    ms.sorteio_numero,
                    e.descricao,
                    ms.data_sorteio,
                    e.hora_limite AS hora_sorteio,
                    ms.situacao,
                    COALESCE(SUM(js.total_sorteio), 0) AS total_sorteio,
                    COALESCE(SUM(js.comissao_sorteio), 0) AS comissao,
                    COALESCE(SUM(js.total_sorteio) - SUM(js.comissao_sorteio), 0) AS liquido
                FROM mov_sorteio ms
                JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                LEFT JOIN mov_jb_sorteio js ON js.sorteio_id = ms.sorteio_id
                    AND EXISTS (SELECT 1 FROM mov_jb j WHERE j.jb_id = js.jb_id AND j.cancelado = 'N')
                WHERE ms.data_sorteio >= CURRENT_DATE - INTERVAL '7 days'
                GROUP BY ms.sorteio_id, ms.sorteio_numero, e.descricao, ms.data_sorteio, e.hora_limite, ms.situacao
                ORDER BY ms.data_sorteio DESC, e.hora_limite DESC
                LIMIT 50
            ";

            $stmt = $conn->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
            }
            $this->datagrid->updatePage();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
