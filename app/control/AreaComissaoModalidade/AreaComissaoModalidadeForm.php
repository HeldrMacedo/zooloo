<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class AreaComissaoModalidadeForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_area_comissao_modalidade');
        $this->form->setFormTitle('Área Comissão Modalidade');
        $this->form->enableClientValidation();

        // Campos do formulário
        $id = new TEntry('area_comissao_modalidade_id');
        $area_id = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', 'apresentacao');
        $comissao = new TEntry('comissao');

        // Configurações dos campos
        $id->setSize('50%');
        $id->setEditable(false);
        
        $area_id->setSize('100%');
        $area_id->addValidation('area_id', new TRequiredValidator);
        
        $modalidade_id->setSize('100%');
        $modalidade_id->addValidation('modalidade_id', new TRequiredValidator);
        
        $comissao->setSize('100%');
        $comissao->setMask('99,99');
        $comissao->setNumericMask(2, ',', '.', true);
        $comissao->addValidation('comissao', new TRequiredValidator);

        // Adicionando campos ao formulário
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Área')], [$area_id]);
        $this->form->addFields([new TLabel('Modalidade')], [$modalidade_id]);
        $this->form->addFields([new TLabel('Comissão (%)')], [$comissao]);

        // Botões de ação
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onClear')), 'fa:eraser red');
        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        // Container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    public function onSave($param)
    {
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);

            // Validação de duplicata
            if (empty($data->area_comissao_modalidade_id))
            {
                $criteria = new TCriteria;
                $criteria->add(new TFilter('area_id', '=', $data->area_id));
                $criteria->add(new TFilter('modalidade_id', '=', $data->modalidade_id));
                
                $existing = AreaComissaoModalidade::getObjects($criteria);
                if ($existing)
                {
                    throw new Exception('Já existe uma comissão cadastrada para esta área e modalidade.');
                }
            }

            // Validação do valor da comissão
            if ($data->comissao < 0 || $data->comissao > 100)
            {
                throw new Exception('A comissão deve estar entre 0% e 100%.');
            }

            $object = new AreaComissaoModalidade;
            $object->fromArray((array) $data);
            $object->store();

            $data = new stdClass;
            $data->area_comissao_modalidade_id = $object->area_comissao_modalidade_id;
            TForm::sendData('form_area_comissao_modalidade', $data);

            TTransaction::close();
            
            $pos_action = new TAction(['AreaComissaoModalidadeList', 'onReload']);
            new TMessage('info', 'Registro salvo com sucesso', $pos_action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                TTransaction::open('permission');
                $object = new AreaComissaoModalidade($key);
                $this->form->setData($object);
                TTransaction::close();
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onClear($param)
    {
        $this->form->clear();
    }

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}