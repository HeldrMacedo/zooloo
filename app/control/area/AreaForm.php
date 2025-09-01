<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;

class AreaForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new \Adianti\Wrapper\BootstrapFormBuilder('form_area');
        $this->form->setFormTitle('Área');
        $this->form->enableClientValidation();

        $id         = new TEntry('area_id');
        $descricao  = new TEntry('descricao');
        $ativo      = new TCombo('ativo');

        $btn = $this->form->addAction( _t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addActionLink( _t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        $id->setSize('50%');
        $descricao->setSize('100%');
        $ativo->setSize('50%');

        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');

        $id->setEditable(false);

        $descricao->addValidation('descricao', new TRequiredValidator);

        $id->setSize('50%');
        $descricao->setSize('100%');
        $ativo->setSize('100%');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Descricao')], [$descricao]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);


        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        //$container->add(new TXMLBreadCrumb('menu.xml', 'SystemUserList'));
        $container->add($this->form);

        // add the container to the page
        parent::add($container);


    }

    public function onSave($param)
    {
        try{
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);
    
            $objetc = new Area();
            $objetc->fromArray((array) $data);
            $objetc->store();

            $data = new stdClass;
            $data->area_id = $objetc->area_id;
            TForm::sendData('form_area', $data);
            TTransaction::close();

            $pos_action = new TAction(['AreaList', 'onReload']);
            new TMessage('info', 'Registro salvo com sucesso!', $pos_action);
        } catch (\Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
        
    }

    public function onReload($param)
    {
        try {
            if ($param['key']) {
                $key=$param['key'];

                TTransaction::open('permission');
                
                $objetc = new Area($key);
                $this->form->setData($objetc);
                TTransaction::close();
            }else {
                $this->form->clear();
            }
        } catch (\Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                $key=$param['key'];
                $this->onReload(['key'=>$key]);
            } else {
                $this->form->clear();
            }
        } catch (\Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}