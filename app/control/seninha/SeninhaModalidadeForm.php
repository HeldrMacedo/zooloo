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
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Wrapper\BootstrapFormBuilder;

class SeninhaModalidadeForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_seninha_modalidade_edit');
        $this->form->setFormTitle('Seninha — Editar Modalidade');
        $this->form->enableClientValidation();

        $id                = new TEntry('modalidade_id');
        $apresentacao      = new TEntry('apresentacao');
        $multiplicador     = new TNumeric('multiplicador', 0, ',', '.', true);
        $limite_palpite    = new TNumeric('limite_palpite', 2, ',', '.', true);
        $mult_colocacao_01 = new TNumeric('multiplicador_colocacao_01', 2, ',', '.', true);
        $mult_colocacao_02 = new TNumeric('multiplicador_colocacao_02', 2, ',', '.', true);
        $mult_colocacao_03 = new TNumeric('multiplicador_colocacao_03', 2, ',', '.', true);
        $ativo             = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);

        $apresentacao->addValidation('Modalidade', new TRequiredValidator);
        $mult_colocacao_01->addValidation('Prêmio Sena', new TRequiredValidator);
        $mult_colocacao_02->addValidation('Prêmio Quina', new TRequiredValidator);
        $mult_colocacao_03->addValidation('Prêmio Quadra', new TRequiredValidator);

        $id->setEditable(false);
        $id->setSize('30%');
        $apresentacao->setSize('100%');
        $multiplicador->setSize('100%');
        $limite_palpite->setSize('100%');
        $mult_colocacao_01->setSize('100%');
        $mult_colocacao_02->setSize('100%');
        $mult_colocacao_03->setSize('100%');
        $ativo->setSize('100%');

        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Modalidade <span class="text-danger">*</span>')], [$apresentacao]);
        $this->form->addFields([new TLabel('Qtd Números')], [$multiplicador]);
        $this->form->addFields([new TLabel('Limite Palpite')], [$limite_palpite]);
        $this->form->addFields(
            [new TLabel('Prêmio Sena (6 ac.) <span class="text-danger">*</span>')],
            [$mult_colocacao_01]
        );
        $this->form->addFields(
            [new TLabel('Prêmio Quina (5 ac.) <span class="text-danger">*</span>')],
            [$mult_colocacao_02]
        );
        $this->form->addFields(
            [new TLabel('Prêmio Quadra (4 ac.) <span class="text-danger">*</span>')],
            [$mult_colocacao_03]
        );
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $btn = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addHeaderActionLink('Fechar', new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    public function onEdit($param = null)
    {
        try {
            TTransaction::open('permission');

            if (!empty($param['key'])) {
                $modalidade = new Modalidade($param['key']);
                $this->form->setData($modalidade);
            } else {
                $this->form->clear();
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave($param = null)
    {
        try {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->validate();
            $this->form->setData($data);

            $modalidade = new Modalidade($data->modalidade_id);
            $modalidade->apresentacao               = strtoupper($data->apresentacao);
            $modalidade->multiplicador              = $data->multiplicador;
            $modalidade->limite_palpite             = $data->limite_palpite;
            $modalidade->multiplicador_colocacao_01 = $data->multiplicador_colocacao_01;
            $modalidade->multiplicador_colocacao_02 = $data->multiplicador_colocacao_02;
            $modalidade->multiplicador_colocacao_03 = $data->multiplicador_colocacao_03;
            $modalidade->ativo                      = $data->ativo == 'S' ? 'S' : 'N';
            $modalidade->store();

            TTransaction::close();

            $pos_action = new TAction(['SeninhaModalidadeList', 'onReload']);
            new TMessage('info', 'Modalidade salva com sucesso!', $pos_action);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param = null)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
