<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Form\TTime;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class ExtracaoForm extends TPage
{
    protected $form;


    public function __construct()
    {
        parent::__construct(); 
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_extracao_form');
        $this->form->setFormTitle('Extração');
        $this->form->enableClientValidation();
        
        $id = new TEntry('extracao_id');
        $descricao = new TEntry('descricao');
        $abreviacao = new TEntry('descricao_mobile');
        $horaLimite = new TTime('hora_limite');
        $premiacao_maxima = new TEntry('premiacao_maxima');
        $premiacao_maxima->setMask('999');
        $semanas =  new  TCheckGroup('semanas');
        $dataPrimeiroSorteio = new TDate('dia_sorteio_inicial');
        $calculoSorteio = new  TDBCombo('calculo_id', 'permission', 'IntCalculoSorteio', 'calculo_id', 'descricao', 'descricao');
        //$limitePalpite = new TNumeric('limite_palpite', 2,',','.', true);
        $ativo = new TCombo('ativo');
        $ativo->addItems([
            'S' => 'Sim',
            'N' => 'Não',
        ]);

        $descricao->addValidation('Descricão', new TRequiredValidator);
        $abreviacao->addValidation('descricao_mobile', new TRequiredValidator);
        $horaLimite->addValidation('Hora Limite', new TRequiredValidator);
        $premiacao_maxima->addValidation('Prêmio Máximo', new TRequiredValidator);
        $dataPrimeiroSorteio->addValidation('Data Primeiro Sorteio', new TRequiredValidator);
    
        $dataPrimeiroSorteio->setMask('dd/mm/yyyy');
        $dataPrimeiroSorteio->setDatabaseMask('yyyy-mm-dd');


        $semanas->addItems([
            'segunda'   => 'Segunda',
            'terca'     => 'Terça',
            'quarta'    => 'Quarta',
            'quinta'    => 'Quinta',
            'sexta'     => 'Sexta',
            'sabado'    => 'Sabado',
            'domingo'   => 'Domingo',
        ]);
        $semanas->setUseButton();
        $semanas->setLayout('horizontal');



        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        $id->setEditable(false);
        $id->setSize('50%');
        $descricao->setSize('100%');
        $abreviacao->setSize('100%');
        $horaLimite->setSize('100%');
        $premiacao_maxima->setSize('100%');
        $dataPrimeiroSorteio->setSize('100%');
        $calculoSorteio->setSize('100%');
        //$limitePalpite->setSize('100%');
        $ativo->setSize('100%');
        $ativo->setValue('S');
        $semanas->setSize('100%');
       
        
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Descricao')], [$descricao]);
        $this->form->addFields([new TLabel('Abreviacao')], [$abreviacao]);
        $this->form->addFields([new TLabel('Hora Limite')], [$horaLimite]);
        $this->form->addFields([new TLabel('Premiacao Maxima')], [$premiacao_maxima]);
        $this->form->addFields([new TLabel('Data Primeiro Sorteio')], [$dataPrimeiroSorteio]);
        $this->form->addFields([new TLabel('Calculo Sorteio')], [$calculoSorteio]);
        //$this->form->addFields([new TLabel('Limite Palpite')], [$limitePalpite]);
        $this->form->addFields([new TLabel('Semanas')], [$semanas]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        //$container->add(new TXMLBreadCrumb('menu.xml', 'SystemUserList'));
        $container->add($this->form);

        // add the container to the page
        parent::add($container);        

    }

    public function onSave($param = null)
    {
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);

            $diaSemana = ['segunda','terca','quarta','quinta','sexta','sabado','domingo'];

            $extracao = new Extracao();
            foreach($diaSemana as $value)
            {
                if (in_array($value, $data->semanas)) 
                {
                    $extracao->{$value} = 'S';
                }
                else
                {
                    $extracao->{$value} = 'N';
                }
            }
            $extracao->filtro_banca = 1;
            $extracao->fromArray((array) $data);
            $extracao->store();

            $data = new stdClass;
            $data->extracao_id = $extracao->extracao_id;
            TForm::sendData('form_extracao', $data);
            TTransaction::close();

            $pos_action = new TAction(['ExtracaoList', 'onReload']);
            new TMessage('info', 'Extração salva com sucesso!', $pos_action);
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());            
        }
    }

    public function onReload($param = null)
    {
        try 
        {
            TTransaction::open('permission');
            if ($param['key']) {
                $key=$param['key'];
                $diasSemana = ['segunda','terca','quarta','quinta','sexta','sabado','domingo'];
                
                $object = new Extracao($key);
                $data = $object->toArray();
                $data['semanas'] = [];
                foreach($diasSemana as $value)
                {
                    if ($object->{$value} == 'S') 
                    {
                        $data['semanas'][] = $value;
                    }
                }

                $this->form->setData((object) $data);

                
            }else {
                $this->form->cleat();
            }
            TTransaction::close();
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
            if (isset($param['key'])) {
                $key=$param['key'];
                $this->onReload(['key'=>$key]);
            }else {
                $this->form->clear();
            }
        }
        catch (Exception $e) 
        {

        }
    }

    public function onClose()
    {
        TScript::create("Template.closeRightPanel()");
    }

    public function onDelete($param = null)
    {

    }
}

