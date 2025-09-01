<?php

use Adianti\Database\TRecord;


class Gerente extends TRecord
{
    const TABLENAME = 'cad_coletor';
    const PRIMARYKEY = 'coletor_id';
    const IDPOLICY = 'max';
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('area_id');
        parent::addAttribute('usuario_id');
        parent::addAttribute('acesso_web');
        parent::addAttribute('outras_areas');
        parent::addAttribute('ativo');
        
    }

    public function get_usuario()
    {
        return SystemUser::find($this->usuario_id);
    }

    public function get_area()
    {
        return Area::find($this->area_id);
    }

}