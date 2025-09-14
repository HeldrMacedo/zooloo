<?php

use Adianti\Database\TRecord;

class Extracao extends TRecord
{
    const TABLENAME = 'cad_extracao';
    const PRIMARYKEY = 'extracao_id';
    const IDPOLICY = 'max';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('filtro_banca');
        parent::addAttribute('descricao');
        parent::addAttribute('descricao_mobile');
        parent::addAttribute('hora_limite');
        parent::addAttribute('segunda');
        parent::addAttribute('terca');
        parent::addAttribute('quarta');
        parent::addAttribute('quinta');
        parent::addAttribute('sexta');
        parent::addAttribute('sabado');
        parent::addAttribute('domingo');
        parent::addAttribute('premiacao_maxima');
        parent::addAttribute('ultimo_sorteio_numero');
        parent::addAttribute('gerar_restante');
        parent::addAttribute('ativo');
        parent::addAttribute('dia_sorteio_inicial');
        parent::addAttribute('extracao_instantanea');
        parent::addAttribute('calculo_id');
        parent::addAttribute('limite_palpite');


    }
}