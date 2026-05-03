<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
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
    // jogo_id de Quininha e Seninha para exibição especial de colocação
    const JOGO_QUININHA = 32;
    const JOGO_SENINHA  = 27;

    protected $form;
    protected $datagrid;
    protected $pageNavigation;

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
        $data_ini->setValue(date('d/m/Y'));
        $data_fim->setValue(date('d/m/Y'));

        $pago->addItems(['' => 'TODOS', 'N' => 'Não Pagos', 'S' => 'Pagos']);
        $pago->setValue('N');

        foreach ([$area_id, $extracao_id, $vendedor_id, $pago] as $f) {
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

        $this->form->setData(TSession::getValue(__CLASS__.'_filter_data'));

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
                ? "<span class='label label-success'>Sim</span>"
                : "<span class='label label-warning'>Não</span>";
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

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

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

            $where  = ["sorteado = 'S'", "cancelado = 'N'"];
            $params = [];

            if (!empty($filter['nsu'])) {
                $where[] = 'nsu = :nsu';
                $params[':nsu'] = (int) $filter['nsu'];
            } else {
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
                if (!empty($filter['vendedor_id'])) {
                    $where[] = 'vendedor_id = :vendedor_id';
                    $params[':vendedor_id'] = $filter['vendedor_id'];
                }
                if ($filter['pago'] !== '') {
                    $where[] = 'sorteado_pago = :pago';
                    $params[':pago'] = $filter['pago'];
                }
            }

            $sql  = 'SELECT * FROM vw_vendajb WHERE ' . implode(' AND ', $where) . ' ORDER BY data_hora DESC LIMIT 500';
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
            $this->datagrid->updatePage();
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
        $action = new TAction([$this, 'onConfirmPagar']);
        $action->setParameter('jb_sorteio_id', $param['jb_sorteio_id']);
        new TQuestion('Deseja pagar esta premiação?', $action);
    }

    public function onConfirmPagar($param)
    {
        try {
            TTransaction::open('permission');
            $item = new MovJbSorteio($param['jb_sorteio_id']);

            if ($item->sorteado_pago === 'S') {
                throw new Exception('O prêmio já foi pago.');
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
}
