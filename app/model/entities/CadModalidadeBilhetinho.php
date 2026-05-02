<?php

use Adianti\Database\TRecord;

class CadModalidadeBilhetinho extends TRecord
{
    const TABLENAME = 'cad_modalidade_bilhetinho';
    const PRIMARYKEY = 'modalidade_id';
    // PK composta (modalidade_id, colocacao): modalidade_id é FK→cad_modalidade
    // Operações de save/delete devem incluir colocacao no WHERE explicitamente
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('colocacao');
        parent::addAttribute('multiplicador');
        parent::addAttribute('limite_descarga');
        parent::addAttribute('limite_palpite');
        parent::addAttribute('limite_aceite');
        parent::addAttribute('ativo');
    }

    public function get_modalidade()
    {
        return Modalidade::find($this->modalidade_id);
    }
}
