<?php

use Adianti\Base\TStandardList;
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
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class LotinhaModalidadeList extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        parent::setDefaultOrder('apresentacao');
        parent::addFilterField('apresentacao', 'like', 'apresentacao');

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        $this->form = new BootstrapFormBuilder('form_search_lotinha_modalidade');
        $this->form->setFormTitle('Lotinha — Configuração de Modalidade');

        $apresentacao = new TEntry('apresentacao');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $btn = $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_apresentacao  = new TDataGridColumn('apresentacao',               'Modalidade',     'left');
        $col_multiplicador = new TDataGridColumn('multiplicador',              'Qtd Números',    'center');
        $col_limite        = new TDataGridColumn('limite_palpite',             'Limite Palpite', 'center');
        $col_cotacao       = new TDataGridColumn('multiplicador_colocacao_01', 'Cotação',        'right');
        $col_ativo         = new TDataGridColumn('ativo',                     'Ativo',          'center');

        $format_brl = fn($value) => 'R$ ' . number_format((float)$value, 2, ',', '.');
        $col_cotacao->setTransformer($format_brl);

        $col_multiplicador->setTransformer(fn($value) => intval($value));
        $col_limite->setTransformer(fn($value) => intval($value));

        $col_ativo->setTransformer(fn($value) => $value == 'S'
            ? "<span class='label label-success'>Sim</span>"
            : "<span class='label label-danger'>Não</span>");

        $this->datagrid->addColumn($col_apresentacao);
        $this->datagrid->addColumn($col_multiplicador);
        $this->datagrid->addColumn($col_limite);
        $this->datagrid->addColumn($col_cotacao);
        $this->datagrid->addColumn($col_ativo);

        $action_edit = new TDataGridAction(['LotinhaModalidadeForm', 'onEdit'], ['key' => '{modalidade_id}', 'register_state' => 'false']);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel('Editar');
        $action_edit->setImage('far:edit blue');
        $this->datagrid->addAction($action_edit);

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Lotinha — Configuração de Modalidade');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        $btnf = TButton::create('find', [$this, 'onSearch'], '', 'fa:search');
        $btnf->style = 'height: 37px; margin-right:4px;';

        $form_search = new \Adianti\Widget\Form\TForm('form_search_descricao');
        $form_search->style = 'float:left;display:flex';
        $form_search->add($apresentacao, true);
        $form_search->add($btnf, true);

        $panel->addHeaderWidget($form_search);

        if (TSession::getValue(get_class($this) . '_filter_counter') > 0) {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel(_t('Filters') . ' (' . TSession::getValue(get_class($this) . '_filter_counter') . ')');
        }

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($panel);

        parent::add($container);
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('permission');

            $filtro_banca = IntJogo::FILTRO_BANCA_LOTINHA;
            $modalidades = Modalidade::where("jogo_id", 'in', "(SELECT jogo_id FROM int_jogo WHERE filtro_banca = :[{$filtro_banca}]:)")->load();

            usort($modalidades, fn($a, $b) => strnatcasecmp($a->apresentacao, $b->apresentacao));
            $objects = (object) $modalidades;

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            TTransaction::close();
            $this->loaded = true;
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'], ['onReload'])))) {
            $this->onReload();
        }
        parent::show();
    }
}
