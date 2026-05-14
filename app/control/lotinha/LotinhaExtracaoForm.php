<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TTime;
use Adianti\Validator\TRequiredValidator;
use Adianti\Wrapper\BootstrapFormBuilder;

class LotinhaExtracaoForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_lotinha_extracao_form');
        $this->form->setFormTitle('Lotinha — Editar Extração');
        $this->form->enableClientValidation();

        $id         = new TEntry('extracao_id');
        $descricao  = new TEntry('descricao');
        $abreviacao = new TEntry('descricao_mobile');
        $horaLimite = new TTime('hora_limite');

        $semanas = new TCheckGroup('semanas');
        $semanas->addItems([
            'segunda' => 'Segunda',
            'terca'   => 'Terça',
            'quarta'  => 'Quarta',
            'quinta'  => 'Quinta',
            'sexta'   => 'Sexta',
            'sabado'  => 'Sábado',
            'domingo' => 'Domingo',
        ]);
        $semanas->setUseButton();
        $semanas->setLayout('horizontal');

        $descricao->addValidation('Descrição', new TRequiredValidator);
        $abreviacao->addValidation('Descrição Mobile', new TRequiredValidator);
        $horaLimite->addValidation('Hora Limite', new TRequiredValidator);

        $id->setEditable(false);
        $id->setSize('50%');
        $descricao->setSize('100%');
        $abreviacao->setSize('100%');
        $horaLimite->setSize('100%');
        $semanas->setSize('100%');

        $this->form->addFields([new TLabel('Id')],               [$id]);
        $this->form->addFields([new TLabel('Descrição')],        [$descricao]);
        $this->form->addFields([new TLabel('Descrição Mobile')], [$abreviacao]);
        $this->form->addFields([new TLabel('Hora Limite')],      [$horaLimite]);
        $this->form->addFields([new TLabel('Dias da Semana')],   [$semanas]);

        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    public function onSave($param = null)
    {
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->validate();
            $this->form->setData($data);

            $diasSemana = ['segunda','terca','quarta','quinta','sexta','sabado','domingo'];

            $extracao = new Extracao($data->extracao_id);

            foreach ($diasSemana as $dia) {
                $extracao->{$dia} = in_array($dia, (array) $data->semanas) ? 'S' : 'N';
            }

            $extracao->descricao        = $data->descricao;
            $extracao->descricao_mobile = $data->descricao_mobile;
            $extracao->hora_limite      = $data->hora_limite;
            $extracao->store();

            TTransaction::close();

            $pos_action = new TAction(['LotinhaExtracaoList', 'onReload']);
            new TMessage('info', 'Extração salva com sucesso!', $pos_action);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param = null)
    {
        try
        {
            TTransaction::open('permission');

            if (!empty($param['key'])) {
                $diasSemana = ['segunda','terca','quarta','quinta','sexta','sabado','domingo'];

                $extracao = new Extracao($param['key']);
                $data = $extracao->toArray();
                $data['semanas'] = [];

                foreach ($diasSemana as $dia) {
                    if ($extracao->{$dia} === 'S') {
                        $data['semanas'][] = $dia;
                    }
                }

                $this->form->setData((object) $data);
            } else {
                $this->form->clear();
            }

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose()
    {
        \Adianti\Widget\Base\TScript::create("Template.closeRightPanel()");
    }
}
