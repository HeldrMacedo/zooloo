<?php

use Adianti\Database\TRecord;

class AreaLimite extends TRecord
{
    const TABLENAME = 'cfg_area_limite';
    const PRIMARYKEY = 'area_limite_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('modalidade_id');
        parent::addAttribute('limite_palpite');
    }

    public function get_area()
    {
        return Area::find($this->area_id);
    }

    public function get_modalidade()
    {
        return Modalidade::find($this->modalidade_id);
    }
}