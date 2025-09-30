<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Validator\TRequiredValidator;
use Adianti\Validator\TNumericValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class ExtracaoDescargaForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_extracao_descarga');
        $this->form->setFormTitle('Extração Descarga');
        $this->form->enableClientValidation();

        // Filtro para extrações ativas
        $criteria_extracao = new TCriteria;
        $criteria_extracao->add(new TFilter('ativo', '=', 'S'));

        // Filtro para modalidades ativas
        $criteria_modalidade = new TCriteria;
        $criteria_modalidade->add(new TFilter('ativo', '=', 'S'));

        $id = new TEntry('extracao_descarga_id');
        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao', 'descricao', $criteria_extracao);
        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', '{jogo->descricao}', 'ordem', $criteria_modalidade);
        $limite_descarga = new TEntry('limite_descarga');

        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        $id->setSize('50%');
        $id->setEditable(false);
        $extracao_id->setSize('100%');
        $extracao_id->addValidation('extracao_id', new TRequiredValidator);
        $modalidade_id->setSize('100%');
        $modalidade_id->addValidation('modalidade_id', new TRequiredValidator);
        $limite_descarga->setSize('100%');
        $limite_descarga->setNumericMask(2, ',', '.', true);
        $limite_descarga->addValidation('limite_descarga', new TRequiredValidator);
        $limite_descarga->addValidation('limite_descarga', new TNumericValidator);

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Extração')], [$extracao_id]);
        $this->form->addFields([new TLabel('Modalidade')], [$modalidade_id]);
        $this->form->addFields([new TLabel('Limite Descarga')], [$limite_descarga]);

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
            if (empty($data->extracao_id))
            {
                throw new Exception('Extração é obrigatória');
            }
            
            if (empty($data->modalidade_id))
            {
                throw new Exception('Modalidade é obrigatória');
            }
            
            if (empty($data->limite_descarga))
            {
                throw new Exception('Limite Descarga é obrigatório');
            }
            
            $limite_num = (float) str_replace(',', '.', $data->limite_descarga);
            if ($limite_num <= 0)
            {
                throw new Exception('Limite Descarga deve ser maior que zero');
            }

            // Verificar duplicidade
            $criteria = new TCriteria;
            $criteria->add(new TFilter('extracao_id', '=', $data->extracao_id));
            $criteria->add(new TFilter('modalidade_id', '=', $data->modalidade_id));
            
            if (!empty($data->extracao_descarga_id))
            {
                $criteria->add(new TFilter('extracao_descarga_id', '!=', $data->extracao_descarga_id));
            }
            
            $repository = new TRepository('ExtracaoDescarga');
            $existing = $repository->load($criteria);
            
            if ($existing)
            {
                throw new Exception('Já existe uma configuração para esta extração e modalidade');
            }

            $object = new ExtracaoDescarga();
            $object->fromArray((array) $data);
            $object->limite_descarga = $limite_num;
            $object->store();

            $data = new stdClass;
            $data->extracao_descarga_id = $object->extracao_descarga_id;
            TForm::sendData('form_extracao_descarga', $data);

            TTransaction::close();
            $pos_action = new TAction(['ExtracaoDescargaList', 'onReload']);
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
                $object = new ExtracaoDescarga($key);
                $object->limite_descarga = number_format($object->limite_descarga, 2, ',', '.');
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