<?php

use Adianti\Database\TRecord;

class VwSorteio extends TRecord
{
    const TABLENAME  = 'vw_sorteio';
    const PRIMARYKEY = 'sorteio_id';
    const IDPOLICY   = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('extracao_id');
        parent::addAttribute('descricao_mobile');
        parent::addAttribute('sorteio_numero');
        parent::addAttribute('data_sorteio');
        parent::addAttribute('hora_sorteio');
        parent::addAttribute('situacao');
        parent::addAttribute('numeros_sorteados');
        parent::addAttribute('descricao');
        parent::addAttribute('premiacao_maxima');
        parent::addAttribute('ativo_extracao');
        parent::addAttribute('filtro_banca');
        parent::addAttribute('limite_palpite');
    }
}
