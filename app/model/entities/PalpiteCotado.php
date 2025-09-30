<?php

use Adianti\Database\TRecord;

class PalpiteCotado extends TRecord
{
    const TABLENAME = 'cfg_palpite_cotado';
    const PRIMARYKEY = 'palpite_cotado_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('modalidade_id');
        parent::addAttribute('palpite');
        parent::addAttribute('cotacao');
        parent::addAttribute('ativo');
    }

    public function get_modalidade()
    {
        return Modalidade::find($this->modalidade_id);
    }
}