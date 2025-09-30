<?php

use Adianti\Database\TRecord;

class MovSorteio extends TRecord
{
    const TABLENAME = 'mov_sorteio';
    const PRIMARYKEY = 'sorteio_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('extracao_id');
        parent::addAttribute('sorteio_numero');
        parent::addAttribute('data_sorteio');
        parent::addAttribute('hora_sorteio');
        parent::addAttribute('situacao');
        parent::addAttribute('numeros_sorteados');
    }

    public function get_extracao()
    {
        return Extracao::find($this->extracao_id);
    }   

}