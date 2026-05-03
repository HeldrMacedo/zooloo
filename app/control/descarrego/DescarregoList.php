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
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class DescarregoList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_descarrego');
        $this->form->setFormTitle('Descarrego');

        $sorteio_id    = new TDBCombo('sorteio_id', 'permission', 'MovSorteio', 'sorteio_id', 'sorteio_numero');
        $area_id       = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id   = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');
        $agrupamento   = new TCombo('agrupamento');

        $agrupamento->addItems(['detalhado' => 'Detalhado', 'agrupado' => 'Agrupado']);
        $agrupamento->setValue('agrupado');
        $agrupamento->setSize('100%');

        foreach ([$sorteio_id, $area_id, $extracao_id, $modalidade_id] as $f) {
            $f->setSize('100%'); $f->setDefaultOption(true);
        }

        $this->form->addFields([new TLabel('Extração:')], [$extracao_id], [new TLabel('Sorteio:')], [$sorteio_id]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Modalidade:')], [$modalidade_id]);
        $this->form->addFields([new TLabel('Agrupamento:')], [$agrupamento]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_palpite  = new TDataGridColumn('palpite', 'Palpite', 'center', '12%');
        $col_modal    = new TDataGridColumn('modalidade', 'Modalidade', 'left', '18%');
        $col_vendedor = new TDataGridColumn('vendedor', 'Vendedor', 'left', '16%');
        $col_qtde     = new TDataGridColumn('qtde', 'Qtde', 'center', '8%');
        $col_apostado = new TDataGridColumn('total_apostado', 'Total Apostado', 'right', '15%');
        $col_limite   = new TDataGridColumn('limite_descarga', 'Limite', 'right', '15%');
        $col_excesso  = new TDataGridColumn('excesso', 'Excesso', 'right', '14%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        foreach ([$col_apostado,$col_limite,$col_excesso] as $c) {
            $c->setTransformer($fmt_brl);
        }
        $col_excesso->setTransformer(function($v) use ($fmt_brl) {
            $formatted = $fmt_brl($v);
            return (float)$v > 0
                ? "<span style='color:red;font-weight:bold'>{$formatted}</span>"
                : $formatted;
        });

        foreach ([$col_palpite,$col_modal,$col_vendedor,$col_qtde,$col_apostado,$col_limite,$col_excesso] as $c) {
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

        if (empty($filter['sorteio_id']) && empty($filter['extracao_id'])) {
            new TMessage('info', 'Selecione uma extração ou sorteio.');
            return;
        }

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $agrupado = ($filter['agrupamento'] ?? 'agrupado') === 'agrupado';

            $jbWhere  = ["j.cancelado = 'N'"];
            $params   = [];

            if (!empty($filter['sorteio_id'])) {
                $jbWhere[] = 'p.sorteio_id = :sorteio_id';
                $params[':sorteio_id'] = $filter['sorteio_id'];
            }
            if (!empty($filter['extracao_id'])) {
                $jbWhere[] = 'ms.extracao_id = :extracao_id';
                $params[':extracao_id'] = $filter['extracao_id'];
            }
            if (!empty($filter['area_id'])) {
                $jbWhere[] = 'j.area_id = :area_id';
                $params[':area_id'] = $filter['area_id'];
            }
            if (!empty($filter['modalidade_id'])) {
                $jbWhere[] = 'p.modalidade_id = :modalidade_id';
                $params[':modalidade_id'] = $filter['modalidade_id'];
            }

            $cond = implode(' AND ', $jbWhere);

            if ($agrupado) {
                $sql = "
                    SELECT
                        p.palpite,
                        m.apresentacao AS modalidade,
                        '' AS vendedor,
                        COUNT(DISTINCT js.jb_sorteio_id) AS qtde,
                        SUM(p.valor_palpite) AS total_apostado,
                        COALESCE(d.limite_descarga, 0) AS limite_descarga,
                        GREATEST(SUM(p.valor_palpite) - COALESCE(d.limite_descarga, 0), 0) AS excesso
                    FROM mov_jb_sort_palpite p
                    JOIN mov_jb_sorteio js ON js.jb_sorteio_id = p.jb_sorteio_id
                    JOIN mov_jb j ON j.jb_id = p.jb_id
                    JOIN mov_sorteio ms ON ms.sorteio_id = p.sorteio_id
                    JOIN cad_modalidade m ON m.modalidade_id = p.modalidade_id
                    LEFT JOIN cfg_extracao_descarga d ON d.extracao_id = ms.extracao_id AND d.modalidade_id = p.modalidade_id
                    WHERE {$cond}
                    GROUP BY p.palpite, m.modalidade_id, m.apresentacao, d.limite_descarga
                    HAVING SUM(p.valor_palpite) > COALESCE(d.limite_descarga, 0)
                    ORDER BY excesso DESC, p.palpite
                    LIMIT 300
                ";
            } else {
                $sql = "
                    SELECT
                        p.palpite,
                        m.apresentacao AS modalidade,
                        v.nome AS vendedor,
                        1 AS qtde,
                        p.valor_palpite AS total_apostado,
                        COALESCE(d.limite_descarga, 0) AS limite_descarga,
                        GREATEST(p.valor_palpite - COALESCE(d.limite_descarga, 0), 0) AS excesso
                    FROM mov_jb_sort_palpite p
                    JOIN mov_jb_sorteio js ON js.jb_sorteio_id = p.jb_sorteio_id
                    JOIN mov_jb j ON j.jb_id = p.jb_id
                    JOIN mov_sorteio ms ON ms.sorteio_id = p.sorteio_id
                    JOIN cad_modalidade m ON m.modalidade_id = p.modalidade_id
                    JOIN cad_vendedor v ON v.vendedor_id = j.vendedor_id
                    LEFT JOIN cfg_extracao_descarga d ON d.extracao_id = ms.extracao_id AND d.modalidade_id = p.modalidade_id
                    WHERE {$cond}
                    AND p.valor_palpite > COALESCE(d.limite_descarga, 0)
                    ORDER BY p.palpite, m.apresentacao, v.nome
                    LIMIT 500
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
                new TMessage('info', 'Nenhum palpite acima do limite encontrado!');
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
