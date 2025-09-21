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
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Form\TTime;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TQuickForm;
use Adianti\Wrapper\BootstrapFormBuilder;

class VendedorForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_vendedor');
        $this->form->setFormTitle('Vendedor');
        $this->form->enableClientValidation();

        // Campos básicos
        $id                 = new TEntry('vendedor_id');
        $nome               = new TEntry('nome');
        $login              = new TEntry('login');
        $senha              = new TPassword('password');
        $confirmar_senha    = new TPassword('repassword');

         // Endereço
        $cep = new TEntry('cep');
        $rua = new TEntry('rua');
        $numero = new TEntry('numero');
        $bairro = new TEntry('bairro');
        $cidade = new TEntry('cidade');
        $uf = new TCombo('uf');

         // Relacionamentos
        $area_id = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao', 'descricao');
        $coletor_id = new TDBCombo('coletor_id', 'permission', 'Gerente', 'coletor_id', 'nome', 'nome');
    
        // Configurações financeiras
        $comissao = new TNumeric('comissao', 2, ',', '.', true);
        $limite_venda = new TNumeric('limite_venda', 2, ',', '.', true);

         // Permissões
        $pode_cancelar = new TCombo('pode_cancelar');
        $pode_cancelar_tempo = new TTime('pode_cancelar_tempo');
        $pode_cancelar_qtde = new TEntry('pode_cancelar_qtde');
        $pode_pagar = new TCombo('pode_pagar');
        $pode_pagar_outro = new TCombo('pode_pagar_outro');
        $pode_reimprimir = new TCombo('pode_reimprimir');
        $pode_reimprimir_qtde = new TEntry('pode_reimprimir_qtde');
        $pode_reimprimir_tempo = new TTime('pode_reimprimir_tempo');

        
        // Configurações de exibição
        $exibe_comissao = new TCombo('exibe_comissao');
        $exibe_premiacao = new TCombo('exibe_premiacao');
        //$tipo_limite = new TCombo('tipo_limite');
        //$treinamento = new TCombo('treinamento');
        $ativo = new TCombo('ativo');

        // Observações
        $observacao = new TText('observacao');

        // Configuração dos combos
        $uf->addItems([
            'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM', 'BA' => 'BA',
            'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO', 'MA' => 'MA',
            'MT' => 'MT', 'MS' => 'MS', 'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB',
            'PR' => 'PR', 'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
            'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC', 'SP' => 'SP',
            'SE' => 'SE', 'TO' => 'TO'
        ]);

        $pode_cancelar->addItems(['S' => 'Sim', 'N' => 'Não']);
        $pode_pagar->addItems(['S' => 'Sim', 'N' => 'Não']);
        $pode_pagar_outro->addItems(['S' => 'Sim', 'N' => 'Não']);
        $pode_reimprimir->addItems(['S' => 'Sim', 'N' => 'Não']);
        $exibe_comissao->addItems(['S' => 'Sim', 'N' => 'Não']);
        $exibe_premiacao->addItems(['S' => 'Sim', 'N' => 'Não', 'U' => 'Último']);
        //$tipo_limite->addItems(['D' => 'Diário', 'A' => 'Acumulado']);
        //$treinamento->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);

        // Valores padrão
        $pode_cancelar->setValue('N');
        $pode_pagar->setValue('S');
        $pode_pagar_outro->setValue('N');
        $pode_reimprimir->setValue('N');
        $exibe_comissao->setValue('S');
        $exibe_premiacao->setValue('S');
        //$tipo_limite->setValue('D');
        //$treinamento->setValue('N');
        $ativo->setValue('S');

        // Validações
        $nome->addValidation('Nome', new TRequiredValidator);
        $login->addValidation('Login', new TRequiredValidator);
        $area_id->addValidation('Área', new TRequiredValidator);
        $coletor_id->addValidation('Setorista', new TRequiredValidator);
        $comissao->addValidation('Comissão', new TRequiredValidator);
        $limite_venda->addValidation('Limite de Venda', new TRequiredValidator);
        $exibe_premiacao->addValidation('Exibe Premiação', new TRequiredValidator);

        // Tamanhos
        $id->setEditable(false);
        $id->setSize('50%');
        $nome->setSize('100%');
        $login->setSize('100%');
        $senha->setSize('100%');
        $confirmar_senha->setSize('100%');
        $cep->setSize('100%');
        $rua->setSize('100%');
        $numero->setSize('100%');
        $bairro->setSize('100%');
        $cidade->setSize('100%');
        $uf->setSize('100%');
        $area_id->setSize('100%');
        $coletor_id->setSize('100%');
        $comissao->setSize('100%');
        $limite_venda->setSize('100%');
        $observacao->setSize('100%', 60);

        // Ações de mudança
        $area_id->setChangeAction(new TAction([$this, 'onChangeArea']));

        // Botões
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        // Layout do formulário
        $this->form->addFields([new TLabel('ID')], [$id]);
        
        // Seção: Dados Pessoais
        $this->form->addContent(['<h5>Dados Pessoais</h5><hr>']);
        $this->form->addFields([new TLabel('Nome')], [$nome]);
        
        // Seção: Endereço
        $this->form->addContent(['<h5>Endereço</h5><hr>']);
        $this->form->addFields([new TLabel('CEP')], [$cep], [new TLabel('Rua')], [$rua]);
        $this->form->addFields([new TLabel('Número')], [$numero], [new TLabel('Bairro')], [$bairro]);
        $this->form->addFields([new TLabel('Cidade')], [$cidade], [new TLabel('UF')], [$uf]);

        // Seção: Hierarquia
        $this->form->addContent(['<h5>Hierarquia</h5><hr>']);
        $this->form->addFields([new TLabel('Área')], [$area_id], [new TLabel('Setorista')], [$coletor_id]);

        // Seção: Configurações Financeiras
        $this->form->addContent(['<h5>Configurações Financeiras</h5><hr>']);
        $this->form->addFields([new TLabel('Comissão (%)')], [$comissao], [new TLabel('Limite de Venda')], [$limite_venda]);
        $this->form->addFields([new TLabel('Exibe Premiação')], [$exibe_premiacao], [new TLabel('Exibe Comissão')], [$exibe_comissao]);

        // Seção: Permissões
        $this->form->addContent(['<h5>Permissões</h5><hr>']);
        $this->form->addFields([new TLabel('Pode Cancelar')], [$pode_cancelar], [new TLabel('Tempo Cancelar')], [$pode_cancelar_tempo]);
        $this->form->addFields([new TLabel('Qtde Cancelar')], [$pode_cancelar_qtde], [new TLabel('Pode Pagar')], [$pode_pagar]);
        $this->form->addFields([new TLabel('Pode Pagar Outro')], [$pode_pagar_outro], [new TLabel('Pode Reimprimir')], [$pode_reimprimir]);
        $this->form->addFields([new TLabel('Qtde Reimprimir')], [$pode_reimprimir_qtde], [new TLabel('Tempo Reimprimir')], [$pode_reimprimir_tempo]);

        // Seção: Acesso ao Sistema
        $this->form->addContent(['<h5>Acesso ao Sistema</h5><hr>']);
        $this->form->addFields([new TLabel('Login')], [$login]);
        $this->form->addFields([new TLabel('Senha')], [$senha], [new TLabel('Confirmar Senha')], [$confirmar_senha]);
        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        TQuickForm::disableField('form_vendedor', 'coletor_id');
        parent::add($container);

    }

     public static function onChangeArea($param)
    {
        try
        {
            if (!empty($param['area_id']))
            {
                TTransaction::open('permission');
                
                // Filtrar setoristas pela área selecionada
                $criteria = new TCriteria;
                $criteria->add(new TFilter('area_id', '=', $param['area_id']));
                $criteria->add(new TFilter('ativo', '=', 'S'));
                
                $setoristas = new TRepository('Gerente');
                $objects = $setoristas->load($criteria, FALSE);
                
                $options = array();
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $options[$object->coletor_id] = $object->nome;
                    }
                }
                
                TCombo::reload('form_vendedor', 'coletor_id', $options);
                TQuickForm::enableField('form_vendedor', 'coletor_id');
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onSave($param)
    {
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);

            // Validações customizadas
            if (empty($data->nome))
            {
                throw new Exception('O campo Nome é obrigatório.');
            }

            if (empty($data->login))
            {
                throw new Exception('O campo Login é obrigatório.');
            }

            if (empty($data->area_id))
            {
                throw new Exception('O campo Área é obrigatório.');
            }

            if (empty($data->coletor_id))
            {
                throw new Exception('O campo Setorista é obrigatório.');
            }

            // Validação de senha
            if (empty($data->vendedor_id))
            {
                if (empty($data->password))
                {
                    throw new Exception('O campo Senha é obrigatório para novos vendedores.');
                }
            }

            if (!empty($data->password))
            {
                if ($data->password !== $param['repassword'])
                {
                    throw new Exception('As senhas não conferem.');
                }
            }

            // Criar/atualizar usuário do sistema
            $user = new SystemUser;
            if (!empty($data->vendedor_id))
            {
                $vendedor_existente = new Vendedor($data->vendedor_id);
                if ($vendedor_existente->usuario_id)
                {
                    $user = new SystemUser($vendedor_existente->usuario_id);
                }
            }
            else
            {
                // Verificar se login já existe
                if (SystemUser::newFromLogin($data->login) instanceof SystemUser)
                {
                    throw new Exception('Já existe um usuário com este login.');
                }
            }

            $user->name = $data->nome;
            $user->login = $data->login;
            $user->email = $data->login . '@zooloo.com';
            $user->frontpage_id = SystemUser::FRONTPAGE_ID;
            $user->function_name = SystemUser::FUNCTION_VENDEDOR;
            $user->active = $data->ativo == 'S' ? 'Y' : 'N';

            if (!empty($data->password))
            {
                $user->password = md5($data->password);
            }

            $user->store();

            // Criar/atualizar vendedor
            $object = new Vendedor;
            if (!empty($data->vendedor_id))
            {
                $object = new Vendedor($data->vendedor_id);
            }

            $object->fromArray((array) $data);
            $object->usuario_id = $user->id;
            //$object->treinamento = $data->treinamento == 'S' ? 'S' : 'N';

            // Converter valores padrão
            $object->pode_cancelar_tempo = $data->pode_cancelar_tempo ?: '00:00:00';
            $object->pode_cancelar_qtde = $data->pode_cancelar_qtde ?: 0;
            $object->pode_reimprimir_qtde = $data->pode_reimprimir_qtde ?: 0;
            $object->pode_reimprimir_tempo = $data->pode_reimprimir_tempo ?: '00:00:00';

            $object->store();

            // Adicionar grupo de vendedor ao usuário
            $user->clearParts();
            $user->addSystemUserGroup(new SystemGroup(SystemGroup::VENDEDOR_GROUP_ID));

            $data = new stdClass;
            $data->vendedor_id = $object->vendedor_id;
            TForm::sendData('form_vendedor', $data);

            TTransaction::close();
            $pos_action = new TAction(['VendedorList', 'onReload']);
            new TMessage('info', 'Vendedor salvo com sucesso', $pos_action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onReload($param)
    {
        // Método para recarregar dados se necessário
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                TTransaction::open('permission');
                $object = new Vendedor($key);
                
                // Carregar dados do usuário
                if ($object->usuario_id)
                {
                    $user = new SystemUser($object->usuario_id);
                    $object->login = $user->login;
                }

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
