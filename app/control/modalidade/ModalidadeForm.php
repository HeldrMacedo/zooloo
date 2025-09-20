<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckBox;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TQuickForm;
use Adianti\Wrapper\BootstrapFormBuilder;

class ModalidadeForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_modalidade');
        $this->form->setFormTitle('Modalidade');
        $this->form->enableClientValidation();

        // Campos do formulário
        $id = new TEntry('modalidade_id');
        $criteriaJogo = new TCriteria;
        $criteriaJogo->add(new TFilter('ativo', '=', 'S'));
        $criteriaJogo->add(new TFilter('filtro_banca', '=', 1));
        $criteriaJogo->add(new TFilter('jogo_id', 'NOT IN', "(SELECT jogo_id FROM cad_modalidade)"));
        $jogo = new TDBCombo('jogo_id', 'permission', 'IntJogo', 'jogo_id', 'descricao', 'descricao', $criteriaJogo);
        $jogo->setId('jogo_id');
        $jogoLabel = new TEntry('jogo_descricao');
        $jogoLabel->setEditable(false);
        $jogoLabel->setId('jogo_descricao');

        $apresentacao = new TEntry('apresentacao');
        $multiplicador = new TNumeric('multiplicador', 2, ',', '.', true);
        $limite_descarga = new TNumeric('limite_descarga', 2, ',', '.', true);
        $limite_palpite = new TNumeric('limite_palpite', 2, ',', '.', true);
        $valor_palpite = new TNumeric('multiplicadorColocacao01', 2, ',', '.', true);
        $ativo = new TCombo('ativo');
        $ativo->addItems(['S' => 'Sim', 'N' => 'Não']);
        $ativo->setValue('S');

        $jogo->addValidation('Jogo', new TRequiredValidator);
        $apresentacao->addValidation('Apresentação', new TRequiredValidator);
        $multiplicador->addValidation('Multiplicador', new TRequiredValidator);

        $id->setEditable(false);
        $id->setSize('50%');
        $jogo->setSize('100%');
        $apresentacao->setSize('100%');
        $multiplicador->setSize('100%');
        $limite_descarga->setSize('100%');
        $limite_palpite->setSize('100%');
        $valor_palpite->setSize('100%');

        $jogo->setChangeAction(new TAction([$this, 'onChangeJogo']));
        $multiplicador->setId('multiplicador');
        $limite_descarga->setId('limite_descarga');
        $limite_palpite->setId('limite_palpite');
        $valor_palpite->setId('valor_palpite');

        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Modalidade <span class="text-danger">*</span>')], [$jogo], [$jogoLabel]);
        $this->form->addFields([new TLabel('Apresentação <span class="text-danger">*</span>')], [$apresentacao]);
        $this->form->addFields([new TLabel('Multiplicador <span class="text-danger">*</span>')], [$multiplicador]);
        $this->form->addFields([new TLabel('Valor Palpite')], [$valor_palpite]);

        $this->form->addFields([new TLabel('Limite Descarga')], [$limite_descarga]);
        $this->form->addFields([new TLabel('Limite Palpite')], [$limite_palpite]);

        $this->form->addFields([new TLabel('Ativo')], [$ativo]);

        self::onChangeJogo();

        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $alertContainer = new TElement('div');
        $alertContainer->id = 'alert_container';
        $alertContainer->style = 'margin-bottom: 20px;';
        $this->form->addContent([$alertContainer]);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);

        $globalJs = <<<JS
            function atualizarAlerta(orientacao) {
            const container = document.getElementById('alert_container');
            container.replaceChildren();
                if (orientacao) {
            const alerta = document.createElement('div');
            alerta.className = 'alert alert-warning';
            alerta.role = 'alert';
            alerta.textContent = orientacao;
            container.appendChild(alerta);
                }
        }
        JS;
        TScript::create($globalJs);
    }

    public static function onChangeJogo($param = null)
    {

        if (!empty($param)) {
            if (
                $param['jogo_id'] == Modalidade::MILHAR_MOTO_01 ||
                $param['jogo_id'] == Modalidade::MILHAR_MOTO_02 ||
                $param['jogo_id'] == Modalidade::MILHAR_MOTO_03
            ) {
                TQuickForm::showField('form_modalidade', 'multiplicadorColocacao01');
            }
            TTransaction::open('permission');
            $jogo = IntJogo::find($param['jogo_id']);
            TTransaction::close();

            if ($jogo->informar_valores_modalidade == 'N') {
                TField::disableField('form_modalidade', 'multiplicador');
                TField::disableField('form_modalidade', 'limite_descarga');
                TField::disableField('form_modalidade', 'limite_palpite');

                TScript::create("atualizarAlerta(" . json_encode($jogo->orientacao) . ");");
            } else {
                TField::enableField('form_modalidade', 'multiplicador');
                TField::enableField('form_modalidade', 'limite_descarga');
                TField::enableField('form_modalidade', 'limite_palpite');

                TScript::create("atualizarAlerta(null);");
            }
        } else {
            TQuickForm::hideField('form_modalidade', 'multiplicadorColocacao01');
        }
    }

    public function onSave($param = null)
    {
        try {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->validate();
            
            $isNew = empty($data->modalidade_id);

            if ($isNew) {
                $modalidade = new Modalidade;
                $modalidade->fromArray((array) $data);
                $modalidade->ativo = $data->ativo == 'S' ? 'S' : 'N';
                $modalidades_existentes = Modalidade::all();

                if (count($modalidades_existentes) > 0) {
                    $maxOrdem = max(array_column($modalidades_existentes, 'ordem'));
                    $modalidade->ordem = ($maxOrdem ? $maxOrdem : 0) + 1;
                } else {
                    $modalidade->ordem = 1;
                }

                $modalidade->store();
            } else {
                $modalidade_atual = Modalidade::find($data->modalidade_id);

                 // Update the current modalidade with new data
                    $modalidade_atual->fromArray((array) $data);
                    $modalidade_atual->ativo = $data->ativo == 'S' ? 'S' : 'N';

                    // echo '<pre>';
                    // var_dump($modalidade_atual);
                    // echo '<br />';
                    // var_dump($data);
                    // echo '</pre>';
                    // exit;
                    $modalidade_atual->store();

                    $modalidade = $modalidade_atual;
            }

             $data = new stdClass;
                $data->modalidade_id = $modalidade->modalidade_id;
                TForm::sendData('form_modalidade', $data);
                TTransaction::close();

                $pos_action = new TAction(['ModalidadeList', 'onReload']);
                new TMessage('info', 'Modalidade salva com sucesso!', $pos_action);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('permission');
            if (isset($param['key'])) {
                $key = $param['key'];
                $object = new Modalidade($key);

                // Converte S/N para checkbox
                $data = $object->toArray();
                $data['jogo_descricao'] = $object->jogo->descricao;

                
                //$data['ativo'] = ($object->ativo == 'S') ? true : false;

                $this->form->setData((object) $data);
                TQuickForm::hideField('form_modalidade', 'jogo_id');
                TQuickForm::showField('form_modalidade', 'jogo_descricao');
            } else {
                
                $this->form->clear();
                TQuickForm::showField('form_modalidade', 'jogo_id');
                TQuickForm::hideField('form_modalidade', 'jogo_descricao');
            }
            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param = null)
    {
        try {
            if (isset($param['key'])) {
                $key = $param['key'];
                $this->onReload(['key' => $key]);
            } else {
                $this->form->clear();
                // Valores padrão para novo registro
                $obj = new stdClass;
                $obj->ativo = 'S';
                $obj->ordem = 1; // Valor padrão para ordem
                $this->form->setData($obj);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param = null)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
