<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Widget\Base\TScript;

class AreaExtracaoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $loaded;
    protected $filter_criteria;

    public function __construct()
    {
        parent::__construct();

        // Criar formulário de filtro
        $this->form = new BootstrapFormBuilder('form_area_extracao');
        $this->form->setFormTitle('Configuração Área x Extração');

        // Filtrar apenas áreas ativas
        $criteriaArea = new TCriteria;
        $criteriaArea->add(new TFilter('ativo', '=', 'S'));

        // Campo de seleção de área
        $area_id = new TDBCombo('area_id', 'permission', 'Area', 'area_id', 'descricao', 'descricao', $criteriaArea);
        $area_id->enableSearch();



        $this->form->addFields([new TLabel('Área')], [$area_id]);
        $area_id->setSize('100%');

        // Ação de busca - usando método não estático
        $area_id->setChangeAction(new TAction([$this, 'onChangeArea']));

        // Botão de buscar
        $btn_search = $this->form->addAction('Buscar', new TAction([$this, 'onLoadAreaExtracoes']), 'fa:search');
        $btn_search->class = 'btn btn-sm btn-primary';

        // Criar datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(400);

        // Colunas do datagrid
        $column_extracao = new TDataGridColumn('extracao', 'Extração', 'left', '70%');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo', 'center', '30%');

        // Transformador para coluna ativo - botões de ativar/desativar
        $column_ativo->setTransformer(function ($value, $object, $row) {
            $area_id = TSession::getValue('area_extracao_area_id');
            // Garantir que $value seja string
            $value = is_bool($value) ? ($value ? 'S' : 'N') : $value;

            if ($value == 'S') {
                $btn = new TElement('button');
                $btn->class = 'btn btn-success btn-sm';
                $btn->add('Ativado');
                $btn->onclick = "__adianti_ajax_exec('class=AreaExtracaoList&method=onToggleStatus&area_id={$area_id}&extracao_id={$object->extracao_id}&ativo=N')";
                return $btn;
            } else {
                $btn = new TElement('button');
                $btn->class = 'btn btn-danger btn-sm';
                $btn->add('Desativado');
                $btn->onclick = "__adianti_ajax_exec('class=AreaExtracaoList&method=onToggleStatus&area_id={$area_id}&extracao_id={$object->extracao_id}&ativo=S')";
                return $btn;
            }
        });

        $this->datagrid->addColumn($column_extracao);
        $this->datagrid->addColumn($column_ativo);

        $this->datagrid->createModel();

        // Painel principal
        $panel = new TPanelGroup();
        $panel->add($this->datagrid)->style = 'overflow-x:auto';

        // Container principal
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);
    }

    /**
     * Método chamado quando a área é alterada no combo
     */
    public static function onChangeArea($param)
    {
        if (!empty($param['area_id'])) {
            TScript::create("__adianti_ajax_exec('class=AreaExtracaoList&method=onLoadAreaExtracoes&area_id={$param['area_id']}');");
        }
    }

    /**
     * Carrega as extrações para a área selecionada
     */
    public function onLoadAreaExtracoes($param = null)
    {
        try {
            // Pegar área do parâmetro ou do formulário
            $area_id = $param['area_id'] ?? null;

            if (empty($area_id)) {
                $data = $this->form->getData();
                $area_id = $data->area_id ?? null;
            }

            if (empty($area_id)) {
                $this->datagrid->clear();
                return;
            }

            TSession::setValue('area_extracao_area_id', $area_id);

            TTransaction::open('permission');

            // Buscar todas as extrações ativas e verificar quais estão configuradas para a área
            $sql = "SELECT 
                        ae.area_extracao_id,
                        ae.area_id,
                        e.extracao_id,
                        e.descricao as extracao,
                        CASE 
                            WHEN ae.ativo IS true THEN ae.ativo
                            ELSE false 
                        END as ativo
                    FROM cad_extracao e
                    LEFT JOIN cfg_area_extracao ae ON ae.extracao_id = e.extracao_id AND ae.area_id = :area_id
                    WHERE e.ativo = 'S'
                    ORDER BY e.descricao";

            $conn = TTransaction::get();
            $result = $conn->prepare($sql);
            $result->bindValue(':area_id', $area_id);
            $result->execute();

            $rows = $result->fetchAll(PDO::FETCH_ASSOC);


            $this->datagrid->clear();

            if ($rows) {
                foreach ($rows as $row) {
                    $item = new stdClass;
                    $item->extracao_id = $row['extracao_id'];
                    $item->extracao = $row['extracao'];
                    $item->ativo = $row['ativo'] ? 'S' : 'N';
                    $item->area_extracao_id = $row['area_extracao_id'];
                    $this->datagrid->addItem($item);
                }
            }


            TTransaction::close();

            // Manter dados do formulário
            $data = new stdClass;
            $data->area_id = $area_id;
            $this->form->setData($data);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Alterna o status ativo/inativo de uma extração para uma área
     */
    public static function onToggleStatus($param)
    {
        try {
            TTransaction::open('permission');

            $area_id = $param['area_id'];
            $extracao_id = $param['extracao_id'];
            $novo_status = $param['ativo'];

            // Verificar se já existe registro
            $criteria = new TCriteria;
            $criteria->add(new TFilter('area_id', '=', $area_id));
            $criteria->add(new TFilter('extracao_id', '=', $extracao_id));

            $area_extracao = AreaExtracao::getObjects($criteria);

            if ($novo_status == 'S') {
                // Ativar: criar ou atualizar registro
                if (empty($area_extracao)) {
                    // Criar novo registro
                    $new_area_extracao = new AreaExtracao;
                    $new_area_extracao->area_id = $area_id;
                    $new_area_extracao->extracao_id = $extracao_id;
                    $new_area_extracao->ativo = true;
                    $new_area_extracao->store();
                } else {
                    // Atualizar registro existente
                    $existing = $area_extracao[0];
                    $existing->ativo = true;
                    $existing->store();
                }

                new TMessage('info', 'Extração ativada para a área com sucesso');
            } else {
                // Desativar: remover registro ou marcar como inativo
                if (!empty($area_extracao)) {
                    $existing = $area_extracao[0];
                    $existing->delete(); // Remove o registro (baseado na lógica Java)
                }

                new TMessage('info', 'Extração desativada para a área com sucesso');
            }

            TTransaction::close();

            // Recarregar a lista
            AdiantiCoreApplication::loadPage('AreaExtracaoList', 'onLoadAreaExtracoes', ['area_id' => $area_id]);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Método para carregar a página
     */
    public function onReload($param = null)
    {
        // Manter área selecionada se existir
        if ($area_id = TSession::getValue('area_extracao_area_id')) {
            $this->onLoadAreaExtracoes(['area_id' => $area_id]);
        }
    }

    /**
     * Método chamado quando a página é mostrada
     */
    public function show()
    {
        // Verificar permissões de usuário baseado no código Angular
        $this->checkUserPermissions();

        parent::show();
    }

    /**
     * Verificar permissões do usuário (baseado na lógica Angular)
     */
    private function checkUserPermissions()
    {
        try {
            TTransaction::open('permission');

            $user = TSession::getValue('userid');
            $login = TSession::getValue('login');

            // Verificar se é setorista (coletor)
            $criteria = new TCriteria;
            $criteria->add(new TFilter('usuario_id', '=', $user));
            $gerente = Gerente::getObjects($criteria);

            if (!empty($gerente)) {
                // É setorista - filtrar apenas suas áreas
                $setorista = $gerente[0];

                // Buscar áreas do setorista
                $criteriaArea = new TCriteria;
                $criteriaArea->add(new TFilter('area_id', '=', $setorista->area_id));
                $criteriaArea->add(new TFilter('ativo', '=', 'S'));

                // Aplicar filtro no combo de área
                $area_combo = $this->form->getField('area_id');
                $area_combo->setCriteria($criteriaArea);

                // Auto-selecionar a área do setorista
                $data = new stdClass;
                $data->area_id = $setorista->area_id;
                $this->form->setData($data);

                // Carregar extrações automaticamente
                $this->onLoadAreaExtracoes(['area_id' => $setorista->area_id]);
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            // Em caso de erro, continuar normalmente
        }
    }
}
