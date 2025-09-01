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
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBEntry;
use Adianti\Wrapper\BootstrapFormBuilder;

class GerenteForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_gerente');
        $this->form->setFormTitle('Gerente');
        $this->form->enableClientValidation();

        $id            = new TEntry('coletor_id');
        $nome          = new TEntry('nome');
        $login         = new TEntry('login');
        $senha         = new TPassword('password ');
        $confirmar_senha = new TPassword('repassword');
        $area_id = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao');
        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');

        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        $id->setSize('50%');
        $id->setEditable(false);
        $nome->setSize('100%');
        $nome->addValidation('nome', new TRequiredValidator);
        $login->setSize('100%');
        $login->addValidation('login', new TRequiredValidator);
        $senha->setSize('100%');
        $confirmar_senha->setSize('100%');
        $area_id->setSize('100%');
        $area_id->addValidation('area_id', new TRequiredValidator);
        $ativo->setSize('100%');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Nome')], [$nome], [new TLabel('Login')], [$login]);
        $this->form->addFields([new TLabel('Senha')], [$senha], [new TLabel('Confirmar Senha')], [$confirmar_senha]);
        $this->form->addFields([new TLabel('Area')], [$area_id], [new TLabel('Ativo')], [$ativo]);

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
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);

            $object = new SystemUser;
            $object->fromArray((array) $data);
            $object->name = $data->nome;
            $object->email = $data->login . '@zooloo.com';
            $object->frontpage_id = SystemUser::FRONTPAGE_ID;
            $object->active = $data->ativo;


            $senha = $object->password;

            if( empty($object->login) )
            {
                throw new Exception(TAdiantiCoreTranslator::translate('The field ^1 is required', _t('Login')));
            }

            if (empty($param['area_id'])) {
                throw new Exception("O campo região é obrigatório.");
            }
            
             if( empty($data->coletor_id) )
            {
                if (SystemUser::newFromLogin($object->login) instanceof SystemUser)
                {
                    throw new Exception(_t('An user with this login is already registered'));
                }
                
                // if (SystemUser::newFromEmail($object->email) instanceof SystemUser)
                // {
                //     throw new Exception(_t('An user with this e-mail is already registered'));
                // }
                
                if ( empty($object->password) )
                {
                    throw new Exception(TAdiantiCoreTranslator::translate('The field ^1 is required', _t('Password')));
                }
                
                $object->active = 'Y';
            }

             if( $object->password )
            {
                if( $object->password !== $param['repassword'] )
                    throw new Exception(_t('The passwords do not match'));
                
                $object->password = md5($object->password);

                if ($object->id)
                {
                    SystemUserOldPassword::validate($object->id, $object->password);
                }
            }
            else
            {
                unset($object->password);
            }
            
            $object->store();

            $userGerente = $object->getUserGerenteForUser();
            if( $userGerente )
            {
                $userGerente->fromArray((array) $data);
                $userGerente->store();
            }
            else
            {
                $userGerente = new Gerente;
                $userGerente->fromArray((array) $data);
                $userGerente->usuario_id = $object->id;
                $userGerente->store();
            }

            if ($object->password)
            {
                SystemUserOldPassword::register($object->id, $object->password);
            }
            $object->clearParts();

            $object->addSystemUserGroup( new SystemGroup(SystemGroup::GERENTE_GROUP_ID));

            $data = new stdClass;
            $data->id = $object->id;
            TForm::sendData('form_ferente', $data);

            TTransaction::close();
            $pos_action = new TAction(['GerenteList', 'onReload']);
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'), $pos_action);
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
                $object = new Gerente($key);
                $object->login = $object->get_usuario()->login;
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
