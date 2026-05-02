<?php

use Adianti\Database\TRecord;

class MovBilhetinho extends TRecord
{
    const TABLENAME = 'mov_bilhetinho';
    const PRIMARYKEY = 'bilhetinho_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('coletor_id');
        parent::addAttribute('terminal_id');
        parent::addAttribute('sorteios_ids');
        parent::addAttribute('sorteios_quantidade');
        parent::addAttribute('vendedor_id');
        parent::addAttribute('bilhete_numero');
        parent::addAttribute('data_hora');
        parent::addAttribute('nome_cliente');
        parent::addAttribute('fone_cliente');
        parent::addAttribute('total_bilhetinho');
        parent::addAttribute('comissao_valor');
        parent::addAttribute('comissao_pago');
        parent::addAttribute('string_autorizacao');
        parent::addAttribute('cancelado');
        parent::addAttribute('cancelado_motivo');
        parent::addAttribute('data_reimpressao');
        parent::addAttribute('reimpressao');
        parent::addAttribute('data_cancelamento');
    }

    public function get_area()
    {
        return Area::find($this->area_id);
    }

    public function get_vendedor()
    {
        return Vendedor::find($this->vendedor_id);
    }
}
