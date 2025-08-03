<?php

namespace area;

use Adianti\Base\TStandardList;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class AreaList  extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('permission');
        parent::setActiveRecord('Area');
        parent::setDefaultOrder('descricao');
        parent::addFilterField('area_id', '=', 'area_id');
        parent::addFilterField('descricao', 'like', 'descricao');
        parent::addFilterField('ativo', '=', 'ativo');
        parent::setLimit(TSession::getValue(__CLASS__.'_limit') ?? 10);

        parent::setAfterSearchCallback([$this, 'onAfterSearch']);

        $this->form = new BootstrapFormBuilder('form_search_area');
        $this->form->setFormTitle('Áreas');

        $id = new TEntry('area_id');
        $descricao = new TEntry('descricao');
        $ativo = new TEntry('ativo');

        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $id->setSize('30%');
        $descricao->setSize('100%');
        $ativo->setSize('100%');

        $this->form->setData(TSession::getValue('Area_filter_data'));

    }
}