<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class VendasPorSorteioList extends TPage
{
    protected $datagrid;
    protected $panel;
    protected $footerTotal;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_numero   = new TDataGridColumn('sorteio_numero', 'Nº Sorteio', 'center', '10%');
        $col_extracao = new TDataGridColumn('descricao', 'Extração', 'left', '20%');
        $col_data     = new TDataGridColumn('data_sorteio', 'Data', 'center', '10%');
        $col_hora     = new TDataGridColumn('hora_sorteio', 'Hora', 'center', '8%');
        $col_status   = new TDataGridColumn('status', 'Status', 'center', '10%');
        $col_total    = new TDataGridColumn('total_sorteio', 'Total Sorteio', 'right', '14%');
        $col_comissao = new TDataGridColumn('comissao', 'Comissão', 'right', '13%');
        $col_liquido  = new TDataGridColumn('liquido', 'Líquido', 'right', '13%');

        $col_numero->setTransformer(function($v, $object) {
            $num_pad = str_pad($v, 6, '0', STR_PAD_LEFT);
            $data_hora = strtotime($object->data_sorteio . ' ' . $object->hora_sorteio);
            if ($data_hora >= time()) {
                return "<a generator='adianti' href='index.php?class=VendasPorSorteioList&method=onEditData&sorteio_id={$object->sorteio_id}' class='text-primary font-weight-bold' style='text-decoration:underline; cursor:pointer;' title='Alterar data do sorteio'>{$num_pad}</a>";
            }
            return "<span class='text-muted'>{$num_pad}</span>";
        });

        $col_data->setTransformer(fn($v) => $v ? date('d/m/Y', strtotime($v)) : '');
        $col_hora->setTransformer(fn($v) => $v ? substr($v, 0, 5) : '');

        $col_status->setTransformer(function($v, $object) {
            $data_hora = strtotime($object->data_sorteio . ' ' . $object->hora_sorteio);
            if ($data_hora < time()) {
                return "<span class='badge bg-danger'>EXPIRADO</span>";
            }
            return "<span class='badge bg-success'>ABERTO</span>";
        });

        $fmt_brl = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
        $col_total->setTransformer($fmt_brl);
        $col_comissao->setTransformer($fmt_brl);
        $col_liquido->setTransformer($fmt_brl);

        foreach ([$col_numero, $col_extracao, $col_data, $col_hora, $col_status, $col_total, $col_comissao, $col_liquido] as $c) {
            $this->datagrid->addColumn($c);
        }

        $action_edit = new TDataGridAction([$this, 'onEditData']);
        $action_edit->setButtonClass('btn btn-default btn-sm');
        $action_edit->setLabel('Alterar Data');
        $action_edit->setImage('fa:calendar-alt blue');
        $action_edit->setField('sorteio_id');
        $action_edit->setDisplayCondition(function($object) {
            $data_hora = strtotime($object->data_sorteio . ' ' . $object->hora_sorteio);
            return $data_hora >= time();
        });
        $this->datagrid->addAction($action_edit);

        $this->datagrid->createModel();

        $this->footerTotal = new TElement('div');
        $this->footerTotal->style = 'text-align:right;padding:8px;font-weight:bold;';

        $this->panel = new TPanelGroup('Sorteios Abertos');
        $btn_refresh = $this->panel->addHeaderActionLink('Atualizar', new TAction([$this, 'onLoad']), 'fa:sync blue');
        $btn_refresh->class = 'btn btn-sm btn-primary';

        $this->panel->add($this->datagrid)->style = 'overflow-x:auto';
        $this->panel->addFooter($this->footerTotal);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->panel);
        parent::add($container);
    }

    public function onLoad($param = [])
    {
        try {
            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sql = "
                SELECT
                    vs.sorteio_id,
                    vs.sorteio_numero,
                    vs.descricao,
                    vs.data_sorteio,
                    vs.hora_sorteio,
                    vs.situacao,
                    COALESCE(SUM(js.total_sorteio), 0) AS total_sorteio,
                    COALESCE(SUM(js.comissao_sorteio), 0) AS comissao,
                    COALESCE(SUM(js.total_sorteio - js.comissao_sorteio), 0) AS liquido
                FROM vw_sorteio vs
                LEFT JOIN mov_jb_sorteio js ON js.sorteio_id = vs.sorteio_id
                    AND EXISTS (SELECT 1 FROM mov_jb j WHERE j.jb_id = js.jb_id AND j.cancelado = 'N')
                WHERE vs.situacao = 'A'
                GROUP BY vs.sorteio_id, vs.sorteio_numero, vs.descricao, vs.data_sorteio, vs.hora_sorteio, vs.situacao
                ORDER BY vs.data_sorteio DESC, vs.hora_sorteio DESC
            ";

            $stmt = $conn->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            TTransaction::close();

            $this->datagrid->clear();
            $total_geral    = 0;
            $total_comissao = 0;
            $total_liquido  = 0;

            foreach ($rows as $row) {
                $this->datagrid->addItem($row);
                $total_geral    += (float)$row->total_sorteio;
                $total_comissao += (float)$row->comissao;
                $total_liquido  += (float)$row->liquido;
            }

            $fmt = fn($v) => 'R$ ' . number_format($v, 2, ',', '.');
            $this->footerTotal->clearChildren();
            $this->footerTotal->add("Total Sorteio: {$fmt($total_geral)} | Comissão: {$fmt($total_comissao)} | Líquido: {$fmt($total_liquido)}");

            $this->loaded = true;

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEditData($param)
    {
        try {
            $sorteio_id = (int) ($param['sorteio_id'] ?? 0);
            if (empty($sorteio_id)) {
                throw new Exception('Sorteio não informado.');
            }

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $stmt = $conn->prepare("SELECT vs.* FROM vw_sorteio vs WHERE vs.sorteio_id = :id LIMIT 1");
            $stmt->execute([':id' => $sorteio_id]);
            $sorteio = $stmt->fetch(\PDO::FETCH_OBJ);
            TTransaction::close();

            if (!$sorteio) {
                throw new Exception('Sorteio não encontrado.');
            }

            $data_hora = strtotime($sorteio->data_sorteio . ' ' . $sorteio->hora_sorteio);
            if ($data_hora < time()) {
                throw new Exception('Este sorteio já está expirado e não pode ter sua data alterada.');
            }

            $sorteio_num = str_pad($sorteio->sorteio_numero, 6, '0', STR_PAD_LEFT);
            $form = new BootstrapFormBuilder('form_alterar_data_sorteio');
            $form->setFormTitle("<span class='text-nowrap'>Sorteio nº {$sorteio_num} - {$sorteio->descricao}</span>");

            $sorteio_id_field = new THidden('sorteio_id');
            $sorteio_id_field->setValue($sorteio->sorteio_id);

            $nova_data = new TDate('nova_data');
            $nova_data->setMask('dd/mm/yyyy');
            $nova_data->setDatabaseMask('yyyy-mm-dd');
            $nova_data->setValue($sorteio->data_sorteio);
            $nova_data->setSize('100%');

            $info = new TElement('div');
            $info->style = 'margin-bottom: 12px; font-size: 13px; line-height: 1.6;';
            $info->add("
                <strong>Extração:</strong> {$sorteio->descricao}<br>
                <strong>Hora Limite:</strong> " . substr($sorteio->hora_sorteio, 0, 5) . "<br>
                <strong>DataAtual:</strong> " . date('d/m/Y', strtotime($sorteio->data_sorteio)) . "
            ");

            $form->addFields([$sorteio_id_field]);
            $form->addFields([$info]);
            $form->addFields([new TLabel('<span class="text-nowrap">Nova Data:</span>')], [$nova_data]);

            $btn_salvar = $form->addAction('Confirmar', new TAction([$this, 'onSalvarData']), 'fa:check green');
            $btn_salvar->class = 'btn btn-primary';

            $btn_cancelar = $form->addAction('Cancelar', new TAction([$this, 'onFecharModal']), 'fa:ban red');
            $btn_cancelar->class = 'btn btn-secondary';

            $window = TWindow::create('Alterar Data do Sorteio', 550, null);
            $window->removePadding();
            $window->add($form);
            $window->show();

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSalvarData($param)
    {
        try {
            $sorteio_id = (int) ($param['sorteio_id'] ?? 0);
            $nova_data  = $param['nova_data'] ?? '';

            if (empty($sorteio_id) || empty($nova_data)) {
                throw new Exception('Data do sorteio é obrigatória.');
            }

            if (strpos($nova_data, '/') !== false) {
                $d = \DateTime::createFromFormat('d/m/Y', $nova_data);
                if (!$d) throw new Exception('Formato de data inválido.');
                $nova_data = $d->format('Y-m-d');
            }

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $stmt = $conn->prepare("SELECT vs.* FROM vw_sorteio vs WHERE vs.sorteio_id = :id LIMIT 1");
            $stmt->execute([':id' => $sorteio_id]);
            $sorteio = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$sorteio) {
                throw new Exception('Sorteio não encontrado.');
            }

            $nova_data_hora = strtotime($nova_data . ' ' . $sorteio->hora_sorteio);
            if ($nova_data_hora < time()) {
                throw new Exception('A nova data/hora do sorteio não pode ser menor que a data/hora atual!');
            }

            $data_limite = strtotime($sorteio->data_sorteio . ' ' . $sorteio->hora_sorteio . ' +30 days');
            if ($nova_data_hora > $data_limite) {
                throw new Exception('A data não pode ser maior que 30 dias a partir da data original do sorteio!');
            }

            $stmtUpdate = $conn->prepare("UPDATE mov_sorteio SET data_sorteio = :data WHERE sorteio_id = :sorteio_id");
            $stmtUpdate->execute([
                ':data'       => $nova_data,
                ':sorteio_id' => $sorteio_id
            ]);

            TTransaction::close();

            TWindow::closeWindow();
            new TMessage('info', 'Data do sorteio alterada com sucesso!');
            $this->onLoad([]);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onFecharModal($param = [])
    {
        TWindow::closeWindow();
    }

    public function show()
    {
        if (!$this->loaded && (!isset($_GET['method']) || !in_array($_GET['method'], ['onLoad']))) {
            $this->onLoad();
        }
        parent::show();
    }
}

