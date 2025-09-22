<?php

use Adianti\Database\TRecord;

class AreaExtracao extends TRecord
{
    const TABLENAME = 'cfg_area_extracao';
    const PRIMARYKEY = 'area_extracao_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('extracao_id');
        parent::addAttribute('ativo');
    }

    public function get_area()
    {
        return Area::find($this->area_id);
    }

    public function get_extracao()
    {
        return Extracao::find($this->extracao_id);
    }
}