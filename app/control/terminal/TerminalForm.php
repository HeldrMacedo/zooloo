<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class TerminalForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_terminal');
        $this->form->setFormTitle('Terminal');
        $this->form->enableClientValidation();

        $id = new TEntry('terminal_id');
        $serial = new TEntry('serial');
        $criteriaVendedor = new TCriteria;
        $criteriaVendedor->add(new TFilter('ativo', '=', 'S'));
        $vendedor_id = new TDBCombo(
            'vendedor_id',
            'permission',
            'Vendedor',
            'vendedor_id',
            'nome',
            'nome',
            $criteriaVendedor
        );
        $tipo = new TCombo('tipo');
        $tipo->addItems(['APP' => 'APP', 'POS' => 'POS', 'COLETOR' => 'Coletor']);
        $tipo->setValue('APP');

        $multi_usuario = new TCombo('multi_usuario');
        $multi_usuario->addItems(['S' => 'Sim', 'N' => 'Não']);
        $multi_usuario->setValue('N');

        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');

        $id->setEditable(false);
        $id->setSize('50%');
        $serial->setSize('100%');
        $vendedor_id->setSize('100%');
        $tipo->setSize('100%');
        $multi_usuario->setSize('100%');
        $ativo->setSize('100%');

        $serial->addValidation('Serial', new TRequiredValidator);
        $vendedor_id->addValidation('Vendedor', new TRequiredValidator);
        $tipo->addValidation('Tipo', new TRequiredValidator);

        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('Clear'), new TAction([$this, 'onEdit']), 'fa:eraser red');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Serial <span style="color:red;">*</span>')], [$serial]);
        $this->form->addFields([new TLabel('Vendedor <span style="color:red;">*</span>')], [$vendedor_id]);
        $this->form->addFields([new TLabel('Tipo')], [$tipo]);
        $this->form->addFields([new TLabel('Multi usuário')], [$multi_usuario]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        parent::add($container);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);

            $serial = trim((string) ($data->serial ?? ''));
            if ($serial === '') {
                throw new Exception('Serial é obrigatório.');
            }
            if (strlen($serial) > 60) {
                throw new Exception('Serial deve ter no máximo 60 caracteres.');
            }

            // Unicidade de serial
            $repo = new TRepository('Terminal');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('serial', '=', $serial));
            if (!empty($data->terminal_id)) {
                $criteria->add(new TFilter('terminal_id', '!=', $data->terminal_id));
            }
            $dup = $repo->load($criteria);
            if (!empty($dup)) {
                throw new Exception('Já existe um terminal cadastrado com este serial.');
            }

            $object = new Terminal;
            $object->fromArray((array) $data);
            $object->serial = $serial;
            $object->tipo = $data->tipo ?: 'APP';
            $object->multi_usuario = $data->multi_usuario ?: 'N';
            $object->ativo = $data->ativo ?: 'S';
            $object->store();

            $formData = new stdClass;
            $formData->terminal_id = $object->terminal_id;
            TForm::sendData('form_terminal', $formData);

            TTransaction::close();

            $pos_action = new TAction(['TerminalList', 'onReload']);
            new TMessage('info', 'Registro salvo com sucesso!', $pos_action);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onReload($param)
    {
        try {
            TTransaction::open('permission');
            if (!empty($param['key'])) {
                $object = new Terminal($param['key']);
                $this->form->setData($object);
            } else {
                $this->form->clear();
            }
            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                $this->onReload(['key' => $param['key']]);
            } else {
                $this->form->clear();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param)
    {
        TScript::create('Template.closeRightPanel()');
    }
}
