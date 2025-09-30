<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Validator\TRequiredValidator;
use Adianti\Validator\TNumericValidator;
use Adianti\Validator\TMaxLengthValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;


class PalpiteCotadoForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_palpite_cotado');
        $this->form->setFormTitle('Palpite Cotado');
        $this->form->enableClientValidation();

         // Filtro para modalidades ativas com multiplicador
        $criteria = new TCriteria;
        $criteria->add(new TFilter('ativo', '=', 'S'));
        $criteria->add(new TFilter('multiplicador', 'IS NOT', NULL));


        $id = new TEntry('palpite_cotado_id');
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', '{jogo->descricao}', 'ordem', $criteria);
        $palpite = new TEntry('palpite');
        $cotacao = new TEntry('cotacao');
        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');

    
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        $id->setSize('50%');
        $id->setEditable(false);
        $modalidade_id->setSize('100%');
        $modalidade_id->addValidation('modalidade_id', new TRequiredValidator);
        $palpite->setSize('100%');
        $palpite->setMask('9999');
        $palpite->addValidation('palpite', new TRequiredValidator);
        $palpite->addValidation('palpite', new TMaxLengthValidator, array(4));
        $cotacao->setSize('100%');
        $cotacao->setMask('99,99');
        $cotacao->setNumericMask(2, ',', '.', true);
        $cotacao->addValidation('cotacao', new TRequiredValidator);
        $cotacao->addValidation('cotacao', new TNumericValidator);
        $ativo->setSize('100%');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Modalidade')], [$modalidade_id], [new TLabel('Palpite')], [$palpite]);
        $this->form->addFields([new TLabel('Cotação (%)')], [$cotacao], [new TLabel('Ativo')], [$ativo]);

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

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

            // Validações customizadas
            if (empty($data->modalidade_id))
            {
                throw new Exception('Modalidade é obrigatória');
            }
            
            if (empty($data->palpite))
            {
                throw new Exception('Palpite é obrigatório');
            }
            
            if (strlen($data->palpite) != 4)
            {
                throw new Exception('Palpite deve ter exatamente 4 dígitos');
            }
            
            if (!is_numeric($data->palpite))
            {
                throw new Exception('Palpite deve conter apenas números');
            }
            
            if (empty($data->cotacao))
            {
                throw new Exception('Cotação é obrigatória');
            }
            
            $cotacao_num = (float) str_replace(',', '.', $data->cotacao);
            if ($cotacao_num < 0 || $cotacao_num > 100)
            {
                throw new Exception('Cotação deve estar entre 0 e 100%');
            }

            // Verificar duplicidade
            $criteria = new TCriteria;
            $criteria->add(new TFilter('modalidade_id', '=', $data->modalidade_id));
            $criteria->add(new TFilter('palpite', '=', $data->palpite));
            
            if (!empty($data->palpite_cotado_id))
            {
                $criteria->add(new TFilter('palpite_cotado_id', '!=', $data->palpite_cotado_id));
            }
            
            $repository = new TRepository('PalpiteCotado');
            $existing = $repository->load($criteria);
            
            if ($existing)
            {
                throw new Exception('Já existe um palpite cotado para esta modalidade e palpite');
            }

            $object = new PalpiteCotado();
            $object->fromArray((array) $data);
            $object->cotacao = $cotacao_num;
            $object->store();

            $data = new stdClass;
            $data->palpite_cotado_id = $object->palpite_cotado_id;
            TForm::sendData('form_palpite_cotado', $data);

            TTransaction::close();
            $pos_action = new TAction(['PalpiteCotadoList', 'onReload']);
            new TMessage('info', 'Registro salvo com sucesso', $pos_action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onReload($param) {}

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                TTransaction::open('permission');
                $object = new PalpiteCotado($key);
                $object->cotacao = number_format($object->cotacao, 2, ',', '.');
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

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}