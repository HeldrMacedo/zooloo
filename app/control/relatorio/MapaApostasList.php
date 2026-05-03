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
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class MapaApostasList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_mapa_apostas');
        $this->form->setFormTitle('Mapa de Apostas');

        $sorteio_id    = new TDBCombo('sorteio_id', 'permission', 'MovSorteio', 'sorteio_id', '{extracao_id} - {sorteio_numero}');
        $area_id       = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');
        $vendedor_id   = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');

        foreach ([$sorteio_id, $area_id, $modalidade_id, $vendedor_id] as $f) {
            $f->setSize('100%'); $f->setDefaultOption(true);
        }

        $this->form->addFields([new TLabel('Sorteio:')], [$sorteio_id], [new TLabel('Área:')], [$area_id]);
        $this->form->addFields([new TLabel('Modalidade:')], [$modalidade_id], [new TLabel('Vendedor:')], [$vendedor_id]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_palpite  = new TDataGridColumn('palpite', 'Palpite', 'center', '12%');
        $col_modal    = new TDataGridColumn('modalidade', 'Modalidade', 'left', '20%');
        $col_qtde     = new TDataGridColumn('qtde', 'Qtde Bilhetes', 'center', '12%');
        $col_total    = new TDataGridColumn('total', 'Total Apostado', 'right', '16%');
        $col_previsto = new TDataGridColumn('previsto', 'Prêmio Previsto', 'right', '18%');
        $col_pago     = new TDataGridColumn('pago', 'Prêmio Pago', 'right', '18%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        foreach ([$col_total,$col_previsto,$col_pago] as $c) {
            $c->setTransformer($fmt_brl);
        }

        foreach ([$col_palpite,$col_modal,$col_qtde,$col_total,$col_previsto,$col_pago] as $c) {
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

        if (empty($filter['sorteio_id'])) {
            new TMessage('info', 'Selecione um sorteio para visualizar o mapa de apostas.');
            return;
        }

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $jbWhere  = ["j.cancelado = 'N'"];
            $params   = [];

            $jbWhere[] = 'p.sorteio_id = :sorteio_id';
            $params[':sorteio_id'] = $filter['sorteio_id'];

            if (!empty($filter['area_id'])) {
                $jbWhere[] = 'j.area_id = :area_id';
                $params[':area_id'] = $filter['area_id'];
            }
            if (!empty($filter['modalidade_id'])) {
                $jbWhere[] = 'p.modalidade_id = :modalidade_id';
                $params[':modalidade_id'] = $filter['modalidade_id'];
            }
            if (!empty($filter['vendedor_id'])) {
                $jbWhere[] = 'j.vendedor_id = :vendedor_id';
                $params[':vendedor_id'] = $filter['vendedor_id'];
            }

            $cond = implode(' AND ', $jbWhere);

            $sql = "
                SELECT
                    p.palpite,
                    m.apresentacao AS modalidade,
                    COUNT(DISTINCT js.jb_sorteio_id) AS qtde,
                    SUM(p.valor_palpite) AS total,
                    SUM(p.premio_colocacao_01) AS previsto,
                    SUM(CASE WHEN js.sorteado_pago = 'S' THEN js.sorteado_valor_pago ELSE 0 END) AS pago
                FROM mov_jb_sort_palpite p
                JOIN mov_jb_sorteio js ON js.jb_sorteio_id = p.jb_sorteio_id
                JOIN mov_jb j ON j.jb_id = p.jb_id
                JOIN cad_modalidade m ON m.modalidade_id = p.modalidade_id
                WHERE {$cond}
                GROUP BY p.palpite, m.modalidade_id, m.apresentacao
                ORDER BY SUM(p.valor_palpite) DESC, p.palpite
                LIMIT 200
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
                new TMessage('info', 'Nenhuma aposta encontrada para este sorteio!');
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
