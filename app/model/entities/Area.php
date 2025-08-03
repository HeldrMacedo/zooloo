<?php

namespace entities;

use Adianti\Database\TRecord;

class Area extends TRecord
{
    const TABLENAME = 'area';
    const PRIMARYKEY = 'area_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
        parent::addAttribute('complemento');
        parent::addAttribute('ativo');
    }
}