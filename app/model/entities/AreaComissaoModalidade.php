<?php

use Adianti\Database\TRecord;

class AreaComissaoModalidade extends TRecord
{
    const TABLENAME = 'cfg_area_comissao_modalidade';
    const PRIMARYKEY = 'area_comissao_modalidade_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('modalidade_id');
        parent::addAttribute('comissao');
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