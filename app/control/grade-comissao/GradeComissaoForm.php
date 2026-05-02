<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class GradeComissaoForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_grade_comissao');
        $this->form->setFormTitle('Grade de Comissão');

        $grade_comissao_id = new TEntry('grade_comissao_id');
        $grade_comissao_id->setEditable(false);
        $grade_comissao_id->style = 'display:none';

        $descricao = new TEntry('descricao');
        $descricao->setSize('100%');
        $descricao->setMaxLength(100);
        $descricao->addValidation('Descrição', new TRequiredValidator);

        $this->form->addFields([$grade_comissao_id]);
        $this->form->addFields([new TLabel('Descrição:')], [$descricao]);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        parent::add($container);
    }

    public function onEdit($param)
    {
        if (!empty($param['key'])) {
            try {
                TTransaction::open('permission');
                $grade = new GradeComissao($param['key']);
                TTransaction::close();

                $this->form->setData($grade);
            } catch (Exception $e) {
                TTransaction::rollback();
                new TMessage('error', $e->getMessage());
            }
        }
    }

    public function onSave($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            TTransaction::open('permission');

            $grade = empty($data->grade_comissao_id)
                ? new GradeComissao
                : new GradeComissao($data->grade_comissao_id);

            $grade->descricao = strtoupper(trim($data->descricao));
            $grade->store();

            TTransaction::close();

            new TMessage('info', 'Grade salva com sucesso!', new TAction(['GradeComissaoList', 'onReload']));
            $this->onClose($param);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
