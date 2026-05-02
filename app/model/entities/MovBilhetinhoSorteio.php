<?php

use Adianti\Database\TRecord;

class MovBilhetinhoSorteio extends TRecord
{
    const TABLENAME = 'mov_bilhetinho_sorteio';
    const PRIMARYKEY = 'bilhetinho_id';
    // PK composta (bilhetinho_id, sorteio_id)
    // Ao buscar por sorteio use TFilter adicional em sorteio_id
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('sorteio_id');
        parent::addAttribute('modalidade_id');
        parent::addAttribute('palpites');
        parent::addAttribute('palpites_quantidade');
        parent::addAttribute('colocao_inicial');
        parent::addAttribute('colocao_final');
        parent::addAttribute('valor_palpites');
        parent::addAttribute('total_sorteio');
        parent::addAttribute('comissao_sorteio');
        parent::addAttribute('sorteado');
        parent::addAttribute('sorteado_colocacao');
        parent::addAttribute('sorteado_valor');
        parent::addAttribute('sorteado_pago');
        parent::addAttribute('sorteado_pago_data');
    }

    public function get_sorteio()
    {
        return MovSorteio::find($this->sorteio_id);
    }

    public function get_modalidade()
    {
        return Modalidade::find($this->modalidade_id);
    }
}
