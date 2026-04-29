<?php

use Adianti\Database\TRecord;

class Terminal extends TRecord
{
    const TABLENAME = 'cad_terminal';
    const PRIMARYKEY = 'terminal_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('vendedor_id');
        parent::addAttribute('tipo');
        parent::addAttribute('serial');
        parent::addAttribute('multi_usuario');
        parent::addAttribute('ativo');
    }

    public function get_vendedor()
    {
        return Vendedor::find($this->vendedor_id);
    }
}
