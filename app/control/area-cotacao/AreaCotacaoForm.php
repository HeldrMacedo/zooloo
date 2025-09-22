<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Validator\TNumericValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TQuickForm;
use Adianti\Wrapper\BootstrapFormBuilder;

class AreaCotacaoForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_area_cotacao');
        $this->form->setFormTitle('Área Cotação');
        $this->form->enableClientValidation();

        // Campos do formulário
        $area_cotacao_id = new TEntry('area_cotacao_id');
        $area_cotacao_id->setEditable(FALSE);

        // Filtrar apenas áreas ativas
        $criteriaArea = new TCriteria;
        $criteriaArea->add(new TFilter('ativo', '=', 'S'));

        $area_id = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao', 'descricao', $criteriaArea);
        $area_id->enableSearch();

        // Filtrar apenas extrações ativas
        $criteriaExtracao = new TCriteria;
        $criteriaExtracao->add(new TFilter('ativo', '=', 'S'));

        $extracao_id = new TDBCombo('extracao_id', 'permission', 'Extracao', 'extracao_id', 'descricao', 'descricao', $criteriaExtracao);
        $extracao_id->enableSearch();

        // Filtrar apenas modalidades ativas
        $criteriaModalidade = new TCriteria;
        $criteriaModalidade->add(new TFilter('ativo', '=', 'S'));

        $modalidade_id = new TDBCombo('modalidade_id', 'permission', 'Modalidade', 'modalidade_id', '{jogo_id} - {apresentacao}', 'apresentacao', $criteriaModalidade);
        $modalidade_id->enableSearch();

        $multiplicador = new TNumeric('multiplicador',  2,',','.', true);

        // Validações obrigatórias
        $area_id->addValidation('Área', new TRequiredValidator);
        $modalidade_id->addValidation('Modalidade', new TRequiredValidator);
        $multiplicador->addValidation('Multiplicador', new TRequiredValidator);
        $multiplicador->addValidation('Multiplicador', new TNumericValidator);

        // Tamanhos
        $area_cotacao_id->setSize('50%');
        $area_id->setSize('100%');
        $extracao_id->setSize('100%');
        $modalidade_id->setSize('100%');
        $multiplicador->setSize('100%');

        // Botões de ação
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');

        // Layout do formulário
        $this->form->addFields([new TLabel('ID')], [$area_cotacao_id]);
        
        // Seção: Configuração
        $this->form->addContent(['<h5>Configuração de Cotação</h5><hr>']);
        $this->form->addFields([new TLabel('Área *')], [$area_id]);
        $this->form->addFields([new TLabel('Extração')], [$extracao_id]);
        $this->form->addFields([new TLabel('Modalidade *')], [$modalidade_id]);
        $this->form->addFields([new TLabel('Multiplicador *')], [$multiplicador]);

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    /**
     * Salvar registro
     */
    public function onSave($param)
    {
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData();
            $this->form->setData($data);

            // Validações customizadas
            if (empty($data->area_id))
            {
                throw new Exception('O campo Área é obrigatório.');
            }

            if (empty($data->modalidade_id))
            {
                throw new Exception('O campo Modalidade é obrigatório.');
            }

            if (empty($data->multiplicador))
            {
                throw new Exception('O campo Multiplicador é obrigatório.');
            }

            // Verificar se já existe uma configuração igual
            $criteria = new TCriteria;
            $criteria->add(new TFilter('area_id', '=', $data->area_id));
            $criteria->add(new TFilter('modalidade_id', '=', $data->modalidade_id));
            
            if ($data->extracao_id) {
                $criteria->add(new TFilter('extracao_id', '=', $data->extracao_id));
            } else {
                $criteria->add(new TFilter('extracao_id', 'IS', NULL));
            }

            if (!empty($data->area_cotacao_id)) {
                $criteria->add(new TFilter('area_cotacao_id', '!=', $data->area_cotacao_id));
            }

            $existing = AreaCotacao::getObjects($criteria);
            if ($existing) {
                throw new Exception('Já existe uma configuração para esta Área, Extração e Modalidade');
            }

            $object = new AreaCotacao;
            if (!empty($data->area_cotacao_id))
            {
                $object = new AreaCotacao($data->area_cotacao_id);
            }

            $object->fromArray((array) $data);
            $object->store();

            $data = new stdClass;
            $data->area_cotacao_id = $object->area_cotacao_id;
            TForm::sendData('form_area_cotacao', $data);

            TTransaction::close();
            $pos_action = new TAction(['AreaCotacaoList', 'onReload']);
            new TMessage('info', 'Registro salvo com sucesso', $pos_action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Carregar registro para edição
     */
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                TTransaction::open('permission');
                $object = new AreaCotacao($key);
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

    public function onReload($param)
    {
        // Método para recarregar dados se necessário
    }

    /**
     * Fechar cortina
     */
    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}