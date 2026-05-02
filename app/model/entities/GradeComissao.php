<?php

class GradeComissao extends TRecord
{
    const TABLENAME  = 'cfg_grade_comissao';
    const PRIMARYKEY = 'grade_comissao_id';
    const IDPOLICY   = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
    }

    public function get_itens()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('grade_comissao_id', '=', $this->grade_comissao_id));
        return (new TRepository('GradeComissaoItens'))->load($criteria);
    }
}
