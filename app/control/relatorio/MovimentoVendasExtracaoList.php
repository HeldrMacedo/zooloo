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

class MovimentoVendasExtracaoList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_movimento_vendas_extracao');
        $this->form->setFormTitle('Movimento Vendas por Extração');

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

        $col_extracao = new TDataGridColumn('extracao', 'Extração', 'left', '24%');
        $col_data     = new TDataGridColumn('data_sorteio', 'Data', 'center', '10%');
        $col_qtde     = new TDataGridColumn('qtde', 'Qtde', 'center', '8%');
        $col_apurado  = new TDataGridColumn('apurado', 'Apurado', 'right', '14%');
        $col_comissao = new TDataGridColumn('comissao', 'Comissão', 'right', '14%');
        $col_liquido  = new TDataGridColumn('liquido', 'Líquido', 'right', '14%');
        $col_premio   = new TDataGridColumn('premio', 'Prêmio', 'right', '14%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        foreach ([$col_apurado,$col_comissao,$col_liquido,$col_premio] as $c) {
            $c->setTransformer($fmt_brl);
        }
        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y', strtotime($v)) : '');

        foreach ([$col_extracao,$col_data,$col_qtde,$col_apurado,$col_comissao,$col_liquido,$col_premio] as $c) {
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

            $sql = "
                SELECT
                    e.descricao AS extracao,
                    p.data_sorteio,
                    COUNT(DISTINCT p.jb_sorteio_id) AS qtde,
                    SUM(p.total_sorteio) AS apurado,
                    SUM(p.comissao_sorteio) AS comissao,
                    SUM(p.total_sorteio - p.comissao_sorteio) AS liquido,
                    SUM(p.sorteado_valor) AS premio
                FROM vw_vendajb p
                JOIN cad_extracao e ON e.extracao_id = p.extracao_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY e.extracao_id, e.descricao, p.data_sorteio
                ORDER BY p.data_sorteio DESC, e.descricao
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
