<?php

use Adianti\Database\TRecord;

class AreaCotacao extends TRecord
{
    const TABLENAME = 'cfg_area_cotacao';
    const PRIMARYKEY = 'area_cotacao_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('extracao_id');
        parent::addAttribute('modalidade_id');
        parent::addAttribute('multiplicador');
    }

    public function get_area()
    {
        return Area::find($this->area_id);
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