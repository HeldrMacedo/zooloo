<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TTime;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Database\TRecord;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Wrapper\TQuickForm;

class ResultadoForm extends TPage
{
    protected $form;
    protected $sorteio_data;
    
    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');
        // Cria o formulário
        $this->form = new BootstrapFormBuilder('form_resultado');
        $this->form->setFormTitle('Resultado do Sorteio');
        
        //$this->form->enableClientSideValidation();
        
        // Campos ocultos
        $sorteio_id = new TEntry('sorteio_id');
        $sorteio_id->setEditable(false);
        $sorteio_id->style = 'display:none';
        
        // Campos de informação do sorteio
        $extracao_descricao = new TEntry('extracao_descricao');
        $data_sorteio = new TEntry('data_sorteio');
        $hora_sorteio = new TEntry('hora_sorteio');
        $situacao_display = new TEntry('situacao_display');
        
        $extracao_descricao->setEditable(false);
        $data_sorteio->setEditable(false);
        $hora_sorteio->setEditable(false);
        $situacao_display->setEditable(false);
        
        // Campos para os números sorteados individuais
        $premios = [];
        $grupos = [];
        $descricoes_grupos = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $premio = new TEntry("premio_{$i}");
            $premio->setMask('9999');
            $premio->setSize('80px');
            $premio->placeholder = "0000";
            $premio->onblur = "chkGrupoDescricao({$i}); updateNumerosString();";
            $premios[$i] = $premio;
            
            $grupo = new TEntry("grupo_{$i}");
            $grupo->setMask('99');
            $grupo->setSize('60px');
            $grupo->setEditable(false);
            $grupo->style = 'background-color: #f5f5f5';
            $grupos[$i] = $grupo;
            
            $descricao_grupo = new TEntry("descricao_grupo_{$i}");
            $descricao_grupo->setSize('120px');
            $descricao_grupo->setEditable(false);
            $descricao_grupo->style = 'background-color: #f5f5f5';
            $descricoes_grupos[$i] = $descricao_grupo;
        }
        
        // Campo para números sorteados como string
        $numeros_sorteados = new TText('numeros_sorteados');
        $numeros_sorteados->setSize('100%', 60);
        $numeros_sorteados->placeholder = 'Números separados por vírgula (ex: 1234,5678,9012)';
        
        // Adiciona os campos ao formulário
        $this->form->addFields([$sorteio_id]);
        $this->form->addFields([new TLabel('Extração:')], [$extracao_descricao]);
        $this->form->addFields([new TLabel('Data:')], [$data_sorteio]);
        $this->form->addFields([new TLabel('Hora:')], [$hora_sorteio]);
        $this->form->addFields([new TLabel('Situação:')], [$situacao_display]);
        
        $this->form->addContent(['<hr><h4>Números dos Prêmios</h4>']);
        
        // Adiciona campos de prêmios em linhas de 2
        for ($i = 1; $i <= 10; $i++) {
            $this->form->addFields(
                [new TLabel("{$i}º Prêmio:")], [$premios[$i]], [$grupos[$i]], [$descricoes_grupos[$i]]
            );
        }
        
        $this->form->addContent(['<hr>']);
        $this->form->addFields([new TLabel('Números Sorteados:')], [$numeros_sorteados]);
        
        // Botões de ação
        $btn_save = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $btn_clear = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser orange');
        $btn_close_draw = $this->form->addAction('Encerrar', new TAction([$this, 'onCloseDraw']), 'fa:lock red');
        $btn_back = $this->form->addAction('Fechar', new TAction(['ResultadoList', 'onReload']), 'fa:times blue');
        
        // JavaScript para sincronizar campos
                // JavaScript para sincronizar campos e calcular grupo/descrição
        
        $this->form->addContent(['<script>
            function chkGrupoDescricao(idx) {
                var premioField = document.querySelector("[name=\'premio_" + idx + "\']");
                var grupoField = document.querySelector("[name=\'grupo_" + idx + "\']");
                var descricaoField = document.querySelector("[name=\'descricao_grupo_" + idx + "\']");
                
                if (premioField && premioField.value && premioField.value.length === 4) {
                    var dezmilhar = parseInt(premioField.value.substring(2));
                    
                    if (dezmilhar === 0) {
                        dezmilhar = 100;
                    }
                    
                    var grupo = "";
                    var descricao = "";
                    
                    // Lógica baseada no código Angular
                    if (dezmilhar <= 4 && dezmilhar >= 1) {
                        grupo = "01"; descricao = "AVESTRUZ";
                    } else if (dezmilhar <= 8 && dezmilhar >= 5) {
                        grupo = "02"; descricao = "AGUIA";
                    } else if (dezmilhar <= 12 && dezmilhar >= 9) {
                        grupo = "03"; descricao = "BURRO";
                    } else if (dezmilhar <= 16 && dezmilhar >= 13) {
                        grupo = "04"; descricao = "BORBOLETA";
                    } else if (dezmilhar <= 20 && dezmilhar >= 17) {
                        grupo = "05"; descricao = "CACHORRO";
                    } else if (dezmilhar <= 24 && dezmilhar >= 21) {
                        grupo = "06"; descricao = "CABRA";
                    } else if (dezmilhar <= 28 && dezmilhar >= 25) {
                        grupo = "07"; descricao = "CARNEIRO";
                    } else if (dezmilhar <= 32 && dezmilhar >= 29) {
                        grupo = "08"; descricao = "CAMELO";
                    } else if (dezmilhar <= 36 && dezmilhar >= 33) {
                        grupo = "09"; descricao = "COBRA";
                    } else if (dezmilhar <= 40 && dezmilhar >= 37) {
                        grupo = "10"; descricao = "COELHO";
                    } else if (dezmilhar <= 44 && dezmilhar >= 41) {
                        grupo = "11"; descricao = "CAVALO";
                    } else if (dezmilhar <= 48 && dezmilhar >= 45) {
                        grupo = "12"; descricao = "ELEFANTE";
                    } else if (dezmilhar <= 52 && dezmilhar >= 49) {
                        grupo = "13"; descricao = "GALO";
                    } else if (dezmilhar <= 56 && dezmilhar >= 53) {
                        grupo = "14"; descricao = "GATO";
                    } else if (dezmilhar <= 60 && dezmilhar >= 57) {
                        grupo = "15"; descricao = "JACARE";
                    } else if (dezmilhar <= 64 && dezmilhar >= 61) {
                        grupo = "16"; descricao = "LEAO";
                    } else if (dezmilhar <= 68 && dezmilhar >= 65) {
                        grupo = "17"; descricao = "MACACO";
                    } else if (dezmilhar <= 72 && dezmilhar >= 69) {
                        grupo = "18"; descricao = "PORCO";
                    } else if (dezmilhar <= 76 && dezmilhar >= 73) {
                        grupo = "19"; descricao = "PAVAO";
                    } else if (dezmilhar <= 80 && dezmilhar >= 77) {
                        grupo = "20"; descricao = "PERU";
                    } else if (dezmilhar <= 84 && dezmilhar >= 81) {
                        grupo = "21"; descricao = "TOURO";
                    } else if (dezmilhar <= 88 && dezmilhar >= 85) {
                        grupo = "22"; descricao = "TIGRE";
                    } else if (dezmilhar <= 92 && dezmilhar >= 89) {
                        grupo = "23"; descricao = "URSO";
                    } else if (dezmilhar <= 96 && dezmilhar >= 93) {
                        grupo = "24"; descricao = "VEADO";
                    } else if (dezmilhar <= 100 && dezmilhar >= 97) {
                        grupo = "25"; descricao = "VACA";
                    }
                    
                    if (grupoField) grupoField.value = grupo;
                    if (descricaoField) descricaoField.value = descricao;
                } else {
                    // Limpa os campos se o número não estiver completo
                    if (grupoField) grupoField.value = "";
                    if (descricaoField) descricaoField.value = "";
                }
            }
            
            function updateNumerosString() {
                var numeros = [];
                for(var i = 1; i <= 10; i++) {
                    var campo = document.querySelector("[name=\'premio_" + i + "\']");
                    if(campo && campo.value && campo.value.trim() !== "") {
                        numeros.push(campo.value.trim());
                    }
                }
                var numerosField = document.querySelector("[name=\'numeros_sorteados\']");
                if(numerosField) {
                    numerosField.value = numeros.join(",");
                }
            }
            
            function updatePremiosFields() {
                var numerosField = document.querySelector("[name=\'numeros_sorteados\']");
                if(numerosField && numerosField.value) {
                    var numeros = numerosField.value.split(",");
                    for(var i = 0; i < 10; i++) {
                        var campo = document.querySelector("[name=\'premio_" + (i+1) + "\']");
                        if(campo) {
                            campo.value = (i < numeros.length) ? numeros[i].trim() : "";
                            // Recalcula grupo e descrição após preencher
                            if(campo.value.length === 4) {
                                chkGrupoDescricao(i+1);
                            }
                        }
                    }
                }
            }
            
            // Adiciona evento ao campo de números sorteados
            document.addEventListener("DOMContentLoaded", function() {
                var numerosField = document.querySelector("[name=\'numeros_sorteados\']");
                if(numerosField) {
                    numerosField.addEventListener("blur", updatePremiosFields);
                }
            });
        </script>']);
        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');
        
        // Container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        
        parent::add($container);
    }

    public function onClose()
    {
        TScript::create("Template.closeRightPanel()");
    }
    
    /**
     * Carrega os dados do sorteio para edição
     */
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                TTransaction::open('permission');
                
                $sorteio = new MovSorteio($param['key']);
                $this->sorteio_data = $sorteio;
                
                // Preenche os dados básicos
                $data = new stdClass;
                $data->sorteio_id = $sorteio->sorteio_id;
                $data->extracao_descricao = $sorteio->extracao->descricao ?? '';
                $data->data_sorteio = TDate::date2br($sorteio->data_sorteio);
                $data->hora_sorteio = $sorteio->hora_sorteio;
                $data->situacao_display = $sorteio->situacao == 'A' ? 'Aberto' : 'Encerrado';
                $data->numeros_sorteados = $sorteio->numeros_sorteados ?? '';
                
                // Separa os números individuais e calcula grupos/descrições
                if (!empty($sorteio->numeros_sorteados)) {
                    $numeros = explode(',', $sorteio->numeros_sorteados);
                    for ($i = 0; $i < count($numeros) && $i < 10; $i++) {
                        $numero = trim($numeros[$i]);
                        $data->{"premio_" . ($i + 1)} = $numero;
                        
                        // Calcula grupo e descrição
                        if (strlen($numero) == 4) {
                            $dezmilhar = intval(substr($numero, 2));
                            if ($dezmilhar === 0) $dezmilhar = 100;
                            
                            $grupo_desc = $this->calcularGrupoDescricao($dezmilhar);
                            $data->{"grupo_" . ($i + 1)} = $grupo_desc['grupo'];
                            $data->{"descricao_grupo_" . ($i + 1)} = $grupo_desc['descricao'];
                        }
                    }
                }
                
                $this->form->setData($data);
                
                for ($i = 1; $i <= 10; $i++) {
                    if ($i > $sorteio->extracao->premiacao_maxima) {
                        TQuickForm::disableField("form_resultado","premio_{$i}");
                    }
                }
                
                // Controla a habilitação dos campos baseado na situação
                $this->controlFieldsAccess($sorteio->situacao);
                
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Controla o acesso aos campos baseado na situação do sorteio
     */
    private function controlFieldsAccess($situacao)
    {
        $editable = ($situacao == 'A'); // Apenas sorteios abertos são editáveis
        
        // Controla os campos de prêmios, grupos e descrições
        for ($i = 1; $i <= 10; $i++) {
            $field = $this->form->getField("premio_{$i}");
            if ($field) {
                $field->setEditable($editable);
            }
            
            // Grupos e descrições sempre ficam desabilitados (são calculados automaticamente)
            $grupo_field = $this->form->getField("grupo_{$i}");
            if ($grupo_field) {
                $grupo_field->setEditable(false);

            }
            
            $desc_field = $this->form->getField("descricao_grupo_{$i}");
            if ($desc_field) {
                $desc_field->setEditable(false);
            }
        }
        
        // Controla o campo de números sorteados
        $numeros_field = $this->form->getField('numeros_sorteados');
        if ($numeros_field) {
            $numeros_field->setEditable($editable);
        }
        
        // Controla a visibilidade dos botões
        if (!$editable) {
            // Para sorteios encerrados, esconde botões de ação
            TScript::create('
                $("[name=\'btn_save\']").hide();
                $("[name=\'btn_clear\']").hide();
                $("[name=\'btn_close_draw\']").hide();
            ');
        } else {
            // Para sorteios abertos, mostra botões apropriados
            $has_numbers = !empty($this->sorteio_data->numeros_sorteados ?? '');
            if (!$has_numbers) {
                TScript::create('$("[name=\'btn_close_draw\']").hide();');
            }
        }
    }
    
    /**
     * Salva o resultado do sorteio
     */
    public function onSave($param)
    {
        try
        {
            $this->form->validate();
            $data = $this->form->getData();
            
            TTransaction::open('permission');
            
            $sorteio = new MovSorteio($data->sorteio_id);
            
            // Verifica se o sorteio ainda está aberto
            if ($sorteio->situacao != 'A') {
                throw new Exception('Este sorteio já foi encerrado e não pode ser modificado.');
            }
            
            // Usa o campo de texto como fonte principal
            $numeros_string = trim($data->numeros_sorteados ?? '');
            
            // Se não há números no campo de texto, monta a partir dos campos individuais
            if (empty($numeros_string)) {
                $numeros = [];
                for ($i = 1; $i <= 10; $i++) {
                    $premio = trim($data->{"premio_{$i}"} ?? '');
                    if (!empty($premio)) {
                        $numeros[] = $premio;
                    }
                }
                $numeros_string = implode(',', $numeros);
            }
            
            $sorteio->numeros_sorteados = $numeros_string;
            $sorteio->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Resultado salvo com sucesso!');
            
            // Recarrega o formulário
            $this->onEdit(['key' => $data->sorteio_id]);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Limpa o resultado do sorteio
     */
    public function onClear($param)
    {
        try
        {
            $data = $this->form->getData();
            
            // Confirma a ação
            $action = new TAction([$this, 'onConfirmClear']);
            $action->setParameter('sorteio_id', $data->sorteio_id);
            
            new TQuestion('Deseja realmente limpar o resultado deste sorteio?', $action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Confirma a limpeza do resultado
     */
    public function onConfirmClear($param)
    {
        try
        {
            TTransaction::open('permission');
            
            $sorteio = new MovSorteio($param['sorteio_id']);
            
            // Verifica se o sorteio ainda está aberto
            if ($sorteio->situacao != 'A') {
                throw new Exception('Este sorteio já foi encerrado e não pode ser modificado.');
            }
            
            $sorteio->numeros_sorteados = '';
            $sorteio->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Resultado limpo com sucesso!');
            
            // Recarrega o formulário
            $this->onEdit(['key' => $param['sorteio_id']]);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Encerra o sorteio
     */
    public function onCloseDraw($param)
    {
        try
        {
            $data = $this->form->getData();
            
            // Confirma a ação
            $action = new TAction([$this, 'onConfirmCloseDraw']);
            $action->setParameter('sorteio_id', $data->sorteio_id);
            
            new TQuestion('Deseja realmente encerrar este sorteio? Esta ação não poderá ser desfeita.', $action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Confirma o encerramento do sorteio
     */
    public function onConfirmCloseDraw($param)
    {
        try
        {
            TTransaction::open('permission');
            
            $sorteio = new MovSorteio($param['sorteio_id']);
            
            // Verifica se há números sorteados
            if (empty($sorteio->numeros_sorteados)) {
                throw new Exception('Não é possível encerrar um sorteio sem números sorteados.');
            }
            
            $sorteio->situacao = 'F'; // F = Fechado/Encerrado
            $sorteio->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Sorteio encerrado com sucesso!');
            
            // Recarrega o formulário
            $this->onEdit(['key' => $param['sorteio_id']]);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


    /**
     * Calcula o grupo e descrição baseado no número
     */
    private function calcularGrupoDescricao($dezmilhar)
    {
        $animais = [
            [1, 4, '01', 'AVESTRUZ'],
            [5, 8, '02', 'AGUIA'],
            [9, 12, '03', 'BURRO'],
            [13, 16, '04', 'BORBOLETA'],
            [17, 20, '05', 'CACHORRO'],
            [21, 24, '06', 'CABRA'],
            [25, 28, '07', 'CARNEIRO'],
            [29, 32, '08', 'CAMELO'],
            [33, 36, '09', 'COBRA'],
            [37, 40, '10', 'COELHO'],
            [41, 44, '11', 'CAVALO'],
            [45, 48, '12', 'ELEFANTE'],
            [49, 52, '13', 'GALO'],
            [53, 56, '14', 'GATO'],
            [57, 60, '15', 'JACARE'],
            [61, 64, '16', 'LEAO'],
            [65, 68, '17', 'MACACO'],
            [69, 72, '18', 'PORCO'],
            [73, 76, '19', 'PAVAO'],
            [77, 80, '20', 'PERU'],
            [81, 84, '21', 'TOURO'],
            [85, 88, '22', 'TIGRE'],
            [89, 92, '23', 'URSO'],
            [93, 96, '24', 'VEADO'],
            [97, 100, '25', 'VACA']
        ];
        
        foreach ($animais as $animal) {
            if ($dezmilhar >= $animal[0] && $dezmilhar <= $animal[1]) {
                return ['grupo' => $animal[2], 'descricao' => $animal[3]];
            }
        }
        
        return ['grupo' => '', 'descricao' => ''];
    }
}