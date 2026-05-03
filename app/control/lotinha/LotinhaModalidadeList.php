<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class LotinhaModalidadeList extends TStandardList
{
    const JOGO_ID = 37; // LOT

    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('permission');
        parent::setActiveRecord('Modalidade');
        parent::setDefaultOrder('ordem', 'asc');
        parent::setFilterFixedCriteria($this->getJogoCriteria());

        $this->form = new BootstrapFormBuilder('form_lotinha_modalidade');
        $this->form->setFormTitle('Lotinha — Configuração de Modalidade');

        $btn = $this->form->addAction('Atualizar', new TAction([$this, 'onReload']), 'fa:sync blue');
        $btn->class = 'btn btn-sm btn-primary';

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_apresentacao  = new TDataGridColumn('apresentacao', 'Modalidade', 'left', '30%');
        $col_multiplicador = new TDataGridColumn('multiplicador', 'Qtd Números', 'center', '12%');
        $col_cotacao       = new TDataGridColumn('multiplicador_colocacao_01', 'Cotação', 'right', '16%');
        $col_limite        = new TDataGridColumn('limite_palpite', 'Limite Palpite', 'right', '16%');
        $col_ativo         = new TDataGridColumn('ativo', 'Ativo', 'center', '10%');

        $format_brl = function($value) {
            return 'R$ ' . number_format((float)$value, 2, ',', '.');
        };
        $col_cotacao->setTransformer($format_brl);
        $col_limite->setTransformer($format_brl);

        $col_ativo->setTransformer(function($value) {
            return $value == 'S'
                ? "<span class='label label-success'>Sim</span>"
                : "<span class='label label-danger'>Não</span>";
        });

        $this->datagrid->addColumn($col_apresentacao);
        $this->datagrid->addColumn($col_multiplicador);
        $this->datagrid->addColumn($col_cotacao);
        $this->datagrid->addColumn($col_limite);
        $this->datagrid->addColumn($col_ativo);

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

    private function getJogoCriteria()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('jogo_id', '=', self::JOGO_ID));
        return $criteria;
    }

    public function onSearch($param)
    {
        $this->onReload($param);
    }
}
