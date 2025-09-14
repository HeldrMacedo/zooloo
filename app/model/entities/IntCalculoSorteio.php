<?php

use Adianti\Database\TRecord;

class IntCalculoSorteio extends TRecord
{
    const TABLENAME = 'int_calculo_sorteio';
    const PRIMARYKEY = 'calculo_id';
    const IDPOLICY = 'max';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
        parent::addAttribute('abreviacao');
        parent::addAttribute('orientacao');
        parent::addAttribute('premiacao_maxima');
        parent::addAttribute('ordem');
        parent::addAttribute('ativo');
    }
}