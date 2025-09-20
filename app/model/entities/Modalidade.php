<?php

use Adianti\Database\TRecord;

class Modalidade extends TRecord
{
    const TABLENAME = 'cad_modalidade';
    const PRIMARYKEY = 'modalidade_id';
    const IDPOLICY = 'max';

    const MILHAR_INSTANTANEA    = 22;
    const MILHAR_MOTO_01        = 34;
    const MILHAR_MOTO_02        = 35;
    const MILHAR_MOTO_03        = 36;

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('jogo_id');
        parent::addAttribute('ordem');
        parent::addAttribute('apresentacao');
        parent::addAttribute('multiplicador');
        parent::addAttribute('limite_descarga');
        parent::addAttribute('limite_palpite');
        parent::addAttribute('limite_aceite');
        parent::addAttribute('ativo');
        parent::addAttribute('multiplicador_colocacao_01');
        parent::addAttribute('multiplicador_colocacao_02');
        parent::addAttribute('multiplicador_colocacao_03');
        parent::addAttribute('multiplicador_colocacao_04');
        parent::addAttribute('multiplicador_colocacao_05');
        parent::addAttribute('limite_min_sorteio_diario');
        parent::addAttribute('limite_min_sorteio_colocacao_diario');
    }

    public function get_jogo()
    {
        return IntJogo::find($this->jogo_id);
    }
}