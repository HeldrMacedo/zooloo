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
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class PremiacaoList extends TPage
{
    // jogo_id de Quininha e Seninha para exibição especial de colocação (int_jogo)
    const JOGO_QUININHA = 25;
    const JOGO_SENINHA  = 27;

    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_premiacao');
        $this->form->setFormTitle('Premiações');

        $data_ini    = new TDate('data_ini');
        $data_fim    = new TDate('data_fim');
        $area_id     = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $vendedor_id = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');
        $pago        = new TCombo('pago');
        $nsu         = new TEntry('nsu');

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));
        $data_fim->setValue(date('Y-m-d'));

        $pago->addItems(['' => 'TODOS', 'N' => 'Não Pagos', 'S' => 'Pagos']);
        $pago->setDefaultOption(false);
        $pago->setSize('100%');
        $pago->setValue('N');

        foreach ([$area_id, $extracao_id, $vendedor_id] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }
        $nsu->placeholder = 'Busca exclusiva por NSU';

        $this->form->addFields([new TLabel('Data Ini:')], [$data_ini], [new TLabel('Data Fim:')], [$data_fim]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Extração:')], [$extracao_id]);
        $this->form->addFields([new TLabel('Vendedor:')], [$vendedor_id], [new TLabel('Pago:')], [$pago]);
        $this->form->addFields([new TLabel('NSU (exclusivo):')], [$nsu]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
        }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_extracao  = new TDataGridColumn('extracao', 'Extração', 'left', '12%');
        $col_data      = new TDataGridColumn('data_hora', 'Data/Hora', 'center', '13%');
        $col_vendedor  = new TDataGridColumn('vendedor', 'Vendedor', 'left', '14%');
        $col_nsu       = new TDataGridColumn('nsu', 'NSU', 'center', '7%');
        $col_palpites  = new TDataGridColumn('palpites', 'Palpite', 'left', '10%');
        $col_modalidade = new TDataGridColumn('apresentacao', 'Modalidade', 'left', '10%');
        $col_apostado  = new TDataGridColumn('total_sorteio', 'Apostado', 'right', '8%');
        $col_premio    = new TDataGridColumn('sorteado_valor', 'Prêmio', 'right', '8%');
        $col_colocacao = new TDataGridColumn('sorteado_colocacao', 'Colocação', 'center', '7%');
        $col_pago      = new TDataGridColumn('sorteado_pago', 'Pago', 'center', '7%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_apostado->setTransformer($fmt_brl);
        $col_premio->setTransformer($fmt_brl);
        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y H:i', strtotime($v)) : '');
        $col_nsu->setTransformer(fn($v) => str_pad($v, 6, '0', STR_PAD_LEFT));
        $col_pago->setTransformer(function($v) {
            return $v === 'S'
                ? "<span class='badge bg-success'>Sim</span>"
                : "<span class='badge bg-warning text-dark'>Não</span>";
        });

        foreach ([$col_extracao,$col_data,$col_vendedor,$col_nsu,$col_palpites,$col_modalidade,$col_apostado,$col_premio,$col_colocacao,$col_pago] as $c) {
            $this->datagrid->addColumn($c);
        }

        $action_pagar = new TDataGridAction([$this, 'onPagar']);
        $action_pagar->setButtonClass('btn btn-success btn-sm');
        $action_pagar->setLabel('Pagar');
        $action_pagar->setImage('fa:dollar-sign');
        $action_pagar->setField('jb_sorteio_id');
        $this->datagrid->addAction($action_pagar);

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
            'pago'     => 'N'
        ];
        $this->form->setData($data);
    }

    public function onReload($param = [])
    {
        $filter = TSession::getValue(__CLASS__.'_filter');
        if (empty($filter)) return;

        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $where  = ["v.sorteado = 'S'", "v.cancelado = 'N'"];
            $params = [];

            if (!empty($filter['nsu'])) {
                $where[] = 'v.nsu = :nsu';
                $params[':nsu'] = (int) $filter['nsu'];
            } else {
                if (!empty($filter['data_ini'])) {
                    $where[] = 'DATE(v.data_hora) >= :data_ini';
                    $params[':data_ini'] = $filter['data_ini'];
                }
                if (!empty($filter['data_fim'])) {
                    $where[] = 'DATE(v.data_hora) <= :data_fim';
                    $params[':data_fim'] = $filter['data_fim'];
                }
                if (!empty($filter['area_id'])) {
                    $where[] = 'v.area_id = :area_id';
                    $params[':area_id'] = $filter['area_id'];
                }
                if (!empty($filter['extracao_id'])) {
                    $where[] = 'v.extracao_id = :extracao_id';
                    $params[':extracao_id'] = $filter['extracao_id'];
                }
                if (!empty($filter['vendedor_id'])) {
                    $where[] = 'v.vendedor_id = :vendedor_id';
                    $params[':vendedor_id'] = $filter['vendedor_id'];
                }
                if ($filter['pago'] !== '') {
                    $where[] = 'v.sorteado_pago = :pago';
                    $params[':pago'] = $filter['pago'];
                }
            }

            // vw_vendajb não expõe jogo_id; o join com cad_modalidade traz o jogo
            // necessário para a exibição de colocação de Quininha/Seninha.
            $sql  = 'SELECT v.*, m.jogo_id FROM vw_vendajb v'
                  . ' LEFT JOIN cad_modalidade m ON m.modalidade_id = v.modalidade_id'
                  . ' WHERE ' . implode(' AND ', $where)
                  . ' ORDER BY v.data_hora DESC LIMIT 500';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $total_apostado = 0; $total_premio = 0;
            foreach ($rows as $row) {
                // Substituição de colocação para Quininha/Seninha
                $row->sorteado_colocacao = $this->formatarColocacao($row->sorteado_colocacao, $row->jogo_id ?? 0);
                $this->datagrid->addItem($row);
                $total_apostado += (float)$row->total_sorteio;
                $total_premio   += (float)$row->sorteado_valor;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add("Total Apostado: {$fmt($total_apostado)} | Total Prêmio: {$fmt($total_premio)}");

            $this->loaded = true;
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function formatarColocacao($colocacao, $jogo_id)
    {
        if (empty($colocacao)) return '';
        $map_qui = ['01' => 'QUI', '02' => 'QUA', '03' => 'TER'];
        $map_sen = ['01' => 'SEN', '02' => 'QUI', '03' => 'QUA'];
        $partes = explode(',', $colocacao);
        if ($jogo_id == self::JOGO_QUININHA) {
            return implode(' ', array_map(fn($p) => $map_qui[trim($p)] ?? $p, $partes));
        }
        if ($jogo_id == self::JOGO_SENINHA) {
            return implode(' ', array_map(fn($p) => $map_sen[trim($p)] ?? $p, $partes));
        }
        return $colocacao;
    }

    public function onPagar($param)
    {
        try {
            $jb_sorteio_id = (int) ($param['jb_sorteio_id'] ?? 0);
            if (empty($jb_sorteio_id)) {
                throw new Exception('Registro não informado.');
            }

            TTransaction::open('permission');
            $item = new MovJbSorteio($jb_sorteio_id);
            TTransaction::close();

            if (!$item || empty($item->jb_sorteio_id)) {
                throw new Exception('Premiação não encontrada.');
            }

            if ($item->sorteado_pago === 'S') {
                new TMessage('info', 'Este bilhete já foi pago!');
                return;
            }

            if ((float) $item->sorteado_valor <= 0) {
                new TMessage('warning', 'Não há prêmio para este jogo.');
                return;
            }

            $action = new TAction([$this, 'onConfirmPagar']);
            $action->setParameter('jb_sorteio_id', $jb_sorteio_id);
            new TQuestion('Deseja pagar esta premiação?', $action);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onConfirmPagar($param)
    {
        try {
            TTransaction::open('permission');
            $item = new MovJbSorteio($param['jb_sorteio_id']);

            if ($item->sorteado_pago === 'S') {
                throw new Exception('Este bilhete já foi pago!');
            }
            if ((float)$item->sorteado_valor <= 0) {
                throw new Exception('Não há prêmio para este jogo.');
            }

            $item->sorteado_pago       = 'S';
            $item->sorteado_valor_pago = $item->sorteado_valor;
            $item->store();
            TTransaction::close();

            new TMessage('info', 'Premiação paga com sucesso!');
            $this->onReload([]);
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
