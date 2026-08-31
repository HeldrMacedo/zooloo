<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
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

class MapaApostasList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_mapa_apostas');
        $this->form->setFormTitle('Mapa de Apostas');

        $data_ini    = new TDate('data_ini');
        $data_fim    = new TDate('data_fim');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $ordem       = new TCombo('ordem');

        $criteria_mod = new TCriteria;
        $criteria_mod->add(new TFilter('jogo_id', 'IN', "(SELECT jogo_id FROM int_jogo WHERE TRIM(abreviacao) IN ('M', 'C', 'D', 'G'))"));
        $criteria_mod->setProperty('order', 'apresentacao');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao', 'apresentacao', $criteria_mod);

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));
        $data_fim->setValue(date('Y-m-d'));

        $ordem->addItems([
            '0' => 'Palpite',
            '1' => 'Quantidade de jogos',
            '2' => 'Valor total jogos'
        ]);
        $ordem->setDefaultOption(false);
        $ordem->setValue('0');

        foreach ([$area_id, $extracao_id, $modalidade_id] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }
        $ordem->setSize('100%');

        $this->form->addFields(
            [new TLabel('Data Inicial:')], [$data_ini],
            [new TLabel('Data Final:')], [$data_fim],
            [new TLabel('Área:')], [$area_id]
        );

        $this->form->addFields(
            [new TLabel('Extração:*')], [$extracao_id],
            [new TLabel('Ordem:')], [$ordem],
            [new TLabel('Modalidade:*')], [$modalidade_id]
        );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
        }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_bicho   = new TDataGridColumn('bicho', 'Bicho', 'left', '35%');
        $col_palpite = new TDataGridColumn('palpite', 'Palpite', 'center', '20%');
        $col_jogos   = new TDataGridColumn('jogos', 'Jogos', 'center', '20%');
        $col_total   = new TDataGridColumn('total', 'Total', 'right', '25%');

        $col_bicho->setTransformer(fn($v) => $v ?: '<em class="text-muted">—</em>');
        $col_total->setTransformer(fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.'));

        foreach ([$col_bicho, $col_palpite, $col_jogos, $col_total] as $c) {
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
            'data_ini'      => date('Y-m-d'),
            'data_fim'      => date('Y-m-d'),
            'area_id'       => '',
            'extracao_id'   => '',
            'modalidade_id' => '',
            'ordem'         => '0'
        ];
        $this->form->setData($data);
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) return;

        if (empty($filter['extracao_id']) || empty($filter['modalidade_id'])) {
            new TMessage('warning', 'Escolha uma Extração e uma Modalidade!');
            return;
        }

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $data_ini    = !empty($filter['data_ini']) ? $filter['data_ini'] : date('Y-m-d');
            $data_fim    = !empty($filter['data_fim']) ? $filter['data_fim'] : date('Y-m-d');
            $data_inicio = $data_ini . ' 00:00:00';
            $data_final  = date('Y-m-d 00:00:00', strtotime($data_fim . ' +1 day'));

            $where_sub = [
                "TRIM(ijog.abreviacao) IN ('M', 'C', 'D', 'G')",
                "j.data_hora >= :data_inicio AND j.data_hora < :data_final",
                "e.extracao_id = :extracao_id",
                "p.modalidade_id = :modalidade_id"
            ];

            $params = [
                ':data_inicio'   => $data_inicio,
                ':data_final'    => $data_final,
                ':extracao_id'   => (int) $filter['extracao_id'],
                ':modalidade_id' => (int) $filter['modalidade_id']
            ];

            if (!empty($filter['area_id'])) {
                $where_sub[] = "j.area_id = :area_id";
                $params[':area_id'] = (int) $filter['area_id'];
            }

            $ordem = (int) ($filter['ordem'] ?? 0);
            $orderBy = "w.palpite DESC";
            if ($ordem === 1) {
                $orderBy = "w.jogos DESC";
            } else if ($ordem === 2) {
                $orderBy = "w.total DESC";
            }

            $sql = "
                SELECT
                    (SELECT g.descricao FROM int_grupo g WHERE
                        (TRIM(w.abreviacao) = 'M' AND g.final_grupo_id = CAST(TRIM(SUBSTRING(w.palpite, 3, 2)) AS integer)) OR
                        (TRIM(w.abreviacao) = 'C' AND g.final_grupo_id = CAST(TRIM(SUBSTRING(w.palpite, 2, 2)) AS integer)) OR
                        (TRIM(w.abreviacao) = 'D' AND g.final_grupo_id = CAST(TRIM(w.palpite) AS integer)) OR
                        (TRIM(w.abreviacao) = 'G' AND TRIM(g.grupo) = TRIM(w.palpite))
                     LIMIT 1) AS bicho,
                    w.palpite,
                    w.jogos,
                    w.total
                FROM (
                    SELECT
                        ijog.abreviacao,
                        p.palpite,
                        COUNT(p.jb_palpites_id) AS jogos,
                        SUM(p.valor_palpite) AS total
                    FROM mov_jb_sort_palpite p
                    JOIN mov_jb_sorteio s ON (s.jb_id = p.jb_id AND s.jb_sorteio_id = p.jb_sorteio_id)
                    JOIN mov_jb j ON (j.jb_id = p.jb_id AND j.cancelado = 'N')
                    JOIN mov_sorteio m ON (m.sorteio_id = s.sorteio_id)
                    JOIN cad_extracao e ON (e.extracao_id = m.extracao_id)
                    JOIN cad_modalidade c ON (c.modalidade_id = p.modalidade_id)
                    JOIN int_jogo ijog ON ijog.jogo_id = c.jogo_id
                    WHERE " . implode(' AND ', $where_sub) . "
                    GROUP BY ijog.abreviacao, p.palpite
                ) w
                ORDER BY {$orderBy}
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $total_geral = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                $total_geral += (float)$row->total;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add("Total Apostas: {$fmt($total_geral)}");

            $this->loaded = true;

            if (empty($rows)) {
                new TMessage('info', 'Não existe resultado para esta data!');
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

