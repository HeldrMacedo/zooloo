<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Validator\TNumericValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Form\TNumeric;
use Adianti\Wrapper\BootstrapFormBuilder;

class ParametrosForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_parametros');
        $this->form->setFormTitle('Parâmetros do Sistema');
        $this->form->enableClientValidation();

        // Campos básicos
        $id = new TEntry('parametros_id');
        $nome_banca = new TEntry('nome_banca');
        $cnpj = new TEntry('cnpj');
        $telefone = new TEntry('telefone');
        $cidade = new TEntry('cidade');
        $estado = new TEntry('estado');
        $site = new TEntry('site');
        $email = new TEntry('email');

        // Mensagens
        $mensagem_01 = new TText('mensagem_01');
        $mensagem_02 = new TText('mensagem_02');
        $mensagem_03 = new TText('mensagem_03');
        $mensagem_04 = new TText('mensagem_04');
        $mensagem_05 = new TText('mensagem_05');

        // Campos numéricos
        $valor_milhar_brinde = new TNumeric('valor_milhar_brinde', 2, ',', '.');
        $qtde_num_mi = new TEntry('qtde_num_mi');
        $qtde_num_ci = new TEntry('qtde_num_ci');
        $qtde_num_mci = new TEntry('qtde_num_mci');
        $valor_milharpremiada = new TNumeric('valor_milharpremiada', 2, ',', '.');

        // Campos boolean
        $ativo_modalidade = new TCombo('ativo_modalidade');
        $ativo_modalidade->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_bilhetinho = new TCombo('ativo_bilhetinho');
        $ativo_bilhetinho->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_instantaneo = new TCombo('ativo_instantaneo');
        $ativo_instantaneo->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_quininha = new TCombo('ativo_quininha');
        $ativo_quininha->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_seninha = new TCombo('ativo_seninha');
        $ativo_seninha->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_lotinha = new TCombo('ativo_lotinha');
        $ativo_lotinha->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_jb = new TCombo('ativo_jb');
        $ativo_jb->addItems(['S' => 'Sim', 'N' => 'Não']);

        $sena_inc_quina = new TCombo('sena_inc_quina');
        $sena_inc_quina->addItems(['S' => 'Sim', 'N' => 'Não']);

        $sena_inc_quadra = new TCombo('sena_inc_quadra');
        $sena_inc_quadra->addItems(['S' => 'Sim', 'N' => 'Não']);

        $quina_inc_quadra = new TCombo('quina_inc_quadra');
        $quina_inc_quadra->addItems(['S' => 'Sim', 'N' => 'Não']);

        $quina_inc_terno = new TCombo('quina_inc_terno');
        $quina_inc_terno->addItems(['S' => 'Sim', 'N' => 'Não']);

        $limite_extracao = new TCombo('limite_extracao');
        $limite_extracao->addItems(['S' => 'Sim', 'N' => 'Não']);

        $ativo_milharpremiada = new TCombo('ativo_milharpremiada');
        $ativo_milharpremiada->addItems(['S' => 'Sim', 'N' => 'Não']);

        // Validações
        $nome_banca->addValidation('Nome da Banca', new TRequiredValidator);
        $qtde_num_mi->addValidation('Qtd Milhar Invertida', new TNumericValidator);
        $qtde_num_ci->addValidation('Qtd Centena Invertida', new TNumericValidator);
        $qtde_num_mci->addValidation('Qtd Milhar Centena Invertida', new TNumericValidator);

        // Tamanhos
        $id->setSize('50%');
        $id->setEditable(false);
        $nome_banca->setSize('100%');
        $cnpj->setSize('100%');
        $telefone->setSize('100%');
        $cidade->setSize('100%');
        $estado->setSize('100%');
        $site->setSize('100%');
        $email->setSize('100%');
        $mensagem_01->setSize('100%', 60);
        $mensagem_02->setSize('100%', 60);
        $mensagem_03->setSize('100%', 60);
        $mensagem_04->setSize('100%', 60);
        $mensagem_05->setSize('100%', 60);
        $valor_milhar_brinde->setSize('100%');
        $qtde_num_mi->setSize('100%');
        $qtde_num_ci->setSize('100%');
        $qtde_num_mci->setSize('100%');
        $valor_milharpremiada->setSize('100%');

        // Layout dos campos
        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Nome da Banca *')], [$nome_banca], [new TLabel('CNPJ')], [$cnpj]);
        $this->form->addFields([new TLabel('Telefone')], [$telefone], [new TLabel('Cidade')], [$cidade]);
        $this->form->addFields([new TLabel('Estado')], [$estado], [new TLabel('Site')], [$site]);
        $this->form->addFields([new TLabel('Email')], [$email]);

        $this->form->addFields([new TLabel('Mensagem 1')], [$mensagem_01]);
        $this->form->addFields([new TLabel('Mensagem 2')], [$mensagem_02]);
        $this->form->addFields([new TLabel('Mensagem 3')], [$mensagem_03]);
        $this->form->addFields([new TLabel('Mensagem 4')], [$mensagem_04]);
        $this->form->addFields([new TLabel('Mensagem 5')], [$mensagem_05]);

        $this->form->addFields([new TLabel('Milhar Brinde a partir de:')], [$valor_milhar_brinde]);
        $this->form->addFields([new TLabel('Qtd Milhar Invertida')], [$qtde_num_mi], [new TLabel('Qtd Centena Invertida')], [$qtde_num_ci]);
        $this->form->addFields([new TLabel('Qtd Milhar Centena Invertida')], [$qtde_num_mci]);
        
        $this->form->addFields([new TLabel('Ativo Modalidade')], [$ativo_modalidade], [new TLabel('Ativo Bilhetinho')], [$ativo_bilhetinho]);
        $this->form->addFields([new TLabel('Ativo Instantâneo')], [$ativo_instantaneo], [new TLabel('Ativo Quininha')], [$ativo_quininha]);
        $this->form->addFields([new TLabel('Ativo Seninha')], [$ativo_seninha], [new TLabel('Ativo Lotinha')], [$ativo_lotinha]);
        $this->form->addFields([new TLabel('Ativo JB')], [$ativo_jb], [new TLabel('Limite Extração')], [$limite_extracao]);

        $this->form->addFields([new TLabel('Sena Inc. Quina')], [$sena_inc_quina], [new TLabel('Sena Inc. Quadra')], [$sena_inc_quadra]);
        $this->form->addFields([new TLabel('Quina Inc. Quadra')], [$quina_inc_quadra], [new TLabel('Quina Inc. Terno')], [$quina_inc_terno]);

        $this->form->addFields([new TLabel('Ativo Milhar Premiada')], [$ativo_milharpremiada], [new TLabel('Valor Milhar Premiada')], [$valor_milharpremiada]);

        // Botões
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');
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

            $this->form->validate();
            $data = $this->form->getData();

            // Validações específicas
            if ($data->qtde_num_mi < 4 || $data->qtde_num_mi > 10) {
                throw new Exception('Campo milhar não pode ser menor que 4 e nem maior que 10!');
            }

            if ($data->qtde_num_ci < 3 || $data->qtde_num_ci > 10) {
                throw new Exception('Campo centena não pode ser menor que 3 e nem maior que 10!');
            }

            if ($data->qtde_num_mci < 4 || $data->qtde_num_mci > 10) {
                throw new Exception('Campo milhar centena não pode ser menor que 4 e nem maior que 10!');
            }

            // Verifica se já existe um registro
            $existing = Parametros::where('parametros_id', '>', 0)->first();
            
            if ($existing && empty($data->parametros_id)) {
                throw new Exception('Só é permitido um registro de parâmetros. Edite o registro existente.');
            }

            $object = new Parametros;
            $object->fromArray((array) $data);
            $object->store();

            $data = new stdClass;
            $data->parametros_id = $object->parametros_id;
            TForm::sendData('form_parametros', $data);

            TTransaction::close();
            $pos_action = new TAction(['ParametrosList', 'onReload']);
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
                $object = new Parametros($key);
                $this->form->setData($object);
                TTransaction::close();
            }
            else
            {
                $this->form->clear();
                
                // Se não há parâmetro key, verifica se existe um registro e carrega
                TTransaction::open('permission');
                $existing = Parametros::where('parametros_id', '>', 0)->first();
                if ($existing) {
                    $this->form->setData($existing);
                }
                TTransaction::close();
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