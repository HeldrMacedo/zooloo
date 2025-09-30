<?php

use Adianti\Database\TRecord;

class ExtracaoDescarga extends TRecord
{
    const TABLENAME = 'cfg_extracao_descarga';
    const PRIMARYKEY = 'extracao_descarga_id';
    const IDPOLICY = 'max';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('extracao_id');
        parent::addAttribute('modalidade_id');
        parent::addAttribute('limite_descarga');
    }

    public function get_extracao()
    {
        return Extracao::find($this->extracao_id);
    }

    public function get_modalidade()
    {
        return Modalidade::find($this->modalidade_id);
    }
}