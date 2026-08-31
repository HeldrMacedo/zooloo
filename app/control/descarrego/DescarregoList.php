<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class DescarregoList extends TPage
{
    const MODO_PENDENTE_DETALHADA     = 'pendente_detalhada';
    const MODO_PENDENTE_AGRUPADA      = 'pendente_agrupada';
    const MODO_DESCARREGADA_DETALHADA = 'descarregada_detalhada';
    const MODO_DESCARREGADA_AGRUPADA  = 'descarregada_agrupada';

    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;
    protected $modo;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_descarrego');
        $this->form->setFormTitle('Descarrego');

        $data          = new TDate('data');
        $area_id       = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id   = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $tipo_consulta = new TCombo('tipo_consulta');

        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        $data->setValue(date('Y-m-d'));

        foreach ([$area_id, $extracao_id] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }

        $tipo_consulta->addItems([
            self::MODO_PENDENTE_DETALHADA     => 'Pendentes [Detalhada]',
            self::MODO_PENDENTE_AGRUPADA      => 'Pendentes [Agrupada]',
            self::MODO_DESCARREGADA_DETALHADA => 'Descarregadas [Detalhada]',
            self::MODO_DESCARREGADA_AGRUPADA  => 'Descarregadas [Agrupada]'
        ]);
        $tipo_consulta->setDefaultOption(false);
        $tipo_consulta->setValue(self::MODO_PENDENTE_DETALHADA);
        $tipo_consulta->setSize('100%');

        $this->form->addFields(
            [new TLabel('Data:')], [$data],
            [new TLabel('Área:')], [$area_id]
        );
        $this->form->addFields(
            [new TLabel('Extração:*')], [$extracao_id],
            [new TLabel('Tipo de Consulta:')], [$tipo_consulta]
        );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
            $this->modo = $filter_data->tipo_consulta ?? self::MODO_PENDENTE_DETALHADA;
        } else {
            $this->modo = self::MODO_PENDENTE_DETALHADA;
        }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $this->buildDatagridColumns();

        $this->datagrid->createModel();

        $this->footerTotal = new TElement('div');
        $this->footerTotal->style = 'text-align:right;padding:8px;font-weight:bold;';

        $this->panel = new TPanelGroup();
        $this->panel->add($this->datagrid)->style = 'overflow-x:auto';
        $this->panel->addFooter($this->footerTotal);

        if (in_array($this->modo, [self::MODO_PENDENTE_DETALHADA, self::MODO_PENDENTE_AGRUPADA])) {
            $btn_proc_todas = $this->panel->addHeaderActionLink('Processar Todas', new TAction([$this, 'onConfirmProcessarTodas']), 'fa:cogs');
            $btn_proc_todas->class = 'btn btn-sm btn-warning';
        }

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($this->panel);
        parent::add($container);
    }

    private function buildDatagridColumns()
    {
        $is_descarregada = in_array($this->modo, [self::MODO_DESCARREGADA_DETALHADA, self::MODO_DESCARREGADA_AGRUPADA]);

        $col_idx      = new TDataGridColumn('idx', '#', 'center', '5%');
        $col_apostado = new TDataGridColumn('apostado', 'Apostado', 'right', '18%');
        $col_jogou    = new TDataGridColumn('jogou', 'Jogo', 'left', '40%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_apostado->setTransformer($fmt_brl);

        if ($is_descarregada) {
            $col_data    = new TDataGridColumn('data_hora', 'Data/Hora', 'center', '17%');
            $col_usuario = new TDataGridColumn('nome', 'Usuário', 'left', '20%');
            $col_data->setTransformer(fn($v) => $v ? date('d/m/Y H:i:s', strtotime($v)) : '');

            foreach ([$col_idx, $col_data, $col_usuario, $col_apostado, $col_jogou] as $c) {
                $this->datagrid->addColumn($c);
            }
        } else {
            $col_data = new TDataGridColumn('data_sorteio', 'Data', 'center', '12%');
            $col_data->setTransformer(fn($v) => $v ? date('d/m/Y', strtotime($v)) : '');

            foreach ([$col_idx, $col_data, $col_apostado, $col_jogou] as $c) {
                $this->datagrid->addColumn($c);
            }

            $action_proc = new TDataGridAction([$this, 'onConfirmProcessarItem']);
            $action_proc->setButtonClass('btn btn-info btn-sm');
            $action_proc->setLabel('Processar');
            $action_proc->setImage('fa:check');
            $action_proc->setField('palpite');
            $action_proc->setField('mod_abreviacao');
            $action_proc->setField('colocacao');
            $action_proc->setField('data_sorteio');
            $action_proc->setField('extracao');
            $action_proc->setField('jogou');
            $this->datagrid->addAction($action_proc);
        }
    }

    public function onSearch($param)
    {
        $data = $this->form->getData();
        $this->modo = $data->tipo_consulta ?? self::MODO_PENDENTE_DETALHADA;
        TSession::setValue(__CLASS__.'_modo', $this->modo);
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filter', (array) $data);
        $this->form->setData($data);
        $this->onReload();
    }

    public function onClear($param)
    {
        TSession::setValue(__CLASS__.'_filter_data', null);
        TSession::setValue(__CLASS__.'_filter', null);
        TSession::setValue(__CLASS__.'_modo', self::MODO_PENDENTE_DETALHADA);
        $this->form->clear();
        $this->datagrid->clear();
        $this->footerTotal->clearChildren();

        $data = (object) [
            'data'          => date('Y-m-d'),
            'area_id'       => '',
            'extracao_id'   => '',
            'tipo_consulta' => self::MODO_PENDENTE_DETALHADA
        ];
        $this->form->setData($data);
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) {
            $filter = ['data' => date('Y-m-d')];
        }

        if (empty($filter['extracao_id'])) {
            new TMessage('info', 'Selecione uma extração!');
            return;
        }

        $this->modo = $filter['tipo_consulta'] ?? (TSession::getValue(__CLASS__.'_modo') ?? self::MODO_PENDENTE_DETALHADA);

        $consultar_processados = in_array($this->modo, [self::MODO_DESCARREGADA_DETALHADA, self::MODO_DESCARREGADA_AGRUPADA]);
        $consulta_agrupados    = in_array($this->modo, [self::MODO_PENDENTE_AGRUPADA, self::MODO_DESCARREGADA_AGRUPADA]);

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $data_sorteio = !empty($filter['data']) ? $filter['data'] : date('Y-m-d');
            $extracao_id  = (int) $filter['extracao_id'];
            $area_id      = !empty($filter['area_id']) ? (int) $filter['area_id'] : null;

            $sql = self::getDescarregoQuery($consultar_processados, $consulta_agrupados, !empty($area_id));

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':dataSorteio', $data_sorteio);
            $stmt->bindValue(':extracao', $extracao_id, \PDO::PARAM_INT);
            if (!empty($area_id)) {
                $stmt->bindValue(':area', $area_id, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $total = 0;
            $idx   = 1;

            foreach ($rows as $row) {
                $row->idx = $idx++;
                $this->datagrid->addItem($row);
                $total += (float)$row->apostado;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add("Total: {$fmt($total)}");

            $this->loaded = true;

            if (empty($rows)) {
                new TMessage('info', 'Não existe resultado para esta consulta!');
            }

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onConfirmProcessarItem($param)
    {
        $jogou = $param['jogou'] ?? '';
        $action = new TAction([$this, 'onProcessarItem']);
        foreach (['palpite', 'mod_abreviacao', 'colocacao', 'data_sorteio', 'extracao', 'jogou'] as $f) {
            if (isset($param[$f])) {
                $action->setParameter($f, $param[$f]);
            }
        }

        new TQuestion("Deseja processar este descarrego ({$jogou})?", $action);
    }

    public function onProcessarItem($param)
    {
        try {
            $data_sorteio = $param['data_sorteio'] ?? '';
            $extracao_id  = (int) ($param['extracao'] ?? 0);
            $abreviacao   = $param['mod_abreviacao'] ?? '';
            $palpite      = $param['palpite'] ?? '';
            $colocacao    = $param['colocacao'] ?? '';
            $filter       = TSession::getValue(__CLASS__.'_filter');
            $area_id      = !empty($filter['area_id']) ? (int) $filter['area_id'] : null;

            $id_usuario   = (int) (TSession::getValue('userid') ?? 1);
            $nome_usuario = TSession::getValue('name') ?? TSession::getValue('userlogin') ?? 'Admin';

            if (empty($data_sorteio) || empty($extracao_id) || empty($abreviacao) || empty($palpite)) {
                throw new Exception('Parâmetros inválidos para processamento.');
            }

            TTransaction::open('permission');
            $conn = TTransaction::get();

            if ($colocacao === 'jogou_colocacao_PP') {
                // Modo Agrupado: processa todas as colocações ativas para este palpite
                $sqlBusca = self::getDescarregoQuery(false, false, !empty($area_id), true, true);
                $stmtBusca = $conn->prepare($sqlBusca);
                $stmtBusca->bindValue(':dataSorteio', $data_sorteio);
                $stmtBusca->bindValue(':extracao', $extracao_id, \PDO::PARAM_INT);
                $stmtBusca->bindValue(':palpite', $palpite);
                $stmtBusca->bindValue(':modabreviacao', $abreviacao);
                if (!empty($area_id)) {
                    $stmtBusca->bindValue(':area', $area_id, \PDO::PARAM_INT);
                }
                $stmtBusca->execute();
                $pendentes = $stmtBusca->fetchAll(\PDO::FETCH_OBJ);

                foreach ($pendentes as $p) {
                    self::executarProcessamento($conn, $data_sorteio, $extracao_id, $p->mod_abreviacao, $p->palpite, $p->colocacao, $id_usuario, $nome_usuario, $area_id);
                }
            } else {
                // Modo Detalhado: processa colocação específica
                self::executarProcessamento($conn, $data_sorteio, $extracao_id, $abreviacao, $palpite, $colocacao, $id_usuario, $nome_usuario, $area_id);
            }

            TTransaction::close();

            new TMessage('info', 'Processado com sucesso!');
            $this->onReload();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onConfirmProcessarTodas($param)
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter['extracao_id'])) {
            new TMessage('warning', 'Selecione uma extração antes de processar!');
            return;
        }

        $action = new TAction([$this, 'onProcessarTodas']);
        new TQuestion('Deseja processar todas as apostas excedentes desta extração?', $action);
    }

    public function onProcessarTodas($param)
    {
        try {
            $filter       = TSession::getValue(__CLASS__.'_filter');
            $data_sorteio = !empty($filter['data']) ? $filter['data'] : date('Y-m-d');
            $extracao_id  = (int) ($filter['extracao_id'] ?? 0);
            $area_id      = !empty($filter['area_id']) ? (int) $filter['area_id'] : null;

            $id_usuario   = (int) (TSession::getValue('userid') ?? 1);
            $nome_usuario = TSession::getValue('name') ?? TSession::getValue('userlogin') ?? 'Admin';

            if (empty($data_sorteio) || empty($extracao_id)) {
                throw new Exception('Selecione data e extração.');
            }

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sqlBusca = self::getDescarregoQuery(false, false, !empty($area_id));
            $stmtBusca = $conn->prepare($sqlBusca);
            $stmtBusca->bindValue(':dataSorteio', $data_sorteio);
            $stmtBusca->bindValue(':extracao', $extracao_id, \PDO::PARAM_INT);
            if (!empty($area_id)) {
                $stmtBusca->bindValue(':area', $area_id, \PDO::PARAM_INT);
            }
            $stmtBusca->execute();
            $pendentes = $stmtBusca->fetchAll(\PDO::FETCH_OBJ);

            $count = 0;
            foreach ($pendentes as $p) {
                $count += self::executarProcessamento($conn, $data_sorteio, $extracao_id, $p->mod_abreviacao, $p->palpite, $p->colocacao, $id_usuario, $nome_usuario, $area_id);
            }

            TTransaction::close();

            new TMessage('info', "Descarrego processado com sucesso! Total de registros: {$count}");
            $this->onReload();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function executarProcessamento($conn, $data_sorteio, $extracao_id, $abreviacao, $palpite, $jogou_colocacao, $id_usuario, $nome_usuario, $area_id = null)
    {
        $col_map = [
            'jogou_colocacao_01' => 'processado_colocacao_01',
            'jogou_colocacao_02' => 'processado_colocacao_02',
            'jogou_colocacao_03' => 'processado_colocacao_03',
            'jogou_colocacao_04' => 'processado_colocacao_04',
            'jogou_colocacao_05' => 'processado_colocacao_05',
            'jogou_colocacao_06' => 'processado_colocacao_06',
            'jogou_colocacao_07' => 'processado_colocacao_07',
            'jogou_colocacao_08' => 'processado_colocacao_08',
            'jogou_colocacao_09' => 'processado_colocacao_09',
            'jogou_colocacao_10' => 'processado_colocacao_10',
        ];

        $processado = $col_map[$jogou_colocacao] ?? null;
        if (!$processado) return 0;

        $sql = "
            UPDATE mov_jb_sort_palpite mjsp
            SET {$processado} = 'S'
            FROM cad_modalidade cm, int_jogo ij, mov_sorteio ms, mov_jb mj
            WHERE cm.modalidade_id = mjsp.modalidade_id
              AND ij.jogo_id = cm.jogo_id
              AND ms.sorteio_id = mjsp.sorteio_id
              AND mj.jb_id = mjsp.jb_id
              AND ms.data_sorteio = :data_sorteio
              AND ms.extracao_id = :extracao_id
              AND ij.abreviacao = :abrev_modalidade
              AND mjsp.palpite = :palpite
        ";
        if (!empty($area_id)) {
            $sql .= " AND mj.area_id = :area_id";
        }

        $params = [
            ':data_sorteio'      => $data_sorteio,
            ':extracao_id'       => (int) $extracao_id,
            ':abrev_modalidade'  => $abreviacao,
            ':palpite'           => $palpite,
        ];
        if (!empty($area_id)) {
            $params[':area_id'] = (int) $area_id;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();

        if ($count > 0) {
            $now = date('Y-m-d H:i:s');
            $sqlInsert = "
                INSERT INTO data_jb_sort_palpite (jb_palpites_id, data_hora, id_usuario, nome)
                SELECT mjsp.jb_palpites_id, :data_hora, :id_usuario, :nome_usuario
                FROM mov_jb_sort_palpite mjsp
                JOIN cad_modalidade cm ON cm.modalidade_id = mjsp.modalidade_id
                JOIN int_jogo ij ON ij.jogo_id = cm.jogo_id
                JOIN mov_sorteio ms ON ms.sorteio_id = mjsp.sorteio_id
                JOIN mov_jb mj ON mj.jb_id = mjsp.jb_id
                WHERE ms.data_sorteio = :data_sorteio
                  AND ms.extracao_id = :extracao_id
                  AND ij.abreviacao = :abrev_modalidade
                  AND mjsp.palpite = :palpite
                  AND mjsp.{$processado} = 'S'
            ";
            if (!empty($area_id)) {
                $sqlInsert .= " AND mj.area_id = :area_id";
            }

            $paramsInsert = [
                ':data_hora'        => $now,
                ':id_usuario'       => $id_usuario,
                ':nome_usuario'     => $nome_usuario,
                ':data_sorteio'     => $data_sorteio,
                ':extracao_id'      => (int) $extracao_id,
                ':abrev_modalidade' => $abreviacao,
                ':palpite'          => $palpite,
            ];
            if (!empty($area_id)) {
                $paramsInsert[':area_id'] = (int) $area_id;
            }

            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->execute($paramsInsert);
        }

        return $count;
    }

    public static function getDescarregoQuery($consultar_processados, $consulta_agrupados, $has_area = false, $has_palpite = false, $has_modabreviacao = false)
    {
        $proc_val = $consultar_processados ? "'S'" : "'N'";

        $union_queries = [];
        for ($i = 1; $i <= 10; $i++) {
            $pad = str_pad($i, 2, '0', STR_PAD_LEFT);
            $suffix = $consulta_agrupados ? " PP" : " {$i}P";
            $col_name = $consulta_agrupados ? "jogou_colocacao_PP" : "jogou_colocacao_{$pad}";

            $union_queries[] = "
                SELECT
                    x.data_sorteio,
                    TRIM(x.mod_abreviacao) || ' ' || x.palpite || '{$suffix}' AS jogou,
                    x.limite_descarga,
                    SUM(x.valor_palpite) - x.limite_descarga AS soma_apostado,
                    x.mod_abreviacao,
                    x.palpite,
                    '{$col_name}' AS colocacao,
                    x.extracao,
                    x.situacao,
                    MAX(x.nome) AS nome,
                    MAX(x.data_hora) AS data_hora
                FROM CONSULTA_PALPITES x
                WHERE x.jogou_colocacao_{$pad} = 'S'
                  AND x.processado_colocacao_{$pad} = {$proc_val}
                  AND x.limite_descarga <> 0
                GROUP BY x.data_sorteio, jogou, x.limite_descarga, x.mod_abreviacao, x.palpite, x.extracao, x.situacao
                HAVING SUM(x.valor_palpite) > x.limite_descarga
            ";
        }

        $where_area = $has_area ? " AND mj.area_id = :area " : "";
        $where_palp = $has_palpite ? " AND mjsp.palpite = :palpite " : "";
        $where_mod  = $has_modabreviacao ? " AND ij.abreviacao = :modabreviacao " : "";

        $sql = "
            WITH CONSULTA_PALPITES AS (
                SELECT DISTINCT ON (mjsp.jb_palpites_id)
                    ms.data_sorteio,
                    ms.extracao_id AS extracao,
                    ij.abreviacao AS mod_abreviacao,
                    mjsp.jb_palpites_id,
                    mjsp.palpite,
                    COALESCE(cfgdes.limite_descarga, cm.limite_descarga, 0) AS limite_descarga,
                    mjsp.valor_palpite,
                    djsp.nome,
                    djsp.data_hora,
                    mjsp.jogou_colocacao_01, mjsp.jogou_colocacao_02, mjsp.jogou_colocacao_03, mjsp.jogou_colocacao_04, mjsp.jogou_colocacao_05,
                    mjsp.jogou_colocacao_06, mjsp.jogou_colocacao_07, mjsp.jogou_colocacao_08, mjsp.jogou_colocacao_09, mjsp.jogou_colocacao_10,
                    mjsp.processado_colocacao_01, mjsp.processado_colocacao_02, mjsp.processado_colocacao_03, mjsp.processado_colocacao_04,
                    mjsp.processado_colocacao_05, mjsp.processado_colocacao_06, mjsp.processado_colocacao_07, mjsp.processado_colocacao_08,
                    mjsp.processado_colocacao_09, mjsp.processado_colocacao_10,
                    ms.situacao
                FROM mov_jb_sort_palpite mjsp
                INNER JOIN cad_modalidade cm ON cm.modalidade_id = mjsp.modalidade_id
                INNER JOIN int_jogo ij ON ij.jogo_id = cm.jogo_id
                INNER JOIN mov_sorteio ms ON ms.sorteio_id = mjsp.sorteio_id
                INNER JOIN mov_jb mj ON mj.jb_id = mjsp.jb_id AND mj.cancelado = 'N'
                LEFT JOIN cfg_extracao_descarga cfgdes ON cfgdes.modalidade_id = cm.modalidade_id AND cfgdes.extracao_id = :extracao
                LEFT JOIN data_jb_sort_palpite djsp ON djsp.jb_palpites_id = mjsp.jb_palpites_id
                WHERE ms.data_sorteio = :dataSorteio AND ms.extracao_id = :extracao
                {$where_area}
                {$where_palp}
                {$where_mod}
            )
            SELECT
                w.data_sorteio,
                w.jogou,
                SUM(w.soma_apostado) AS apostado,
                w.mod_abreviacao,
                w.palpite,
                w.colocacao,
                w.extracao,
                w.situacao,
                MAX(w.nome) AS nome,
                MAX(w.data_hora) AS data_hora
            FROM (
                " . implode(" UNION ALL ", $union_queries) . "
            ) w
            GROUP BY w.data_sorteio, w.jogou, w.mod_abreviacao, w.palpite, w.extracao, w.situacao, w.colocacao
            ORDER BY apostado DESC, jogou
        ";

        return $sql;
    }

    public function show()
    {
        if (!$this->loaded && (!isset($_GET['method']) || !in_array($_GET['method'], [
            'onSearch', 'onClear', 'onReload'
        ]))) {
            $this->onReload();
        }
        parent::show();
    }
}
