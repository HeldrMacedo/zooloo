<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ConsultaVendasList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_consulta_vendas');
        $this->form->setFormTitle('Consulta de Vendas');

        $data_ini   = new TDate('data_ini');
        $data_fim   = new TDate('data_fim');
        $area_id    = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao');
        $vendedor_id = new TDBCombo('vendedor_id', 'permission', 'Vendedor', 'vendedor_id', 'nome');
        $situacao   = new TCombo('situacao');
        $nsu        = new TEntry('nsu');

        $data_ini->setMask('dd/mm/yyyy'); $data_ini->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy'); $data_fim->setDatabaseMask('yyyy-mm-dd');
        $data_ini->setValue(date('Y-m-d'));
        $data_fim->setValue(date('Y-m-d'));

        $situacao->addItems(['' => 'TODOS', 'ATIVO' => 'ATIVO', 'CANCELADO' => 'CANCELADO']);
        $situacao->setDefaultOption(false);
        $situacao->setSize('100%');
        $situacao->setValue('');

        foreach ([$area_id, $extracao_id, $vendedor_id] as $f) {
            $f->setSize('100%');
            $f->setDefaultOption(true);
        }
        $nsu->placeholder = 'Busca exclusiva por NSU';

        $this->form->addFields([new TLabel('Data Ini:')], [$data_ini], [new TLabel('Data Fim:')], [$data_fim]);
        $this->form->addFields([new TLabel('Área:')], [$area_id], [new TLabel('Extração:')], [$extracao_id]);
        $this->form->addFields([new TLabel('Vendedor:')], [$vendedor_id], [new TLabel('Situação:')], [$situacao]);
        $this->form->addFields([new TLabel('NSU (exclusivo):')], [$nsu]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        if ($filter_data = TSession::getValue(__CLASS__.'_filter_data')) {
            $this->form->setData($filter_data);
        }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_nsu       = new TDataGridColumn('nsu', 'NSU', 'center', '7%');
        $col_data      = new TDataGridColumn('data_hora', 'Data/Hora', 'center', '14%');
        $col_vendedor  = new TDataGridColumn('vendedor', 'Vendedor', 'left', '16%');
        $col_modalidade = new TDataGridColumn('apresentacao', 'Modalidade', 'left', '12%');
        $col_situacao  = new TDataGridColumn('situacao', 'Situação', 'center', '8%');
        $col_extracao  = new TDataGridColumn('extracao', 'Extração', 'left', '12%');
        $col_comissao  = new TDataGridColumn('comissao_sorteio', 'Comissão', 'right', '9%');
        $col_total     = new TDataGridColumn('total_sorteio', 'Total', 'right', '9%');
        $col_previsto  = new TDataGridColumn('previsao_premio', 'Prev. Prêmio', 'right', '9%');

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_comissao->setTransformer($fmt_brl);
        $col_total->setTransformer($fmt_brl);
        $col_previsto->setTransformer($fmt_brl);

        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y H:i:s', strtotime($v)) : '');

        $col_nsu->setTransformer(function($v, $object) {
            $nsu_str = str_pad($v, 6, '0', STR_PAD_LEFT);
            return "<a generator='adianti' href='index.php?class=ConsultaVendasList&method=onView&nsu={$v}' class='text-primary font-weight-bold' style='text-decoration:underline; cursor:pointer;'>{$nsu_str}</a>";
        });

        $col_situacao->setTransformer(function($v) {
            return $v === 'CANCELADO'
                ? "<span class='label label-danger'>CANCELADO</span>"
                : "<span class='label label-success'>ATIVO</span>";
        });

        foreach ([$col_nsu,$col_data,$col_vendedor,$col_modalidade,$col_situacao,$col_extracao,$col_comissao,$col_total,$col_previsto] as $c) {
            $this->datagrid->addColumn($c);
        }

        $action_view = new TDataGridAction([$this, 'onView']);
        $action_view->setButtonClass('btn btn-default btn-sm');
        $action_view->setLabel('Detalhes');
        $action_view->setImage('fa:eye blue');
        $action_view->setField('nsu');
        $this->datagrid->addAction($action_view);

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
            'situacao' => ''
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

            $where  = ['1=1'];
            $params = [];

            // Modo NSU exclusivo
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
                if (!empty($filter['situacao'])) {
                    $where[] = 'situacao ILIKE :situacao';
                    $params[':situacao'] = '%' . $filter['situacao'] . '%';
                }
            }

            $sql = 'SELECT * FROM vw_vendajb WHERE ' . implode(' AND ', $where) . ' ORDER BY data_hora DESC LIMIT 500';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $total_comissao = 0;
            $total_venda = 0;
            $total_previsto = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                if ($row->situacao !== 'CANCELADO') {
                    $total_comissao += (float)$row->comissao_sorteio;
                    $total_venda    += (float)$row->total_sorteio;
                    $total_previsto += (float)$row->previsao_premio;
                }
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add("Comissão: {$fmt($total_comissao)} | Total: {$fmt($total_venda)} | Prev. Prêmio: {$fmt($total_previsto)}");

            $this->loaded = true;
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onView($param)
    {
        try {
            if (empty($param['nsu'])) {
                throw new Exception('NSU não informado.');
            }

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sql = 'SELECT * FROM vw_vendajb WHERE nsu = :nsu LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':nsu' => (int) $param['nsu']]);
            $venda = $stmt->fetch(\PDO::FETCH_OBJ);
            TTransaction::close();

            if (!$venda) {
                throw new Exception('Venda não encontrada para o NSU informado.');
            }

            // Status de pagamento conforme regra original
            if ($venda->sorteado === 'S') {
                $sorteado_valor      = (float) ($venda->sorteado_valor ?? 0);
                $sorteado_valor_pago = (float) ($venda->sorteado_valor_pago ?? 0);

                if ($sorteado_valor_pago <= 0) {
                    $pago_html = "<span class='badge bg-warning text-dark'>Não foi pago!</span>";
                } elseif ($sorteado_valor > 0 && $sorteado_valor == $sorteado_valor_pago) {
                    $pago_html = "<span class='badge bg-success'>Pago totalmente!</span>";
                } elseif ($sorteado_valor_pago > 0 && $sorteado_valor > $sorteado_valor_pago) {
                    $pago_html = "<span class='badge bg-info text-white'>Pago parcialmente!</span>";
                } else {
                    $pago_html = "<span class='badge bg-success'>Pago!</span>";
                }
            } else {
                $pago_html = "<span class='text-muted'>Venda sem premiação!</span>";
            }

            $sorteado_badge = ($venda->sorteado === 'S')
                ? "<span class='badge bg-success'>SIM</span>"
                : "<span class='badge bg-secondary'>NÃO</span>";

            $situacao_badge = ($venda->situacao === 'CANCELADO')
                ? "<span class='badge bg-danger'>CANCELADO</span>"
                : "<span class='badge bg-success'>ATIVO</span>";

            $nsu_formatado = str_pad($venda->nsu, 6, '0', STR_PAD_LEFT);
            $data_hora     = $venda->data_hora ? date('d/m/Y H:i:s', strtotime($venda->data_hora)) : '—';
            $poule         = $venda->poule ?? $venda->pouple ?? '—';
            $palpites      = !empty($venda->palpites) ? str_replace(',', ' ', $venda->palpites) : (!empty($venda->palpite) ? str_replace(',', ' ', $venda->palpite) : '—');
            $total_fmt     = 'R$ ' . number_format((float)$venda->total_sorteio, 2, ',', '.');
            $comissao_fmt  = 'R$ ' . number_format((float)($venda->comissao_sorteio ?? 0), 2, ',', '.');
            $premio_fmt    = 'R$ ' . number_format((float)($venda->sorteado_valor ?? 0), 2, ',', '.');
            $previsto_fmt  = 'R$ ' . number_format((float)($venda->previsao_premio ?? 0), 2, ',', '.');
            $coloc_ini     = $venda->colocao_inicial ?? $venda->colocacao_inicial ?? '1';
            $coloc_fim     = $venda->colocao_final ?? $venda->colocacao_final ?? '1';

            $reimpressao_info = ($venda->reimpressao ?? 0);
            if (!empty($venda->data_reimpressao)) {
                $reimpressao_info .= ' - ' . date('d/m/Y H:i', strtotime($venda->data_reimpressao));
            } else {
                $reimpressao_info .= ' - Sem Reimpressão';
            }

            $content = new TElement('div');
            $content->style = 'padding: 15px; font-size: 14px;';
            $content->add("
                <div class='card mb-2'>
                    <div class='card-header bg-primary text-white d-flex justify-content-between align-items-center' style='padding: 10px 15px;'>
                        <strong><i class='fa fa-receipt'></i> Detalhes da Venda - NSU {$nsu_formatado}</strong>
                        <span>{$situacao_badge}</span>
                    </div>
                    <div class='card-body' style='line-height: 1.8; padding: 15px;'>
                        <div class='row'>
                            <div class='col-md-6'>
                                <strong>Cliente:</strong> " . ($venda->cliente ?: '—') . "<br>
                                <strong>Fone:</strong> " . ($venda->fone ?: '—') . "<br>
                                <strong>Data:</strong> {$data_hora}<br>
                                <strong>Bilhete/Poule:</strong> {$poule}<br>
                                <strong>Extração:</strong> {$venda->extracao}<br>
                                <strong>Vendedor:</strong> {$venda->vendedor}
                            </div>
                            <div class='col-md-6'>
                                <strong>Modalidade:</strong> " . ($venda->apresentacao ?? $venda->modalidade) . "<br>
                                <strong>Sorteado:</strong> {$sorteado_badge}<br>
                                <strong>Pagamento:</strong> {$pago_html}<br>
                                <strong>Palpites (Qtde):</strong> " . ($venda->qtde_palpites ?? 1) . "<br>
                                <strong>Prêmios:</strong> {$coloc_ini}ºP ao {$coloc_fim}ºP<br>
                                <strong>Valor a pagar:</strong> <span class='text-primary font-weight-bold'>{$total_fmt}</span>
                            </div>
                        </div>

                        <hr style='margin: 12px 0;'>

                        <div>
                            <strong>Palpites:</strong>
                            <div style='color: #000000; background:#f8f9fa;padding:10px;border-radius:4px;font-family:monospace;font-size:15px;word-break:break-all;margin-top:5px;border:1px solid #e9ecef;'>
                                {$palpites}
                            </div>
                        </div>

                        <hr style='margin: 12px 0;'>

                        <div class='row text-muted' style='font-size: 12px;'>
                            <div class='col-md-6'>
                                <strong>NSU:</strong> {$venda->nsu}<br>
                                <strong>MD5:</strong> " . ($venda->md5 ?: '—') . "
                            </div>
                            <div class='col-md-6'>
                                <strong>Comissão:</strong> {$comissao_fmt} | <strong>Prev. Prêmio:</strong> {$previsto_fmt}<br>
                                <strong>Reimpressão:</strong> {$reimpressao_info}
                            </div>
                        </div>
                    </div>
                </div>
            ");

            $window = TWindow::create('Detalhes da Venda', 680, null);
            $window->add($content);
            $window->show();

        } catch (Exception $e) {
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
