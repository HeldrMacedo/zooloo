<?php

use Adianti\Database\TRecord;

class MovJbSortPalpite extends TRecord
{
    const TABLENAME = 'mov_jb_sort_palpite';
    const PRIMARYKEY = 'jb_palpites_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('jb_sorteio_id');
        parent::addAttribute('jb_id');
        parent::addAttribute('sorteio_id');
        parent::addAttribute('modalidade_id');
        parent::addAttribute('palpite');
        parent::addAttribute('valor_palpite');
        parent::addAttribute('jogou_colocacao_01');
        parent::addAttribute('jogou_colocacao_02');
        parent::addAttribute('jogou_colocacao_03');
        parent::addAttribute('jogou_colocacao_04');
        parent::addAttribute('jogou_colocacao_05');
        parent::addAttribute('jogou_colocacao_06');
        parent::addAttribute('jogou_colocacao_07');
        parent::addAttribute('jogou_colocacao_08');
        parent::addAttribute('jogou_colocacao_09');
        parent::addAttribute('jogou_colocacao_10');
        parent::addAttribute('premio_colocacao_01');
        parent::addAttribute('premio_colocacao_02');
        parent::addAttribute('premio_colocacao_03');
        parent::addAttribute('premio_colocacao_04');
        parent::addAttribute('premio_colocacao_05');
        parent::addAttribute('ganhou_premio_total');
        parent::addAttribute('pago_premio_total');
    }
}
