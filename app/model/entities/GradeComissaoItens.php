<?php

class GradeComissaoItens extends TRecord
{
    const TABLENAME  = 'cfg_grade_comissao_itens';
    const PRIMARYKEY = 'grade_comissao_itens_id';
    const IDPOLICY   = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('comissao');
        parent::addAttribute('grade_comissao_id');
        parent::addAttribute('modalidade_id');
    }

    public function get_modalidade()
    {
        return Modalidade::find($this->modalidade_id);
    }

    public function get_grade()
    {
        return GradeComissao::find($this->grade_comissao_id);
    }
}
