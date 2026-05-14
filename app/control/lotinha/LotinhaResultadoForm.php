<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class LotinhaResultadoForm extends TPage
{
    const FILTRO_BANCA = IntJogo::FILTRO_BANCA_LOTINHA;
    const DEZENAS      = 15;
    const MASK         = '00,00,00,00,00,00,00,00,00,00,00,00,00,00,00';

    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->form = new BootstrapFormBuilder('form_lotinha_resultado');
        $this->form->setFormTitle('Lotinha — Lançar Resultado');

        $sorteio_id = new TEntry('sorteio_id');
        $sorteio_id->setEditable(false);
        $sorteio_id->style = 'display:none';

        $hora_sorteio = new TEntry('hora_sorteio');
        $hora_sorteio->setEditable(false);

        $sorteio_numero   = new TEntry('sorteio_numero');
        $data_display     = new TEntry('data_display');
        $situacao_display = new TEntry('situacao_display');
        $sorteio_numero->setEditable(false);
        $data_display->setEditable(false);
        $situacao_display->setEditable(false);

        $numeros_sorteados = new TEntry('numeros_sorteados');
        $numeros_sorteados->setMask(self::MASK);
        $numeros_sorteados->placeholder = self::MASK;
        $numeros_sorteados->setSize('100%');

        $this->form->addAction('Salvar',         new TAction([$this, 'onSave']),      'fa:save green');
        $this->form->addAction('Limpar',         new TAction([$this, 'onClear']),     'fa:eraser orange');
        $this->form->addAction('Fechar Sorteio', new TAction([$this, 'onCloseDraw']), 'fa:lock red');
        $this->form->addFields([$sorteio_id]);
        $this->form->addFields([new TLabel('Nº Sorteio:')], [$sorteio_numero],
                               [new TLabel('Situação:')],   [$situacao_display]);
        $this->form->addFields([new TLabel('Data Sorteio:')], [$data_display],
                               [new TLabel('Hora Sorteio:')], [$hora_sorteio]);
        $this->form->addContent(['<hr>']);
        $label = new TLabel('Números Sorteados (15 dezenas):');
        $label->setSize('100%');
        $this->form->addFields([$label], [$numeros_sorteados]);

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        parent::add($container);
    }

    /**
     * Valida se a extração selecionada pertence ao filtro_banca = 4 (Lotinha).
     */
    public static function assertExtracaoLotinha($extracao_id): void
    {
        $extracao = new Extracao($extracao_id);
        if ((int) $extracao->filtro_banca !== self::FILTRO_BANCA) {
            throw new Exception('Extração informada não pertence à Lotinha.');
        }
    }

    /**
     * Valida o formato dos números sorteados (15 dezenas separadas por vírgula).
     */
    public static function validarNumerosSorteados(?string $numeros): string
    {
        $numeros = trim($numeros ?? '');
        $partes  = array_values(array_filter(explode(',', $numeros), fn($p) => trim($p) !== ''));
        if (count($partes) !== self::DEZENAS) {
            throw new Exception('Informe exatamente ' . self::DEZENAS . ' dezenas separadas por vírgula.');
        }
        foreach ($partes as $dezena) {
            if (!preg_match('/^\d{2}$/', trim($dezena))) {
                throw new Exception('Cada dezena deve ter exatamente 2 dígitos numéricos.');
            }
        }
        return implode(',', array_map('trim', $partes));
    }

    /**
     * Verifica se o horário atual já permite o lançamento do resultado.
     */
    public static function podeLancar(string $hora_sorteio, string $hora_atual): bool
    {
        if (empty($hora_sorteio)) {
            return true;
        }
        return $hora_atual >= $hora_sorteio;
    }

    public function onSearch($param)
    {
        $data = $this->form->getData();
        if (empty($data->data_sorteio) || empty($data->extracao_id)) {
            new TMessage('error', 'Informe a data e a extração.');
            return;
        }
        try {
            TTransaction::open('permission');
            self::assertExtracaoLotinha($data->extracao_id);

            $criteria = new TCriteria;
            $criteria->add(new TFilter('data_sorteio', '=', $data->data_sorteio));
            $criteria->add(new TFilter('extracao_id',  '=', $data->extracao_id));
            $sorteios = (new TRepository('MovSorteio'))->load($criteria);
            TTransaction::close();

            if (empty($sorteios)) {
                new TMessage('info', 'Não existe sorteio para esta data e extração.');
                return;
            }
            $this->onEdit(['key' => $sorteios[0]->sorteio_id]);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param)
    {
        if (empty($param['key'])) {
            return;
        }
        try {
            TTransaction::open('permission');
            $sorteio = new MovSorteio($param['key']);
            self::assertExtracaoLotinha($sorteio->extracao_id);
            TTransaction::close();

            $obj = new stdClass;
            $obj->sorteio_id        = $sorteio->sorteio_id;
            $obj->sorteio_numero    = $sorteio->sorteio_numero;
            $obj->data_display      = TDate::date2br($sorteio->data_sorteio);
            $obj->hora_sorteio      = $sorteio->hora_sorteio;
            $obj->situacao_display  = $sorteio->situacao === 'A' ? 'Aberto' : 'Encerrado';
            $obj->numeros_sorteados = $sorteio->numeros_sorteados ?? '';
            $this->form->setData($obj);

            if ($sorteio->situacao !== 'A') {
                $this->form->getField('numeros_sorteados')?->setEditable(false);
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave($param)
    {
        try {
            $data = $this->form->getData();

            if (empty($data->sorteio_id)) {
                throw new Exception('Nenhum sorteio carregado. Clique em Buscar primeiro.');
            }

            TTransaction::open('permission');
            $sorteio = new MovSorteio($data->sorteio_id);
            self::assertExtracaoLotinha($sorteio->extracao_id);

            if ($sorteio->situacao !== 'A') {
                throw new Exception('Este sorteio já foi encerrado.');
            }

            if (!self::podeLancar((string) $sorteio->hora_sorteio, date('H:i:s'))) {
                throw new Exception("O sorteio só pode ser lançado após às {$sorteio->hora_sorteio}");
            }

            $sorteio->numeros_sorteados = self::validarNumerosSorteados($data->numeros_sorteados ?? '');
            $sorteio->store();
            TTransaction::close();

            new TMessage('info', 'Resultado salvo com sucesso!');
            $this->onEdit(['key' => $data->sorteio_id]);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear($param)
    {
        $data = $this->form->getData();
        if (empty($data->sorteio_id)) {
            $this->form->clear();
            return;
        }
        $action = new TAction([$this, 'onConfirmClear']);
        $action->setParameter('sorteio_id', $data->sorteio_id);
        new TQuestion('Deseja realmente limpar o resultado deste sorteio?', $action);
    }

    public function onConfirmClear($param)
    {
        try {
            TTransaction::open('permission');
            $sorteio = new MovSorteio($param['sorteio_id']);
            self::assertExtracaoLotinha($sorteio->extracao_id);
            if ($sorteio->situacao !== 'A') {
                throw new Exception('Este sorteio já foi encerrado.');
            }
            $sorteio->numeros_sorteados = '';
            $sorteio->store();
            TTransaction::close();

            new TMessage('info', 'Resultado limpo com sucesso!');
            $this->onEdit(['key' => $param['sorteio_id']]);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onCloseDraw($param)
    {
        $data = $this->form->getData();
        if (empty($data->sorteio_id)) {
            return;
        }
        $action = new TAction([$this, 'onConfirmCloseDraw']);
        $action->setParameter('sorteio_id', $data->sorteio_id);
        new TQuestion('Deseja realmente fechar este sorteio? Esta ação não poderá ser desfeita.', $action);
    }

    public function onConfirmCloseDraw($param)
    {
        try {
            TTransaction::open('permission');
            $sorteio = new MovSorteio($param['sorteio_id']);
            self::assertExtracaoLotinha($sorteio->extracao_id);
            if (empty($sorteio->numeros_sorteados)) {
                throw new Exception('Não é possível fechar um sorteio sem números sorteados.');
            }
            $sorteio->situacao = 'F';
            $sorteio->store();
            TTransaction::close();

            new TMessage('info', 'Sorteio encerrado com sucesso!');
            $this->onEdit(['key' => $param['sorteio_id']]);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
