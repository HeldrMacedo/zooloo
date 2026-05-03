<?php

use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Registry\TSession;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class SeninhaExtracaoList extends TStandardList
{
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('permission');
        parent::setActiveRecord('Extracao');
        parent::setDefaultOrder('descricao');
        parent::addFilterField('descricao', 'like', 'descricao');

        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        $this->form = new BootstrapFormBuilder('form_seninha_extracao');
        $this->form->setFormTitle('Seninha — Extrações');

        $descricao = new TEntry('descricao');
        $descricao->setSize('100%');

        $this->form->addFields([new TLabel('Descrição:')], [$descricao]);
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_id        = new TDataGridColumn('extracao_id', 'ID', 'center', '8%');
        $col_descricao = new TDataGridColumn('descricao', 'Descrição', 'left', '35%');
        $col_hora      = new TDataGridColumn('hora_limite', 'Hora Limite', 'center', '12%');
        $col_seg       = new TDataGridColumn('segunda', 'Seg', 'center', '7%');
        $col_ter       = new TDataGridColumn('terca', 'Ter', 'center', '7%');
        $col_qua       = new TDataGridColumn('quarta', 'Qua', 'center', '7%');
        $col_qui       = new TDataGridColumn('quinta', 'Qui', 'center', '7%');
        $col_sex       = new TDataGridColumn('sexta', 'Sex', 'center', '7%');
        $col_sab       = new TDataGridColumn('sabado', 'Sáb', 'center', '5%');
        $col_dom       = new TDataGridColumn('domingo', 'Dom', 'center', '5%');

        $fmt_sim_nao = fn($v) => $v === 'S'
            ? "<span class='label label-success'>S</span>"
            : "<span class='label label-default'>N</span>";

        foreach ([$col_seg,$col_ter,$col_qua,$col_qui,$col_sex,$col_sab,$col_dom] as $c) {
            $c->setTransformer($fmt_sim_nao);
        }

        foreach ([$col_id,$col_descricao,$col_hora,$col_seg,$col_ter,$col_qua,$col_qui,$col_sex,$col_sab,$col_dom] as $c) {
            $this->datagrid->addColumn($c);
        }

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new \Adianti\Widget\Container\TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        parent::add($container);
    }
}
